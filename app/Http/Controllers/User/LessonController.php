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
    public function index(string $exam, string $courseSlug)
    {
        try {
            // Validate course slug format
            if (!$courseSlug || !is_string($courseSlug) || strlen($courseSlug) > 255) {
                return view('user.lesson.index', [
                    'exam' => $exam,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Invalid course identifier.',
                ]);
            }

            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return view('user.lesson.index', [
                    'exam' => $exam,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Course not found or no longer available.',
                ]);
            }

            $userId = Auth::id();

            // Check if user has access to this course
            if (!$this->lessonService->hasUserAccessToCourse($course, $userId)) {
                return view('user.lesson.index', [
                    'exam' => $exam,
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
                'exam' => $exam,
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
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id()
            ]);
            return view('user.lesson.index', [
                'exam' => $exam,
                'course' => null,
                'lesson' => null,
                'error' => 'Database error occurred. Please try again later.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in lesson index: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('user.lesson.index', [
                'exam' => $exam,
                'course' => null,
                'lesson' => null,
                'error' => 'An unexpected error occurred while loading the course.',
            ]);
        }
    }

    /**
     * Display a specific lesson
     */
    public function show(string $exam, string $courseSlug, Lesson $lesson): View
    {
        try {
            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return view('user.lesson.show', [
                    'exam' => $exam,
                    'course' => null,
                    'lesson' => null,
                    'error' => 'Course not found or no longer available.',
                ]);
            }

            $userId = Auth::id();

            // Verify lesson belongs to course
            if ($lesson->lessonSection->course_id !== $course->id) {
                return view('user.lesson.show', [
                    'exam' => $exam,
                    'course' => $course,
                    'lesson' => null,
                    'error' => 'Lesson does not belong to this course.',
                ]);
            }

            // Check if user has access to this lesson
            if (!$this->lessonService->hasUserAccessToLesson($lesson, $userId)) {
                return view('user.lesson.show', [
                    'exam' => $exam,
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
                'exam' => $exam,
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
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id,
                'user_id' => Auth::id()
            ]);
            return view('user.lesson.show', [
                'exam' => $exam,
                'course' => null,
                'lesson' => null,
                'error' => 'Database error occurred. Please try again later.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in lesson show: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('user.lesson.show', [
                'exam' => $exam,
                'course' => null,
                'lesson' => null,
                'error' => 'An unexpected error occurred while loading the lesson.',
            ]);
        }
    }

    /**
     * Mark lesson as complete and redirect to next lesson
     */
    public function nextLesson(LessonRequest $request, string $exam, string $courseSlug, Lesson $lesson): RedirectResponse
    {
        try {
            $userId = Auth::id();
            $course = $this->lessonService->getCourseWithLessons($courseSlug);

            if (!$course) {
                return redirect()->route('lesson.index', [$exam, $courseSlug])
                    ->with('error', 'Course not found or no longer available.');
            }

            // Verify lesson belongs to course
            if ($lesson->lessonSection->course_id !== $course->id) {
                return redirect()->route('lesson.index', [$exam, $courseSlug])
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
                return redirect()->route('lesson.show', [$exam, $courseSlug, $nextLesson->slug])
                    ->with('success', 'Congratulations! You have successfully completed the lesson.');
            }

            return redirect()->route('lesson.index', [$exam, $courseSlug])
                ->with('success', 'Congratulations! You have completed all lessons in this course.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in nextLesson: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id,
                'user_id' => $userId ?? Auth::id()
            ]);
            return redirect()->back()->with('error', 'Database error occurred. Please try again later.');
        } catch (\Exception $e) {
            Log::error('Error in nextLesson: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'lesson_id' => $lesson->id ?? null,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'An unexpected error occurred while processing your request.');
        }
    }

    /**
     * Initiate a course and track the start in progress logs
     */
    public function initiateCourse(string $exam, string $courseSlug)
    {
        try {
            // Validate course slug format
            if (!$courseSlug || !is_string($courseSlug) || strlen($courseSlug) > 255) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid course identifier.'
                ], 400);
            }

            $course = $this->lessonService->getCourseWithLessons($courseSlug);
            
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found or no longer available.'
                ], 404);
            }

            $userId = Auth::id();

            // Check if user has access to this course
            if (!$this->lessonService->hasUserAccessToCourse($course, $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this course.'
                ], 403);
            }

            // Track course initiation
            $this->lessonService->trackCourseInitiation($course, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Course initiated successfully.',
                'redirect_url' => route('user.lesson.index', [$exam, $courseSlug])
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in course initiation: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred. Please try again later.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error in course initiation: ' . $e->getMessage(), [
                'exam' => $exam,
                'course_slug' => $courseSlug,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while initiating the course.'
            ], 500);
        }
    }
}
