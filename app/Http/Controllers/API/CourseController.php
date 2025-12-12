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

class CourseController extends Controller
{
    use ApiResponse;

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
