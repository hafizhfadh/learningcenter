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

/**
 * @group Learning Paths
 *
 * Endpoints for browsing learning paths, viewing details, enrolling and tracking progress.
 */
class LearningPathController extends Controller
{
    use ApiResponse;

    /**
     * List learning paths
     *
     * Return a cursor-paginated list of learning paths accessible to the current user.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @queryParam per_page int Number of items per page, maximum 50. Example: 15
     * @queryParam cursor string The pagination cursor from the previous response. Example: "eyJpZCI6M30"
     * @queryParam search string Filter by learning path name or description. Example: "programming"
     * @queryParam enrolled string Filter by enrollment status: all, enrolled, not_enrolled. Example: "enrolled"
     * @response 200 scenario="Learning paths found" {
     *   "code": 200,
     *   "message": "Learning paths retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Programming Basics",
     *       "slug": "programming-basics",
     *       "description": "Introductory programming path",
     *       "banner_url": "https://example.com/banners/programming-basics.png",
     *       "is_active": true,
     *       "total_estimated_time": 7200,
     *       "courses_count": 3,
     *       "is_enrolled": true,
     *       "progress": 45,
     *       "created_at": "2024-01-01T12:00:00Z",
     *       "updated_at": "2024-01-02T12:00:00Z"
     *     }
     *   ],
     *   "pagination": {
     *     "per_page": 15,
     *     "next_cursor": "eyJpZCI6M30",
     *     "prev_cursor": null,
     *     "has_more": true,
     *     "count": 1
     *   }
     * }
     * @response 200 scenario="No learning paths" {
     *   "code": 200,
     *   "message": "No learning paths found",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 401 scenario="Missing or invalid tokens" {
     *   "code": 401,
     *   "message": "Unauthorized",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 403 scenario="Expired app token" {
     *   "code": 403,
     *   "message": "Forbidden",
     *   "data": [],
     *   "pagination": {}
     * }
     */
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

    /**
     * Get learning path details
     *
     * Retrieve full details for a single learning path, including enrolled courses and user progress.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @urlParam id int required The ID of the learning path. Example: 1
     * @response 200 scenario="Learning path found" {
     *   "code": 200,
     *   "message": "Learning path details retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Programming Basics",
     *     "slug": "programming-basics",
     *     "description": "Introductory programming path",
     *     "banner_url": "https://example.com/banners/programming-basics.png",
     *     "is_active": true,
     *     "total_estimated_time": 7200,
     *     "courses_count": 3,
     *     "is_enrolled": true,
     *     "progress": 45,
     *     "courses": [],
     *     "enrollment": {
     *       "enrolled_at": "2024-01-01T12:00:00Z",
     *       "progress": 45,
     *       "status": "enrolled"
     *     },
     *     "created_at": "2024-01-01T12:00:00Z",
     *     "updated_at": "2024-01-02T12:00:00Z"
     *   },
     *   "pagination": {}
     * }
     * @response 404 scenario="Learning path not accessible" {
     *   "code": 404,
     *   "message": "Learning path not found or not accessible",
     *   "data": [],
     *   "pagination": {}
     * }
     */
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

    /**
     * Enroll in a learning path
     *
     * Enroll the authenticated user in the given learning path and its courses.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @urlParam id int required The ID of the learning path to enroll in. Example: 1
     * @response 201 scenario="Enrollment created" {
     *   "code": 201,
     *   "message": "Successfully enrolled in learning path",
     *   "data": {
     *     "learning_path_id": 1,
     *     "user_id": 1,
     *     "enrolled_at": "2024-01-01T12:00:00Z",
     *     "progress": 0,
     *     "status": "enrolled",
     *     "courses_enrolled": 3
     *   },
     *   "pagination": {}
     * }
     * @response 400 scenario="Already enrolled" {
     *   "code": 400,
     *   "message": "You are already enrolled in this learning path",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 404 scenario="Learning path not accessible" {
     *   "code": 404,
     *   "message": "Learning path not found or not accessible",
     *   "data": [],
     *   "pagination": {}
     * }
     */
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

    /**
     * Get learning path progress
     *
     * Retrieve progress for the authenticated user across all enrolled learning paths.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @queryParam per_page int Number of items per page, maximum 50. Example: 15
     * @queryParam cursor string The pagination cursor from the previous response. Example: "eyJpZCI6M30"
     * @response 200 scenario="Progress retrieved" {
     *   "code": 200,
     *   "message": "Learning path progress retrieved successfully",
     *   "data": [
     *     {
     *       "learning_path": {
     *         "id": 1,
     *         "name": "Programming Basics",
     *         "slug": "programming-basics",
     *         "banner_url": "https://example.com/banners/programming-basics.png",
     *         "total_estimated_time": 7200,
     *         "courses_count": 3
     *       },
     *       "enrollment": {
     *         "enrolled_at": "2024-01-01T12:00:00Z",
     *         "progress": 45,
     *         "status": "enrolled"
     *       },
     *       "course_progress": []
     *     }
     *   ],
     *   "pagination": {
     *     "per_page": 15,
     *     "next_cursor": "eyJpZCI6M30",
     *     "prev_cursor": null,
     *     "has_more": true,
     *     "count": 1
     *   }
     * }
     * @response 200 scenario="No enrollments" {
     *   "code": 200,
     *   "message": "No learning path enrollments found",
     *   "data": [],
     *   "pagination": {}
     * }
     */
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
