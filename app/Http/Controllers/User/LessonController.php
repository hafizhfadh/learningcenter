<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use App\Services\LessonService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LessonController extends Controller
{
    public function __construct(
        private LessonService $lessonService
    ) {}

    /**
     * Display the lesson index page with the first lesson
     */
    public function index(string $learningPath, string $courseSlug)
    {
        try {
            // Validate course slug format
            if (!$courseSlug || !is_string($courseSlug) || strlen($courseSlug) > 255) {
                return view('user.lesson.index', [
                    'learningPath' => $learningPath,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Invalid course identifier.',
                ]);
            }

            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return view('user.lesson.index', [
                    'learningPath' => $learningPath,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Course not found or no longer available.',
                ]);
            }

            $userId = Auth::id();

            // Check if user has access to this course
            if (!$this->lessonService->hasUserAccessToCourse($course, $userId)) {
                return view('user.lesson.index', [
                    'learningPath' => $learningPath,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'You do not have access to this course.',
                ]);
            }

            // Calculate progress
            $progressData = $this->lessonService->calculateCourseProgress($course, $userId);
            
            // Get first lesson
            $lesson = $this->lessonService->getFirstLesson($course);
            
            // Get grouped lessons
            $groupedLessons = $this->lessonService->getGroupedLessons($course);

            // Get next lesson for navigation
            $nextLesson = $lesson ? $this->lessonService->getNextLesson($lesson, $course) : null;

            

            return view('user.lesson.index', [
                'learningPath' => $learningPath,
                'course' => $course,
                'lesson' => $lesson,
                'groupedLessons' => $groupedLessons,
                'completedLessons' => $progressData['completed_lessons'],
                'totalLessons' => $progressData['total_lessons'],
                'progress' => $progressData['progress_percentage'],
                'previousLesson' => null,
                'nextLesson' => $nextLesson,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in lesson index: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id()
            ]);
            
            return view('user.lesson.index', [
                'learningPath' => $learningPath,
                'course' => null,
                'lesson' => null,
                'error' => 'Database error occurred. Please try again later.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in lesson index: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('user.lesson.index', [
                'learningPath' => $learningPath,
                'course' => null,
                'lesson' => null,
                'error' => 'An unexpected error occurred while loading the course.',
            ]);
        }
    }

    /**
     * Display a specific lesson
     */
    public function show(string $learningPath, string $courseSlug, Lesson $lesson): View
    {
        try {
            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return view('user.lesson.show', [
                    'learningPath' => $learningPath,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Course not found or no longer available.',
                ]);
            }

            $userId = Auth::id();

            // Verify lesson belongs to course
            if ($lesson->lessonSection->course_id !== $course->id) {
                return view('user.lesson.show', [
                    'learningPath' => $learningPath,
                    'course' => $course,
                    'lesson' => null,
                    'error' => 'Lesson does not belong to this course.',
                ]);
            }

            // Check if user has access to this lesson
            if (!$this->lessonService->hasUserAccessToLesson($lesson, $userId)) {
                return view('user.lesson.show', [
                    'learningPath' => $learningPath,
                    'course' => $course,
                    'lesson' => null,
                    'error' => 'You do not have access to this lesson.',
                ]);
            }

            // Calculate progress
            $progressData = $this->lessonService->calculateCourseProgress($course, $userId);
            
            // Get grouped lessons
            $groupedLessons = $this->lessonService->getGroupedLessons($course);

            // Get navigation lessons
            $previousLesson = $this->lessonService->getPreviousLesson($lesson, $course);
            $nextLesson = $this->lessonService->getNextLesson($lesson, $course);

            // Get lesson content based on type
            $lessonContent = $this->lessonService->getLessonContent($lesson);

            return view('user.lesson.show', [
                'learningPath' => $learningPath,
                'course' => $course,
                'lesson' => $lesson,
                'lessonContent' => $lessonContent,
                'groupedLessons' => $groupedLessons,
                'completedLessons' => $progressData['completed_lessons'],
                'totalLessons' => $progressData['total_lessons'],
                'progress' => $progressData['progress_percentage'],
                'previousLesson' => $previousLesson,
                'nextLesson' => $nextLesson,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in lesson show: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id,
                'user_id' => Auth::id()
            ]);
            return view('user.lesson.show', [
                'learningPath' => $learningPath,
                'course' => null,
                'lesson' => null,
                'error' => 'Database error occurred. Please try again later.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in lesson show: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('user.lesson.show', [
                'learningPath' => $learningPath,
                'course' => null,
                'lesson' => null,
                'error' => 'An unexpected error occurred while loading the lesson.',
            ]);
        }
    }

    /**
     * Mark lesson as complete and redirect to next lesson
     */
    public function nextLesson(LessonRequest $request, string $learningPath, string $courseSlug, Lesson $lesson): RedirectResponse
    {
        try {
            $userId = Auth::id();
            $course = $this->lessonService->getCourseWithLessons($courseSlug);

            if (!$course) {
                return redirect()->route('user.lesson.index', [$learningPath, $courseSlug])
                    ->with('error', 'Course not found or no longer available.');
            }

            // Verify lesson belongs to course
            if ($lesson->lessonSection->course_id !== $course->id) {
                return redirect()->route('user.lesson.index', [$learningPath, $courseSlug])
                    ->with('error', 'Lesson does not belong to this course.');
            }

            // Check if user has access to this lesson
            if (!$this->lessonService->hasUserAccessToLesson($lesson, $userId)) {
                return redirect()->back()->with('error', 'You do not have access to this lesson.');
            }

            // Mark lesson as complete
            $this->lessonService->markLessonAsComplete($lesson, $userId, $course->id);

            // Get next lesson
            $nextLesson = $this->lessonService->getNextLesson($lesson, $course);

            if ($nextLesson) {
                return redirect()->route('user.lesson.show', [$learningPath, $courseSlug, $nextLesson->slug])
                    ->with('success', 'Congratulations! You have successfully completed the lesson.');
            }

            return redirect()->route('user.lesson.index', [$learningPath, $courseSlug])
                ->with('success', 'Congratulations! You have completed all lessons in this course.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in nextLesson: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id,
                'user_id' => $userId ?? Auth::id()
            ]);
            return redirect()->back()->with('error', 'Database error occurred. Please try again later.');
        } catch (\Exception $e) {
            Log::error('Error in nextLesson: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An unexpected error occurred while processing your request.');
        }
    }

    /**
     * Initiate a course with self-enrollment capability and enhanced tracking
     */
    public function initiateCourse(string $learningPath, string $courseSlug)
    {
        try {
            // Validate course slug format
            if (!$courseSlug || !is_string($courseSlug) || strlen($courseSlug) > 255) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid course identifier.',
                    'error_type' => 'validation_error'
                ], 400);
            }

            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found or no longer available.',
                    'error_type' => 'course_not_found'
                ], 404);
            }

            $userId = Auth::id();

            // Check user eligibility for self-initiation
            $eligibilityCheck = $this->lessonService->isUserEligibleForSelfInitiation($course, $userId);
            
            // If user is not eligible and auto-enrollment is not available
            if (!$eligibilityCheck['eligible'] && !$eligibilityCheck['auto_enroll_available']) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $eligibilityCheck['reasons']),
                    'error_type' => 'eligibility_failed',
                    'reasons' => $eligibilityCheck['reasons']
                ], 403);
            }

            $wasAutoEnrolled = false;
            $enrollment = null;

            // If user is eligible and auto-enrollment is available, enroll them
            if ($eligibilityCheck['eligible'] && $eligibilityCheck['auto_enroll_available']) {
                try {
                    $enrollment = $this->lessonService->autoEnrollUser($course, $userId);
                    $wasAutoEnrolled = true;
                    
                    Log::info('User auto-enrolled in course', [
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'course_slug' => $courseSlug,
                        'enrollment_id' => $enrollment->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Auto-enrollment failed: ' . $e->getMessage(), [
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'course_slug' => $courseSlug
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to enroll in course. Please try again later.',
                        'error_type' => 'enrollment_failed'
                    ], 500);
                }
            }

            // Verify user now has access (either was already enrolled or just auto-enrolled)
            if (!$this->lessonService->hasUserAccessToCourse($course, $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify course access. Please contact support.',
                    'error_type' => 'access_verification_failed'
                ], 403);
            }

            // Track self-initiated course start with enhanced logging
            $trackingResult = $this->lessonService->trackSelfInitiatedCourseStart($course, $userId, $wasAutoEnrolled);
            
            if (!$trackingResult['success']) {
                Log::error('Course initiation tracking failed: ' . $trackingResult['message'], [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'course_slug' => $courseSlug,
                    'was_auto_enrolled' => $wasAutoEnrolled
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Course access granted but tracking failed. You can still proceed to the course.',
                    'error_type' => 'tracking_failed',
                    'redirect_url' => route('user.lesson.index', [$learningPath, $courseSlug])
                ], 500);
            }

            // Success response with detailed information
            $responseData = [
                'success' => true,
                'message' => $trackingResult['message'],
                'redirect_url' => route('user.lesson.index', [$learningPath, $courseSlug]),
                'course_info' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->description
                ],
                'initiation_details' => [
                    'auto_enrolled' => $wasAutoEnrolled,
                    'first_lesson_id' => $trackingResult['first_lesson']?->id,
                    'first_lesson_title' => $trackingResult['first_lesson']?->title,
                    'progress_log_id' => $trackingResult['progress_log']?->id,
                    'enrollment_id' => $trackingResult['enrollment']?->id
                ]
            ];

            Log::info('Course self-initiation successful', [
                'user_id' => $userId,
                'course_id' => $course->id,
                'course_slug' => $courseSlug,
                'auto_enrolled' => $wasAutoEnrolled,
                'progress_log_id' => $trackingResult['progress_log']?->id
            ]);

            return response()->json($responseData);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in course self-initiation: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again later.',
                'error_type' => 'database_error'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in course self-initiation: ' . $e->getMessage(), [
                'learningPath' => $learningPath,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while initiating the course. Please try again later. ' . $e->getMessage(),
                'error_type' => 'unexpected_error'
            ], 500);
        }
    }
}
