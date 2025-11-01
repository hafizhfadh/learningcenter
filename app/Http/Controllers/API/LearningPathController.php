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
 * APIs for managing learning paths for students
 */
class LearningPathController extends Controller
{
    use ApiResponse;

    /**
      * Get Learning Paths List
      * 
      * Retrieve a list of learning paths accessible to authenticated users with institution-bound roles.
      * Only school_teacher, school_admin, and student roles can access learning paths.
      * 
      * @authenticated
      * 
      * @queryParam cursor string Cursor for pagination (encoded cursor from previous response). Example: eyJpZCI6MTAsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0
       * @queryParam per_page int Number of items per page (max 50). Example: 15
       * @queryParam search string Search term for filtering learning paths by name or description. Example: programming
       * @queryParam enrolled string Filter by enrollment status (enrolled, not_enrolled, all). Example: enrolled
      * 
      * @response 200 scenario="Success with learning paths" {
      *   "code": 200,
      *   "message": "Learning paths retrieved successfully",
      *   "data": [
      *     {
      *       "id": 1,
      *       "name": "Full Stack Web Development",
      *       "slug": "full-stack-web-development",
      *       "description": "Complete web development learning path covering frontend and backend technologies",
      *       "banner_url": "https://example.com/storage/banners/fullstack.jpg",
      *       "is_active": true,
      *       "total_estimated_time": 120,
      *       "courses_count": 8,
      *       "is_enrolled": true,
      *       "progress": 45.5,
      *       "created_at": "2024-01-01T00:00:00.000000Z",
      *       "updated_at": "2024-01-01T00:00:00.000000Z"
      *     }
      *   ],
      *   "pagination": {
       *     "per_page": 15,
       *     "next_cursor": "eyJpZCI6MTUsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
       *     "prev_cursor": null,
       *     "has_more": true,
       *     "count": 15
       *   }
      * }
     * 
     * @response 200 scenario="Empty result" {
     *   "code": 200,
     *   "message": "No learning paths found",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data array Array of learning path objects
     * @responseField data[].id int Learning path ID
     * @responseField data[].name string Learning path name
     * @responseField data[].slug string Learning path slug
     * @responseField data[].description string Learning path description
     * @responseField data[].banner_url string Learning path banner image URL
     * @responseField data[].is_active boolean Whether the learning path is active
     * @responseField data[].total_estimated_time int Total estimated time in hours
     * @responseField data[].courses_count int Number of courses in the learning path
     * @responseField data[].is_enrolled boolean Whether the current user is enrolled
     * @responseField data[].progress float User's progress percentage (0-100)
     
     * @responseField pagination object Pagination information
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
     * Get Learning Path Details
     * 
     * Retrieve detailed information about a specific learning path including all courses,
     * lessons, and user progress information.
     * 
     * @authenticated
     * 
     * @urlParam id int required The learning path ID. Example: 1
     * 
     * @response 200 scenario="Learning path found" {
     *   "code": 200,
     *   "message": "Learning path details retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Full Stack Web Development",
     *     "slug": "full-stack-web-development",
     *     "description": "Complete web development learning path covering frontend and backend technologies",
     *     "banner_url": "https://example.com/storage/banners/fullstack.jpg",
     *     "is_active": true,
     *     "total_estimated_time": 120,
     *     "courses_count": 8,
     *     "is_enrolled": true,
     *     "progress": 45.5,
     *     "institution": {
     *       "id": 1,
     *       "name": "Harvard University",
     *       "slug": "harvard-university"
     *     },
     *     "courses": [
     *       {
     *         "id": 1,
     *         "title": "HTML & CSS Fundamentals",
     *         "slug": "html-css-fundamentals",
     *         "description": "Learn the basics of HTML and CSS",
     *         "banner_url": "https://example.com/storage/banners/html-css.jpg",
     *         "estimated_time": 15,
     *         "is_published": true,
     *         "order_index": 1,
     *         "lessons_count": 12,
     *         "user_progress": {
     *           "is_enrolled": true,
     *           "progress": 75.0,
     *           "completed_lessons": 9,
     *           "total_lessons": 12
     *         },
     *         "created_at": "2024-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "enrollment": {
     *       "enrolled_at": "2024-01-15T10:30:00.000000Z",
     *       "progress": 45.5,
     *       "status": "active"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 404 scenario="Learning path not found" {
     *   "code": 404,
     *   "message": "Learning path not found or not accessible",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object Learning path details with courses and progress
     * @responseField data.courses array Array of courses in the learning path
     * @responseField data.courses[].user_progress object User's progress in each course
     * @responseField data.enrollment object User's enrollment information
     * @responseField pagination object Pagination information (empty for single resource)
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
     * Enroll in Learning Path
     * 
     * Enroll the authenticated student in a specific learning path.
     * This will also automatically enroll the student in all courses within the learning path.
     * 
     * @authenticated
     * 
     * @urlParam id int required The learning path ID. Example: 1
     * 
     * @response 201 scenario="Successfully enrolled" {
     *   "code": 201,
     *   "message": "Successfully enrolled in learning path",
     *   "data": {
     *     "learning_path_id": 1,
     *     "user_id": 5,
     *     "enrolled_at": "2024-01-15T10:30:00.000000Z",
     *     "progress": 0,
     *     "status": "active",
     *     "courses_enrolled": 8
     *   },
     *   "pagination": {}
     * }
     * 
     * @response 400 scenario="Already enrolled" {
     *   "code": 400,
     *   "message": "You are already enrolled in this learning path",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @response 404 scenario="Learning path not found" {
     *   "code": 404,
     *   "message": "Learning path not found or not accessible",
     *   "data": [],
     *   "pagination": {}
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data object Enrollment information
     * @responseField data.courses_enrolled int Number of courses automatically enrolled
     * @responseField pagination object Pagination information (empty for this endpoint)
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
     * Get User's Learning Path Progress
     * 
     * Get detailed progress information for the authenticated user's enrolled learning paths.
     * 
     * @authenticated
     * 
     * @queryParam cursor string Cursor for pagination (encoded cursor from previous response). Example: eyJpZCI6MTAsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0
      * @queryParam per_page int Number of items per page (max 50). Example: 15
     * 
     * @response 200 scenario="Progress retrieved successfully" {
     *   "code": 200,
     *   "message": "Learning path progress retrieved successfully",
     *   "data": [
     *     {
     *       "learning_path": {
     *         "id": 1,
     *         "name": "Full Stack Web Development",
     *         "slug": "full-stack-web-development",
     *         "banner_url": "https://example.com/storage/banners/fullstack.jpg",
     *         "total_estimated_time": 120,
     *         "courses_count": 8
     *       },
     *       "enrollment": {
     *         "enrolled_at": "2024-01-15T10:30:00.000000Z",
     *         "progress": 45.5,
     *         "status": "active"
     *       },
     *       "course_progress": [
     *         {
     *           "course_id": 1,
     *           "course_title": "HTML & CSS Fundamentals",
     *           "progress": 75.0,
     *           "completed_lessons": 9,
     *           "total_lessons": 12,
     *           "status": "in_progress"
     *         }
     *       ]
     *     }
     *   ],
     *   "pagination": {
      *     "per_page": 15,
      *     "next_cursor": null,
      *     "prev_cursor": null,
      *     "has_more": false,
      *     "count": 3
      *   }
     * }
     * 
     * @responseField code int HTTP status code
     * @responseField message string Response message
     * @responseField data array Array of learning path progress objects
     * @responseField data[].learning_path object Learning path basic information
     * @responseField data[].enrollment object User's enrollment details
     * @responseField data[].course_progress array Progress for each course in the learning path
     * @responseField pagination object Pagination information
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
