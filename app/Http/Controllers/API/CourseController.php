<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * @group Courses
 *
 * Endpoints for browsing, searching and viewing course details.
 */
class CourseController extends Controller
{
    use ApiResponse;

    /**
     * List courses
     *
     * Return a paginated list of courses accessible to the current user.
     * Students only see published courses; staff can see all.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @queryParam page int The page number to return. Example: 1
     * @queryParam per_page int Number of items per page (1-100). Example: 20
     * @queryParam sort string Field to sort by: title, created_at, estimated_time. Example: "created_at"
     * @queryParam order string Sort direction: asc or desc. Example: "desc"
     * @response 200 scenario="Courses retrieved" {
     *   "code": 200,
     *   "message": "Courses retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Intro to Programming",
     *       "slug": "intro-to-programming",
     *       "description": "Learn the basics of programming.",
     *       "banner_url": "https://example.com/banners/intro-to-programming.png",
     *       "tags": ["programming", "beginner"],
     *       "estimated_time": 3600,
     *       "is_published": true,
     *       "created_at": "2024-01-01T12:00:00Z",
     *       "instructor": {
     *         "id": 10,
     *         "name": "Jane Doe",
     *         "email": "jane@example.com"
     *       },
     *       "enrollment_status": "not_enrolled",
     *       "total_lessons": 10,
     *       "total_tasks": 5
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 35,
     *     "last_page": 2,
     *     "from": 1,
     *     "to": 20,
     *     "has_more_pages": true
     *   }
     * }
     * @response 422 scenario="Validation failed" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "page": [
     *         "The page must be at least 1."
     *       ]
     *     }
     *   },
     *   "pagination": {}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sort' => 'string|in:title,created_at,estimated_time',
            'order' => 'string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $perPage = $request->get('per_page', 20);
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        $user = Auth::user();
        
        // Build query based on user role
        $query = Course::with(['creator', 'teachers', 'lessons', 'tasks'])
            ->withCount(['lessons', 'tasks']);

        // Students can only see published courses
        if ($user->hasRole('student')) {
            $query->where('is_published', true);
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        $courses = $query->paginate($perPage);

        // Transform the data to include enrollment status and instructor info
        $transformedCourses = $courses->getCollection()->map(function ($course) use ($user) {
            $enrollment = null;
            if ($user->hasRole('student')) {
                $enrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
            }

            // Get primary instructor (creator or first assigned teacher)
            $instructor = $course->creator;
            if ($course->teachers->isNotEmpty()) {
                $instructor = $course->teachers->first();
            }

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'banner_url' => $course->banner_url,
                'tags' => $course->tags,
                'estimated_time' => $course->estimated_time,
                'is_published' => $course->is_published,
                'created_at' => $course->created_at,
                'instructor' => $instructor ? [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'email' => $instructor->email,
                ] : null,
                'enrollment_status' => $enrollment ? $enrollment->enrollment_status : 'not_enrolled',
                'total_lessons' => $course->lessons_count,
                'total_tasks' => $course->tasks_count,
            ];
        });

        $pagination = [
            'current_page' => $courses->currentPage(),
            'per_page' => $courses->perPage(),
            'total' => $courses->total(),
            'last_page' => $courses->lastPage(),
            'from' => $courses->firstItem(),
            'to' => $courses->lastItem(),
            'has_more_pages' => $courses->hasMorePages(),
        ];

        return $this->paginatedResponse(
            $transformedCourses,
            $pagination,
            'Courses retrieved successfully'
        );
    }

    /**
     * Search courses
     *
     * Search courses by title, description, tags, instructor and date/time filters.
     * Supports relevance-based sorting when a search query is provided.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @queryParam q string Search term used to match title, description or tags. Example: "programming"
     * @queryParam instructor string Filter by instructor name. Example: "Jane Doe"
     * @queryParam tags string Comma-separated list of tags to filter by. Example: "beginner,backend"
     * @queryParam start_date string Filter courses created on or after this date (Y-m-d). Example: "2024-01-01"
     * @queryParam end_date string Filter courses created on or before this date (Y-m-d). Example: "2024-12-31"
     * @queryParam min_time int Minimum estimated time in minutes. Example: 30
     * @queryParam max_time int Maximum estimated time in minutes. Example: 120
     * @queryParam page int The page number to return. Example: 1
     * @queryParam per_page int Number of items per page (1-100). Example: 20
     * @queryParam sort string Sort field: title, created_at, estimated_time, relevance. Example: "relevance"
     * @queryParam order string Sort direction: asc or desc. Example: "desc"
     * @response 200 scenario="Search completed" {
     *   "code": 200,
     *   "message": "Search completed successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Intro to Programming",
     *       "slug": "intro-to-programming",
     *       "description": "Learn the basics of programming.",
     *       "banner_url": "https://example.com/banners/intro-to-programming.png",
     *       "tags": ["programming", "beginner"],
     *       "estimated_time": 3600,
     *       "is_published": true,
     *       "created_at": "2024-01-01T12:00:00Z",
     *       "instructor": {
     *         "id": 10,
     *         "name": "Jane Doe",
     *         "email": "jane@example.com"
     *       },
     *       "enrollment_status": "not_enrolled",
     *       "total_lessons": 10,
     *       "total_tasks": 5,
     *       "relevance_score": 1
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 10,
     *     "last_page": 1,
     *     "from": 1,
     *     "to": 10,
     *     "has_more_pages": false
     *   }
     * }
     * @response 422 scenario="Validation failed" {
     *   "code": 422,
     *   "message": "Validation failed",
     *   "data": {
     *     "errors": {
     *       "start_date": [
     *         "The start date does not match the format Y-m-d."
     *       ]
     *     }
     *   },
     *   "pagination": {}
     * }
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'string|max:255',
            'instructor' => 'string|max:255',
            'tags' => 'string|max:255',
            'start_date' => 'date_format:Y-m-d',
            'end_date' => 'date_format:Y-m-d|after_or_equal:start_date',
            'min_time' => 'integer|min:0',
            'max_time' => 'integer|min:0|gte:min_time',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'sort' => 'string|in:title,created_at,estimated_time,relevance',
            'order' => 'string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $perPage = $request->get('per_page', 20);
        $sort = $request->get('sort', 'relevance');
        $order = $request->get('order', 'desc');

        $user = Auth::user();
        
        // Build search query
        $query = Course::with(['creator', 'teachers', 'lessons', 'tasks'])
            ->withCount(['lessons', 'tasks']);

        // Students can only see published courses
        if ($user->hasRole('student')) {
            $query->where('is_published', true);
        }

        // Apply search filters
        if ($request->filled('q')) {
            $searchTerm = $request->get('q');
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('tags', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->filled('instructor')) {
            $instructorName = $request->get('instructor');
            $query->where(function (Builder $q) use ($instructorName) {
                $q->whereHas('creator', function (Builder $creatorQuery) use ($instructorName) {
                    $creatorQuery->where('name', 'LIKE', "%{$instructorName}%");
                })->orWhereHas('teachers', function (Builder $teacherQuery) use ($instructorName) {
                    $teacherQuery->where('name', 'LIKE', "%{$instructorName}%");
                });
            });
        }

        if ($request->filled('tags')) {
            $tags = $request->get('tags');
            $query->where('tags', 'LIKE', "%{$tags}%");
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }

        if ($request->filled('min_time')) {
            $query->where('estimated_time', '>=', $request->get('min_time'));
        }

        if ($request->filled('max_time')) {
            $query->where('estimated_time', '<=', $request->get('max_time'));
        }

        // Apply sorting (relevance sorting is handled differently)
        if ($sort === 'relevance' && $request->filled('q')) {
            // Simple relevance scoring based on title match priority
            $searchTerm = $request->get('q');
            $query->selectRaw('courses.*, 
                CASE 
                    WHEN title LIKE ? THEN 3
                    WHEN description LIKE ? THEN 2
                    WHEN tags LIKE ? THEN 1
                    ELSE 0
                END as relevance_score', [
                "%{$searchTerm}%",
                "%{$searchTerm}%", 
                "%{$searchTerm}%"
            ])->orderBy('relevance_score', 'desc');
        } else {
            $query->orderBy($sort === 'relevance' ? 'created_at' : $sort, $order);
        }

        $courses = $query->paginate($perPage);

        // Transform the data
        $transformedCourses = $courses->getCollection()->map(function ($course) use ($user, $request) {
            $enrollment = null;
            if ($user->hasRole('student')) {
                $enrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
            }

            // Get primary instructor
            $instructor = $course->creator;
            if ($course->teachers->isNotEmpty()) {
                $instructor = $course->teachers->first();
            }

            $courseData = [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'banner_url' => $course->banner_url,
                'tags' => $course->tags,
                'estimated_time' => $course->estimated_time,
                'is_published' => $course->is_published,
                'created_at' => $course->created_at,
                'instructor' => $instructor ? [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'email' => $instructor->email,
                ] : null,
                'enrollment_status' => $enrollment ? $enrollment->enrollment_status : 'not_enrolled',
                'total_lessons' => $course->lessons_count,
                'total_tasks' => $course->tasks_count,
            ];

            // Add relevance score if available
            if (isset($course->relevance_score)) {
                $courseData['relevance_score'] = $course->relevance_score / 3; // Normalize to 0-1
            }

            return $courseData;
        });

        $pagination = [
            'current_page' => $courses->currentPage(),
            'per_page' => $courses->perPage(),
            'total' => $courses->total(),
            'last_page' => $courses->lastPage(),
            'from' => $courses->firstItem(),
            'to' => $courses->lastItem(),
            'has_more_pages' => $courses->hasMorePages(),
        ];

        return $this->paginatedResponse(
            $transformedCourses,
            $pagination,
            'Search completed successfully'
        );
    }

    /**
     * Get course details
     *
     * Retrieve full details for a single course, including lessons, sections, tasks and statistics.
     * Students cannot access unpublished courses.
     *
     * @authenticated
     * @headerParam Authorization string required Bearer token returned from login.
     * @headerParam APP_TOKEN string required Enhanced app token returned from login.
     * @urlParam courseId int required The ID of the course to retrieve. Example: 1
     * @response 200 scenario="Course found" {
     *   "code": 200,
     *   "message": "Course details retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "title": "Intro to Programming",
     *     "slug": "intro-to-programming",
     *     "description": "Learn the basics of programming.",
     *     "banner_url": "https://example.com/banners/intro-to-programming.png",
     *     "tags": ["programming", "beginner"],
     *     "estimated_time": 3600,
     *     "is_published": true,
     *     "created_at": "2024-01-01T12:00:00Z",
     *     "updated_at": "2024-01-02T12:00:00Z",
     *     "instructor": {
     *       "id": 10,
     *       "name": "Jane Doe",
     *       "email": "jane@example.com"
     *     },
     *     "teachers": [],
     *     "enrollment_status": "not_enrolled",
     *     "enrollment_date": null,
     *     "progress_percentage": 0,
     *     "lessons": [],
     *     "lesson_sections": [],
     *     "tasks": [],
     *     "learning_paths": [],
     *     "statistics": {
     *       "total_lessons": 10,
     *       "completed_lessons": 0,
     *       "total_tasks": 5,
     *       "completed_tasks": 0,
     *       "total_enrolled_students": 0,
     *       "average_completion_rate": 0
     *     }
     *   },
     *   "pagination": {}
     * }
     * @response 403 scenario="Unpublished course for student" {
     *   "code": 403,
     *   "message": "Access denied. This course is not published.",
     *   "data": [],
     *   "pagination": {}
     * }
     * @response 404 scenario="Course not found" {
     *   "code": 404,
     *   "message": "Course not found",
     *   "data": [],
     *   "pagination": {}
     * }
     */
    public function show(Request $request, $courseId): JsonResponse
    {
        $user = Auth::user();
        
        $course = Course::with([
            'creator',
            'teachers',
            'lessons' => function ($query) {
                $query->orderBy('order_index');
            },
            'lessonSections' => function ($query) {
                $query->with(['lessons' => function ($lessonQuery) {
                    $lessonQuery->orderBy('order_index');
                }])->orderBy('order_index');
            },
            'tasks',
            'learningPaths',
            'enrollments'
        ])->find($courseId);

        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }

