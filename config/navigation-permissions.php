<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines which navigation items are accessible to
    | different user roles. Each role can have specific permissions for
    | viewing, creating, editing, and deleting resources.
    |
    */

    'roles' => [
        'super_admin' => [
            'permissions' => ['*'], // Full access to everything
            'navigation_groups' => ['*'],
            'resources' => ['*'],
            'pages' => ['*'],
            'widgets' => ['*'],
        ],

        'admin' => [
            'permissions' => [
                'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User',
                'ViewAny:Course', 'View:Course', 'Create:Course', 'Update:Course', 'Delete:Course',
                'ViewAny:LearningPath', 'View:LearningPath', 'Create:LearningPath', 'Update:LearningPath', 'Delete:LearningPath',
                'ViewAny:Lesson', 'View:Lesson', 'Create:Lesson', 'Update:Lesson', 'Delete:Lesson',
                'ViewAny:LessonSection', 'View:LessonSection', 'Create:LessonSection', 'Update:LessonSection', 'Delete:LessonSection',
                'ViewAny:Enrollment', 'View:Enrollment', 'Create:Enrollment', 'Update:Enrollment', 'Delete:Enrollment',
                'ViewAny:Institution', 'View:Institution', 'Create:Institution', 'Update:Institution', 'Delete:Institution',
                'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role',
            ],
            'navigation_groups' => [
                'User Management',
                'Learning Management',
                'System Administration',
            ],
            'resources' => [
                'App\Filament\Resources\Users\UserResource',
                'App\Filament\Resources\Courses\CourseResource',
                'App\Filament\Resources\LearningPaths\LearningPathResource',
                'App\Filament\Resources\Lessons\LessonResource',
                'App\Filament\Resources\LessonSections\LessonSectionResource',
                'App\Filament\Resources\Enrollments\EnrollmentResource',
                'App\Filament\Resources\Institutions\InstitutionResource',
                'BezhanSalleh\FilamentShield\Resources\RoleResource',
            ],
            'pages' => [
                'Filament\Pages\Dashboard',
            ],
            'widgets' => [
                'App\Filament\Widgets\SystemStatsOverview',
                'App\Filament\Widgets\RecentCourses',
                'App\Filament\Widgets\RecentLessons',
                'App\Filament\Widgets\RecentUsers',
                'App\Filament\Widgets\RecentEnrollments',
            ],
        ],

        'teacher' => [
            'permissions' => [
                'ViewAny:Course', 'View:Course', 'Create:Course', 'Update:Course',
                'ViewAny:LearningPath', 'View:LearningPath', 'Create:LearningPath', 'Update:LearningPath',
                'ViewAny:Lesson', 'View:Lesson', 'Create:Lesson', 'Update:Lesson',
                'ViewAny:LessonSection', 'View:LessonSection', 'Create:LessonSection', 'Update:LessonSection',
                'ViewAny:Enrollment', 'View:Enrollment',
                'ViewAny:User', 'View:User', // Limited user access for students only
            ],
            'navigation_groups' => [
                'Learning Management',
                'Student Management',
            ],
            'resources' => [
                'App\Filament\Resources\Courses\CourseResource',
                'App\Filament\Resources\LearningPaths\LearningPathResource',
                'App\Filament\Resources\Lessons\LessonResource',
                'App\Filament\Resources\LessonSections\LessonSectionResource',
                'App\Filament\Resources\Enrollments\EnrollmentResource',
            ],
            'pages' => [
                'Filament\Pages\Dashboard',
            ],
            'widgets' => [
                'App\Filament\Widgets\RecentCourses',
                'App\Filament\Widgets\RecentLessons',
                'App\Filament\Widgets\RecentEnrollments',
            ],
            'restrictions' => [
                // Teachers can only see/edit their own courses and students enrolled in their courses
                'scope_filters' => [
                    'App\Filament\Resources\Courses\CourseResource' => 'teacher_courses_only',
                    'App\Filament\Resources\Users\UserResource' => 'enrolled_students_only',
                ],
            ],
        ],

        'student' => [
            'permissions' => [
                'ViewAny:Course', 'View:Course',
                'ViewAny:LearningPath', 'View:LearningPath',
                'ViewAny:Lesson', 'View:Lesson',
                'ViewAny:LessonSection', 'View:LessonSection',
                'ViewAny:Enrollment', 'View:Enrollment',
            ],
            'navigation_groups' => [
                'My Learning',
            ],
            'resources' => [
                'App\Filament\Resources\Courses\CourseResource',
                'App\Filament\Resources\LearningPaths\LearningPathResource',
                'App\Filament\Resources\Lessons\LessonResource',
                'App\Filament\Resources\LessonSections\LessonSectionResource',
                'App\Filament\Resources\Enrollments\EnrollmentResource',
            ],
            'pages' => [
                'Filament\Pages\Dashboard',
            ],
            'widgets' => [
                'App\Filament\Widgets\RecentCourses',
                'App\Filament\Widgets\RecentLessons',
            ],
            'restrictions' => [
                // Students can only see courses they're enrolled in
                'scope_filters' => [
                    'App\Filament\Resources\Courses\CourseResource' => 'enrolled_courses_only',
                    'App\Filament\Resources\Enrollments\EnrollmentResource' => 'own_enrollments_only',
                ],
                'disabled_actions' => [
                    'create', 'edit', 'delete', 'bulk_delete', 'force_delete',
                ],
            ],
        ],

        'panel_user' => [
            'permissions' => [
                'ViewAny:Course', 'View:Course',
                'ViewAny:LearningPath', 'View:LearningPath',
            ],
            'navigation_groups' => [
                'Browse',
            ],
            'resources' => [
                'App\Filament\Resources\Courses\CourseResource',
                'App\Filament\Resources\LearningPaths\LearningPathResource',
            ],
            'pages' => [
                'Filament\Pages\Dashboard',
            ],
            'widgets' => [],
            'restrictions' => [
                'disabled_actions' => [
                    'create', 'edit', 'delete', 'bulk_delete', 'force_delete', 'view',
                ],
                'read_only' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Groups Configuration
    |--------------------------------------------------------------------------
    |
    | Define the navigation groups and their associated icons, sort orders,
    | and visibility rules.
    |
    */

    'navigation_groups' => [
        'User Management' => [
            'icon' => 'heroicon-o-users',
            'sort' => 10,
            'collapsible' => true,
        ],
        'Learning Management' => [
            'icon' => 'heroicon-o-academic-cap',
            'sort' => 20,
            'collapsible' => true,
        ],
        'Student Management' => [
            'icon' => 'heroicon-o-user-group',
            'sort' => 25,
            'collapsible' => true,
        ],
        'My Learning' => [
            'icon' => 'heroicon-o-book-open',
            'sort' => 30,
            'collapsible' => false,
        ],
        'System Administration' => [
            'icon' => 'heroicon-o-cog-6-tooth',
            'sort' => 90,
            'collapsible' => true,
        ],
        'Browse' => [
            'icon' => 'heroicon-o-magnifying-glass',
            'sort' => 40,
            'collapsible' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Indicators Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how restricted or disabled items should be visually indicated
    | in the navigation sidebar.
    |
    */

    'visual_indicators' => [
        'disabled_opacity' => 0.5,
        'restricted_badge' => [
            'enabled' => true,
            'text' => 'Restricted',
            'color' => 'warning',
        ],
        'tooltip_messages' => [
            'no_permission' => 'You do not have permission to access this resource.',
            'restricted_access' => 'Your access to this resource is limited.',
            'read_only' => 'You have read-only access to this resource.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for navigation permissions to improve performance.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key_prefix' => 'navigation_permissions',
    ],
];