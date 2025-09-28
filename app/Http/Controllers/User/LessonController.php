<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\UserLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    public function index($exam, $courseSlug)
    {
        $course = Course::with(['lessonGroups.lessons' => function ($q) {
            $q->where('is_published', true)->orderBy('order');
        }])->where('slug', $courseSlug)->firstOrFail();
    
        $userId = Auth::id();

        $completedLessons = UserLessonProgress::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->whereIn('status', ['completed', 'mastered'])
            ->count();
    
        $totalLessons = $course->lessonGroups->flatMap->lessons->count();
    
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
    
        $lesson = $course->lessonGroups
            ->flatMap->lessons
            ->sortBy('order')
            ->first();
    
        $groupedLessons = $course->lessonGroups->mapWithKeys(function ($group) {
            return [$group->name => $group->lessons];
        });
    
        return view('user.lesson.index', compact(
            'exam',
            'course',
            'lesson',
            'groupedLessons',
            'completedLessons',
            'totalLessons',
            'progress'
        ))->with([
            'previousLesson' => null,
            'nextLesson' => $lesson ? $lesson->nextLesson : null,
        ]);
    }

    public function show($exam, $courseSlug, Lesson $lesson)
    {
        $course = Course::with(['lessonGroups.lessons' => function ($q) {
            $q->where('is_published', true)->orderBy('order');
        }])->where('slug', $courseSlug)->firstOrFail();

        $userId = Auth::id();

        $userProgress = UserLessonProgress::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('lesson_id');

        $completedLessons = $userProgress->filter(function ($progress) {
            return in_array($progress->status, ['completed', 'mastered']);
        })->count();

        $totalLessons = $course->lessonGroups->flatMap->lessons->count();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $groupedLessons = $course->lessonGroups->mapWithKeys(function ($group) {
            return [$group->name => $group->lessons];
        });

        return view('user.lesson.show', compact(
            'exam',
            'course',
            'lesson',
            'groupedLessons',
            'completedLessons',
            'totalLessons',
            'progress',
            'userProgress'
        ))->with([
            'previousLesson' => $lesson->previousLesson,
            'nextLesson' => $lesson->nextLesson,
        ]);
    }


    public function nextLesson(Request $request, $exam, $courseSlug, Lesson $lesson)
    {
        $userId = Auth::id();

        $course = Course::where('slug', $courseSlug)->firstOrFail();

        // Optional: Validasi akses user ke lesson/course

        $progress = UserLessonProgress::firstOrNew([
            'user_id' => $userId,
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
        ]);

        $progress->progress_percentage = 100;
        $progress->status = 'completed';
        $progress->completed_at = now();
        if (!$progress->started_at) {
            $progress->started_at = now();
        }
        $progress->save();

        $nextLesson = $lesson->nextLesson;

        if ($nextLesson) {
            return redirect()->route('lesson.show', [$exam, $courseSlug, $nextLesson->slug])
                ->with('success', 'Selamat! Kamu berhasil menyelesaikan pelajaran.');
        }

        return redirect()->route('course.index', [$exam, $courseSlug])
            ->with('success', 'Selamat! Kamu telah menyelesaikan semua pelajaran di course ini.');
    }
}