        // Check if student can access unpublished course
        if ($user->hasRole('student') && !$course->is_published) {
            return $this->errorResponse('Access denied. This course is not published.', 403);
        }

        // Get enrollment information for students
        $enrollment = null;
        $progressPercentage = 0;
        if ($user->hasRole('student')) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            
            if ($enrollment) {
                // Calculate progress percentage based on completed lessons
                $totalLessons = $course->lessons->count();
                if ($totalLessons > 0) {
                    $completedLessons = $course->progressLogs()
                        ->where('user_id', $user->id)
                        ->where('lesson_id', '!=', null)
                        ->where('action', 'completed')
                        ->distinct('lesson_id')
                        ->count();
                    $progressPercentage = ($completedLessons / $totalLessons) * 100;
                }
            }
        }

        // Get primary instructor
        $instructor = $course->creator;
        if ($course->teachers->isNotEmpty()) {
            $instructor = $course->teachers->first();
        }

        // Get user progress for lessons
        $userProgress = [];
        if ($user->hasRole('student')) {
            $progressLogs = $course->progressLogs()
                ->where('user_id', $user->id)
                ->whereNotNull('lesson_id')
                ->get()
                ->keyBy('lesson_id');
            $userProgress = $progressLogs;
        }

        // Transform lessons with completion status
        $transformedLessons = $course->lessons->map(function ($lesson) use ($userProgress) {
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'order_index' => $lesson->order_index,
                'estimated_time' => $lesson->estimated_time,
                'is_completed' => isset($userProgress[$lesson->id]) && $userProgress[$lesson->id]->action === 'completed',
                'completed_at' => isset($userProgress[$lesson->id]) && $userProgress[$lesson->id]->action === 'completed' 
                    ? $userProgress[$lesson->id]->completed_at 
                    : null,
            ];
        });

        // Transform lesson sections with completion status
        $transformedSections = $course->lessonSections->map(function ($section) use ($userProgress) {
            $sectionLessons = $section->lessons->map(function ($lesson) use ($userProgress) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'order_index' => $lesson->order_index,
                    'estimated_time' => $lesson->estimated_time,
                    'is_completed' => isset($userProgress[$lesson->id]) && $userProgress[$lesson->id]->action === 'completed',
                    'completed_at' => isset($userProgress[$lesson->id]) && $userProgress[$lesson->id]->action === 'completed' 
                        ? $userProgress[$lesson->id]->completed_at 
                        : null,
                ];
            });

            return [
                'id' => $section->id,
                'title' => $section->title,
                'order_index' => $section->order_index,
                'lessons' => $sectionLessons,
            ];
        });

        // Transform tasks with completion status
        $transformedTasks = $course->tasks->map(function ($task) use ($user) {
            $isCompleted = false;
            $completedAt = null;
            
            if ($user->hasRole('student')) {
                // Check if user has submitted this task
                $submission = $task->submissions()->where('student_id', $user->id)->first();
                if ($submission) {
                    $isCompleted = true;
                    $completedAt = $submission->submitted_at;
                }
            }

            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'task_type' => $task->task_type,
                'is_completed' => $isCompleted,
                'completed_at' => $completedAt,
                'due_date' => $task->due_date,
            ];
        });

        // Calculate statistics
        $totalEnrolledStudents = $course->enrollments()->count();
        $completedLessonsCount = $course->progressLogs()
            ->where('action', 'completed')
            ->distinct('user_id')
            ->count();
        $averageCompletionRate = $totalEnrolledStudents > 0 
            ? ($completedLessonsCount / $totalEnrolledStudents) * 100 
            : 0;

        $courseData = [
            'id' => $course->id,
            'title' => $course->title,
            'slug' => $course->slug,
            'description' => $course->description,
            'banner_url' => $course->banner_url,
            'tags' => $course->tags,
            'estimated_time' => $course->estimated_time,
            'is_published' => $course->is_published,
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
            'instructor' => $instructor ? [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'email' => $instructor->email,
            ] : null,
            'teachers' => $course->teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'assigned_at' => $teacher->pivot->assigned_at,
                ];
            }),
            'enrollment_status' => $enrollment ? $enrollment->enrollment_status : 'not_enrolled',
            'enrollment_date' => $enrollment ? $enrollment->created_at : null,
            'progress_percentage' => round($progressPercentage, 1),
            'lessons' => $transformedLessons,
            'lesson_sections' => $transformedSections,
            'tasks' => $transformedTasks,
            'learning_paths' => $course->learningPaths->map(function ($path) {
                return [
                    'id' => $path->id,
                    'title' => $path->title,
                    'order_index' => $path->pivot->order_index,
                ];
            }),
            'statistics' => [
                'total_lessons' => $course->lessons->count(),
                'completed_lessons' => $user->hasRole('student') ? 
                    $course->progressLogs()
                        ->where('user_id', $user->id)
                        ->where('action', 'completed')
                        ->distinct('lesson_id')
                        ->count() : 0,
                'total_tasks' => $course->tasks->count(),
                'completed_tasks' => $user->hasRole('student') ?
                    $transformedTasks->where('is_completed', true)->count() : 0,
                'total_enrolled_students' => $totalEnrolledStudents,
                'average_completion_rate' => round($averageCompletionRate, 1),
            ],
        ];

        return $this->successResponse($courseData, 'Course details retrieved successfully');
    }
}
