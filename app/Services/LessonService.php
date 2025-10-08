<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ProgressLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const PROGRESS_CACHE_TTL = 300; // 5 minutes

    /**
     * Get course with lessons grouped by sections (with caching)
     */
    public function getCourseWithLessons(string $courseSlug): Course
    {
        $cacheKey = "course_with_lessons_{$courseSlug}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($courseSlug) {
            return Course::with([
                'lessonSections' => function ($query) {
                    $query->orderBy('order_index');
                },
                'lessonSections.lessons' => function ($query) {
                    $query->where('is_published', true)
                          ->orderBy('order_index')
                          ->select('id', 'title', 'slug', 'lesson_type', 'lesson_banner', 'lesson_video',
                                  'content_body', 'order_index', 'lesson_section_id', 'course_id', 
                                  'is_published', 'created_at', 'updated_at');
                }
            ])
            ->select('id', 'title', 'slug', 'description', 'created_at', 'updated_at')
            ->where('slug', $courseSlug)
            ->firstOrFail();
        });
    }

    /**
     * Calculate course progress for a user (with caching)
     */
    public function calculateCourseProgress(Course $course, int $userId): array
    {
        $cacheKey = "course_progress_{$course->id}_{$userId}";
        
        return Cache::remember($cacheKey, self::PROGRESS_CACHE_TTL, function () use ($course, $userId) {
            try {
                // Use a single query to get both counts
                $progressData = DB::table('progress_logs')
                    ->where('user_id', $userId)
                    ->where('course_id', $course->id)
                    ->whereNull('deleted_at') // Respect soft deletes
                    ->selectRaw('
                        COUNT(*) as total_progress_entries,
                        SUM(CASE WHEN action = "completed" THEN 1 ELSE 0 END) as completed_lessons
                    ')
                    ->first();

                // Get total lessons count efficiently
                $totalLessons = DB::table('lessons')
                    ->join('lesson_sections', 'lessons.lesson_section_id', '=', 'lesson_sections.id')
                    ->where('lesson_sections.course_id', $course->id)
                    ->where('lessons.is_published', true)
                    ->whereNull('lessons.deleted_at') // Respect soft deletes for lessons
                    ->whereNull('lesson_sections.deleted_at') // Respect soft deletes for sections
                    ->count();

                $completedLessons = $progressData->completed_lessons ?? 0;
                $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

                return [
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                    'progress_percentage' => $progress
                ];
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Database error in calculateCourseProgress', [
                    'course_id' => $course->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A',
                    'bindings' => $e->getBindings() ?? []
                ]);
                
                // Return safe defaults when database query fails
                return [
                    'completed_lessons' => 0,
                    'total_lessons' => 0,
                    'progress_percentage' => 0
                ];
            } catch (\Exception $e) {
                Log::error('Unexpected error in calculateCourseProgress', [
                    'course_id' => $course->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
                
                // Return safe defaults for any other errors
                return [
                    'completed_lessons' => 0,
                    'total_lessons' => 0,
                    'progress_percentage' => 0
                ];
            }
        });
    }

    /**
     * Get user progress for all lessons in a course (with caching)
     */
    public function getUserProgress(Course $course, int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "user_progress_{$course->id}_{$userId}";
        
        return Cache::remember($cacheKey, self::PROGRESS_CACHE_TTL, function () use ($course, $userId) {
            return ProgressLog::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->select('lesson_id', 'status', 'progress_percentage', 'completed_at', 'started_at')
                ->get()
                ->keyBy('lesson_id');
        });
    }

    /**
     * Get lessons grouped by sections (optimized)
     */
    public function getGroupedLessons(Course $course): \Illuminate\Support\Collection
    {
        $cacheKey = "grouped_lessons_{$course->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($course) {
            return $course->lessonSections->mapWithKeys(function ($section) {
                return [$section->title => $section->lessons];
            });
        });
    }

    /**
     * Get the first lesson in a course (optimized with direct query)
     */
    public function getFirstLesson(Course $course): ?Lesson
    {
        $cacheKey = "first_lesson_{$course->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($course) {
            return Lesson::join('lesson_sections', 'lessons.lesson_section_id', '=', 'lesson_sections.id')
                ->where('lesson_sections.course_id', $course->id)
                ->where('lessons.is_published', true)
                ->orderBy('lesson_sections.order_index')
                ->orderBy('lessons.order_index')
                ->select('lessons.*')
                ->first();
        });
    }

    /**
     * Mark lesson as completed and return next lesson (with cache invalidation)
     */
    public function completeLesson(Lesson $lesson, Course $course, int $userId): ?Lesson
    {
        $nextLesson = null;
        
        DB::transaction(function () use ($lesson, $course, $userId, &$nextLesson) {
            $progress = ProgressLog::firstOrNew([
                'user_id' => $userId,
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
            ]);

            $progress->progress_percentage = 100;
            $progress->status = 'completed';
            $progress->action = 'completed';
            $progress->completed_at = now();
            
            if (!$progress->started_at) {
                $progress->started_at = now();
            }
            
            $progress->save();
            
            // Invalidate relevant caches
            $this->invalidateUserProgressCache($course->id, $userId);
            
            $nextLesson = $this->getNextLesson($lesson, $course);
        });

        return $nextLesson;
    }

    /**
     * Get the next lesson in sequence (optimized with direct query)
     */
    public function getNextLesson(Lesson $currentLesson, Course $course): ?Lesson
    {
        $cacheKey = "next_lesson_{$currentLesson->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currentLesson, $course) {
            // Get current lesson's section and order
            $currentSection = $currentLesson->lessonSection;
            
            // First, try to find next lesson in the same section
            $nextInSection = Lesson::where('lesson_section_id', $currentSection->id)
                ->where('order_index', '>', $currentLesson->order_index)
                ->where('is_published', true)
                ->orderBy('order_index')
                ->first();
                
            if ($nextInSection) {
                return $nextInSection;
            }
            
            // If no next lesson in current section, find first lesson in next section
            return Lesson::join('lesson_sections', 'lessons.lesson_section_id', '=', 'lesson_sections.id')
                ->where('lesson_sections.course_id', $course->id)
                ->where('lesson_sections.order_index', '>', $currentSection->order_index)
                ->where('lessons.is_published', true)
                ->orderBy('lesson_sections.order_index')
                ->orderBy('lessons.order_index')
                ->select('lessons.*')
                ->first();
        });
    }

    /**
     * Get the previous lesson in sequence (optimized with direct query)
     */
    public function getPreviousLesson(Lesson $currentLesson, Course $course): ?Lesson
    {
        $cacheKey = "previous_lesson_{$currentLesson->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currentLesson, $course) {
            // Get current lesson's section and order
            $currentSection = $currentLesson->lessonSection;
            
            // First, try to find previous lesson in the same section
            $previousInSection = Lesson::where('lesson_section_id', $currentSection->id)
                ->where('order_index', '<', $currentLesson->order_index)
                ->where('is_published', true)
                ->orderBy('order_index', 'desc')
                ->first();
                
            if ($previousInSection) {
                return $previousInSection;
            }
            
            // If no previous lesson in current section, find last lesson in previous section
            return Lesson::join('lesson_sections', 'lessons.lesson_section_id', '=', 'lesson_sections.id')
                ->where('lesson_sections.course_id', $course->id)
                ->where('lesson_sections.order_index', '<', $currentSection->order_index)
                ->where('lessons.is_published', true)
                ->orderBy('lesson_sections.order_index', 'desc')
                ->orderBy('lessons.order_index', 'desc')
                ->select('lessons.*')
                ->first();
        });
    }

    /**
     * Check if user has access to a lesson
     */
    public function hasAccessToLesson(Lesson $lesson, int $userId): bool
    {
        // Add your access control logic here
        // For now, return true (all users have access)
        return true;
    }

    /**
     * Get lesson content based on type
     */
    public function getLessonContent(Lesson $lesson): array
    {
        $content = [
            'type' => $lesson->content_type,
            'lesson_type' => $lesson->lesson_type,
            'content' => $lesson->content,
            'video_url' => $lesson->video_url,
            'duration' => $lesson->duration,
            'quiz_data' => $lesson->quiz_data,
            'interactive_data' => $lesson->interactive_data,
        ];

        return $content;
    }

    /**
     * Check if user has access to a course
     */
    public function hasUserAccessToCourse(Course $course, int $userId): bool
    {
        // Check if user is enrolled in the course
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('enrollment_status', 'enrolled')
            ->first();

        return $enrollment !== null;
    }

    /**
     * Check if user has access to a specific lesson
     */
    public function hasUserAccessToLesson(Lesson $lesson, int $userId): bool
    {
        // Check if lesson is published
        if (!$lesson->is_published) {
            return false;
        }

        // Check if user is enrolled in the course
        $enrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $lesson->course_id)
            ->where('enrollment_status', 'enrolled')
            ->first();

        return $enrollment !== null;
    }

    /**
     * Mark a lesson as complete for a user
     */
    public function markLessonAsComplete(Lesson $lesson, int $userId, int $courseId): ProgressLog
    {
        $progress = ProgressLog::firstOrNew([
            'user_id' => $userId,
            'lesson_id' => $lesson->id,
            'course_id' => $courseId,
        ]);

        $progress->progress_percentage = 100;
        $progress->status = 'completed';
        $progress->action = 'completed';
        $progress->completed_at = now();
        
        if (!$progress->started_at) {
            $progress->started_at = now();
        }
        
        $progress->save();
        
        // Invalidate relevant caches
        $this->invalidateUserProgressCache($courseId, $userId);

        return $progress;
    }
    
    /**
     * Track course initiation in progress logs
     */
    public function trackCourseInitiation(Course $course, int $userId): ProgressLog
    {
        // Get the first lesson of the course
        $firstLesson = $this->getFirstLesson($course);
        
        if (!$firstLesson) {
            throw new \Exception('Course has no lessons available.');
        }

        // Check if course initiation has already been tracked
        $existingLog = ProgressLog::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('lesson_id', $firstLesson->id)
            ->where('action', 'course_started')
            ->first();

        if ($existingLog) {
            return $existingLog;
        }

        // Create a new progress log for course initiation
        $progressLog = ProgressLog::create([
            'user_id' => $userId,
            'course_id' => $course->id,
            'lesson_id' => $firstLesson->id,
            'action' => 'course_started',
        ]);

        // Invalidate relevant caches
        $this->invalidateUserProgressCache($course->id, $userId);

        return $progressLog;
    }

    /**
     * Check if user is eligible for course self-initiation
     */
    public function isUserEligibleForSelfInitiation(Course $course, int $userId): array
    {
        $eligibilityResult = [
            'eligible' => false,
            'reasons' => [],
            'auto_enroll_available' => false
        ];

        // Check if course is published
        if ($course->is_published) {
            $eligibilityResult['reasons'][] = 'Course is not currently available for enrollment.';
            return $eligibilityResult;
        }

        // Check if course has lessons
        $firstLesson = $this->getFirstLesson($course);
        if (!$firstLesson) {
            $eligibilityResult['reasons'][] = 'Course has no available lessons.';
            return $eligibilityResult;
        }

        // Check if user is already enrolled
        $existingEnrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            if ($existingEnrollment->enrollment_status === 'enrolled') {
                $eligibilityResult['reasons'][] = 'You are already enrolled in this course.';
                $eligibilityResult['eligible'] = true;
                return $eligibilityResult;
            } elseif ($existingEnrollment->enrollment_status === 'completed') {
                $eligibilityResult['reasons'][] = 'You have already completed this course.';
                return $eligibilityResult;
            } elseif ($existingEnrollment->enrollment_status === 'suspended') {
                $eligibilityResult['reasons'][] = 'Your enrollment in this course has been suspended.';
                return $eligibilityResult;
            }
        }

        // If no enrollment exists or enrollment is inactive, user can self-enroll
        $eligibilityResult['eligible'] = true;
        $eligibilityResult['auto_enroll_available'] = true;

        return $eligibilityResult;
    }

    /**
     * Automatically enroll user in a course for self-initiation
     */
    public function autoEnrollUser(Course $course, int $userId): Enrollment
    {
        // Check if enrollment already exists
        $existingEnrollment = Enrollment::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            // Reactivate if inactive
            if ($existingEnrollment->enrollment_status !== 'enrolled') {
                $existingEnrollment->update([
                    'enrollment_status' => 'enrolled',
                    'enrolled_at' => now(),
                    'progress' => 0
                ]);
            }
            return $existingEnrollment;
        }

        // Create new enrollment
        return Enrollment::create([
            'user_id' => $userId,
            'course_id' => $course->id,
            'enrollment_status' => 'enrolled',
            'progress' => 0,
            'enrolled_at' => now()
        ]);
    }

    /**
     * Enhanced course initiation tracking with detailed logging
     */
    public function trackSelfInitiatedCourseStart(Course $course, int $userId, bool $wasAutoEnrolled = false): array
    {
        $result = [
            'success' => false,
            'progress_log' => null,
            'enrollment' => null,
            'first_lesson' => null,
            'message' => ''
        ];

        try {
            DB::transaction(function () use ($course, $userId, $wasAutoEnrolled, &$result) {
                // Get the first lesson
                $firstLesson = $this->getFirstLesson($course);
                if (!$firstLesson) {
                    throw new \Exception('Course has no available lessons.');
                }

                // Get or create enrollment
                $enrollment = Enrollment::where('user_id', $userId)
                    ->where('course_id', $course->id)
                    ->where('enrollment_status', 'enrolled')
                    ->first();

                if (!$enrollment) {
                    throw new \Exception('User is not enrolled in this course.');
                }

                // Check if course initiation has already been tracked
                $existingLog = ProgressLog::where('user_id', $userId)
                    ->where('course_id', $course->id)
                    ->where('lesson_id', $firstLesson->id)
                    ->where('action', 'course_started')
                    ->first();

                if (!$existingLog) {
                    // Create a new progress log for course initiation
                    $progressLog = ProgressLog::create([
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'lesson_id' => $firstLesson->id,
                        'action' => 'course_started',
                        'status' => 'in_progress',
                        'progress_percentage' => 0,
                        'started_at' => now(),
                        'metadata' => json_encode([
                            'self_initiated' => true,
                            'auto_enrolled' => $wasAutoEnrolled,
                            'initiation_timestamp' => now()->toISOString()
                        ])
                    ]);
                } else {
                    $progressLog = $existingLog;
                }

                // Invalidate relevant caches
                $this->invalidateUserProgressCache($course->id, $userId);

                $result['success'] = true;
                $result['progress_log'] = $progressLog;
                $result['enrollment'] = $enrollment;
                $result['first_lesson'] = $firstLesson;
                $result['message'] = $wasAutoEnrolled 
                    ? 'Successfully enrolled and initiated course.' 
                    : 'Course initiated successfully.';
            });
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Invalidate user progress cache
     */
    private function invalidateUserProgressCache(int $courseId, int $userId): void
    {
        Cache::forget("course_progress_{$courseId}_{$userId}");
        Cache::forget("user_progress_{$courseId}_{$userId}");
        Cache::forget("grouped_lessons_{$courseId}");
    }
}