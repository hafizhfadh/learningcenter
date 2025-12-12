<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\LearningPath;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LearningPathController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
     {
         $user = Auth::user();
         $perPage = min($request->get('per_page', 15), 50);
         $search = $request->get('search');
         $enrolledFilter = $request->get('enrolled', 'all');
         $cursor = $request->get('cursor');

         $query = LearningPath::with(['courses'])
             ->accessibleByUser($user)
             ->orderBy('id'); // Ensure consistent ordering for cursor pagination

         // Apply search filter
         if ($search) {
             $query->where(function ($q) use ($search) {
                 $q->where('name', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%");
             });
         }

         // Apply enrollment filter
         if ($enrolledFilter === 'enrolled') {
             $query->whereHas('enrollments', function ($q) use ($user) {
                 $q->where('user_id', $user->id);
             });
         } elseif ($enrolledFilter === 'not_enrolled') {
             $query->whereDoesntHave('enrollments', function ($q) use ($user) {
                 $q->where('user_id', $user->id);
             });
         }

         // Use cursor pagination
         $learningPaths = $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

         if ($learningPaths->isEmpty()) {
             return $this->successResponse([], 'No learning paths found');
         }

         $data = $learningPaths->map(function ($learningPath) use ($user) {
              return [
                  'id' => $learningPath->id,
                  'name' => $learningPath->name,
                  'slug' => $learningPath->slug,
                  'description' => $learningPath->description,
                  'banner_url' => $learningPath->banner_url,
                  'is_active' => (bool) $learningPath->is_active,
                  'total_estimated_time' => $learningPath->total_estimated_time,
                  'courses_count' => $learningPath->courses_count,
                  'is_enrolled' => $learningPath->isUserEnrolled($user->id),
                  'progress' => $learningPath->getProgressForUser($user->id),
                  'created_at' => $learningPath->created_at,
                  'updated_at' => $learningPath->updated_at,
              ];
          });

         $pagination = [
             'per_page' => $learningPaths->perPage(),
             'next_cursor' => $learningPaths->nextCursor()?->encode(),
             'prev_cursor' => $learningPaths->previousCursor()?->encode(),
             'has_more' => $learningPaths->hasMorePages(),
             'count' => $learningPaths->count(),
         ];

         return $this->paginatedResponse($data, $pagination, 'Learning paths retrieved successfully');
     }

    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $learningPath = LearningPath::with([
             'courses' => function ($query) {
                 $query->where('is_published', true)
                       ->orderBy('learning_path_course.order_index');
             },
             'courses.lessons',
             'enrollments' => function ($query) use ($user) {
                 $query->where('user_id', $user->id);
             }
         ])
         ->accessibleByUser($user)
         ->find($id);

        if (!$learningPath) {
            return $this->errorResponse('Learning path not found or not accessible', 404);
        }

        $enrollment = $learningPath->enrollments->first();

        $coursesData = $learningPath->courses->map(function ($course) use ($user) {
            // Get user's course enrollment and progress
            $courseEnrollment = $course->enrollments()->where('user_id', $user->id)->first();
            $completedLessons = 0;
            $totalLessons = $course->lessons->count();

            if ($courseEnrollment) {
                $completedLessons = $course->progressLogs()
                    ->where('user_id', $user->id)
                    ->where('action', 'completed')
                    ->distinct('lesson_id')
                    ->count();
            }

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'banner_url' => $course->banner_url,
                'estimated_time' => $course->estimated_time,
                'is_published' => (bool) $course->is_published,
                'order_index' => $course->pivot->order_index,
                'lessons_count' => $totalLessons,
                'user_progress' => [
                    'is_enrolled' => (bool) $courseEnrollment,
                    'progress' => $courseEnrollment ? $courseEnrollment->progress : 0,
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $totalLessons,
                ],
                'created_at' => $course->created_at,
            ];
        });

        $data = [
             'id' => $learningPath->id,
             'name' => $learningPath->name,
             'slug' => $learningPath->slug,
             'description' => $learningPath->description,
             'banner_url' => $learningPath->banner_url,
             'is_active' => (bool) $learningPath->is_active,
             'total_estimated_time' => $learningPath->total_estimated_time,
             'courses_count' => $learningPath->courses_count,
             'is_enrolled' => (bool) $enrollment,
             'progress' => $enrollment ? $enrollment->progress : 0,
             'courses' => $coursesData,
             'enrollment' => $enrollment ? [
                 'enrolled_at' => $enrollment->created_at,
                 'progress' => $enrollment->progress,
                 'status' => $enrollment->enrollment_status ?? 'active',
             ] : null,
             'created_at' => $learningPath->created_at,
             'updated_at' => $learningPath->updated_at,
         ];

        return $this->successResponse($data, 'Learning path details retrieved successfully');
    }

    public function enroll(int $id): JsonResponse
    {
        $user = Auth::user();

        $learningPath = LearningPath::with('courses')
             ->accessibleByUser($user)
             ->find($id);

        if (!$learningPath) {
            return $this->errorResponse('Learning path not found or not accessible', 404);
        }

        // Check if already enrolled
        if ($learningPath->isUserEnrolled($user->id)) {
            return $this->errorResponse('You are already enrolled in this learning path', 400);
        }

        // Create learning path enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'learning_path_id' => $learningPath->id,
            'progress' => 0,
            'enrollment_status' => 'enrolled',
        ]);

        // Auto-enroll in all courses within the learning path
        $coursesEnrolled = 0;
        foreach ($learningPath->courses as $course) {
            $existingCourseEnrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$existingCourseEnrollment) {
                Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'progress' => 0,
                    'enrollment_status' => 'enrolled',
                ]);
                $coursesEnrolled++;
            }
        }

        $data = [
            'learning_path_id' => $learningPath->id,
            'user_id' => $user->id,
            'enrolled_at' => $enrollment->created_at,
            'progress' => $enrollment->progress,
            'status' => $enrollment->enrollment_status,
            'courses_enrolled' => $coursesEnrolled,
        ];

        return $this->successResponse($data, 'Successfully enrolled in learning path', 201);
    }

    public function progress(Request $request): JsonResponse
     {
         $user = Auth::user();
         $perPage = min($request->get('per_page', 15), 50);
         $cursor = $request->get('cursor');

         $enrollments = Enrollment::with([
              'learningPath.courses.lessons'
          ])
         ->where('user_id', $user->id)
         ->whereNotNull('learning_path_id')
         ->orderBy('id') // Ensure consistent ordering for cursor pagination
         ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

         if ($enrollments->isEmpty()) {
             return $this->successResponse([], 'No learning path enrollments found');
         }

         $data = $enrollments->map(function ($enrollment) use ($user) {
             $learningPath = $enrollment->learningPath;
             
             $courseProgress = $learningPath->courses->map(function ($course) use ($user) {
                 $courseEnrollment = $course->enrollments()->where('user_id', $user->id)->first();
                 $completedLessons = 0;
                 $totalLessons = $course->lessons->count();

                 if ($courseEnrollment) {
                     $completedLessons = $course->progressLogs()
                         ->where('user_id', $user->id)
                         ->where('action', 'completed')
                         ->distinct('lesson_id')
                         ->count();
                 }

                 return [
                     'course_id' => $course->id,
                     'course_title' => $course->title,
                     'progress' => $courseEnrollment ? $courseEnrollment->progress : 0,
                     'completed_lessons' => $completedLessons,
                     'total_lessons' => $totalLessons,
                     'status' => $courseEnrollment ? $courseEnrollment->enrollment_status : 'not_enrolled',
                 ];
             });

             return [
                 'learning_path' => [
                     'id' => $learningPath->id,
                     'name' => $learningPath->name,
                     'slug' => $learningPath->slug,
                     'banner_url' => $learningPath->banner_url,
                     'total_estimated_time' => $learningPath->total_estimated_time,
                     'courses_count' => $learningPath->courses_count,
                 ],
                 'enrollment' => [
                     'enrolled_at' => $enrollment->created_at,
                     'progress' => $enrollment->progress,
                     'status' => $enrollment->enrollment_status,
                 ],
                 'course_progress' => $courseProgress,
             ];
         });

         $pagination = [
             'per_page' => $enrollments->perPage(),
             'next_cursor' => $enrollments->nextCursor()?->encode(),
             'prev_cursor' => $enrollments->previousCursor()?->encode(),
             'has_more' => $enrollments->hasMorePages(),
             'count' => $enrollments->count(),
         ];

         return $this->paginatedResponse($data, $pagination, 'Learning path progress retrieved successfully');
     }
}
