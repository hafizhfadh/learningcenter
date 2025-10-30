<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonSection;
use App\Models\ProgressLog;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('dashboard_stats', 300, function () {
            // Single optimized query to get all counts
            $counts = DB::select("
                SELECT 
                    (SELECT COUNT(*) FROM users) as users_count,
                    (SELECT COUNT(*) FROM courses) as courses_count,
                    (SELECT COUNT(*) FROM lessons) as lessons_count,
                    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'active') as active_enrollments_count,
                    (SELECT COUNT(*) FROM learning_paths) as learning_paths_count,
                    (SELECT COUNT(*) FROM lesson_sections) as lesson_sections_count,
                    (SELECT COUNT(*) FROM tasks) as tasks_count,
                    (SELECT COUNT(*) FROM task_submissions) as task_submissions_count,
                    (SELECT COUNT(*) FROM progress_logs) as progress_logs_count,
                    (SELECT COUNT(*) FROM institutions) as institutions_count
            ")[0];

            return [
                'users' => $counts->users_count,
                'courses' => $counts->courses_count,
                'lessons' => $counts->lessons_count,
                'active_enrollments' => $counts->active_enrollments_count,
                'learning_paths' => $counts->learning_paths_count,
                'lesson_sections' => $counts->lesson_sections_count,
                'tasks' => $counts->tasks_count,
                'task_submissions' => $counts->task_submissions_count,
                'progress_logs' => $counts->progress_logs_count,
                'institutions' => $counts->institutions_count,
            ];
        });

        return [
            Stat::make('Total Users', number_format($stats['users']))
                ->description('Registered users in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Courses', number_format($stats['courses']))
                ->description('Available courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Total Lessons', number_format($stats['lessons']))
                ->description('Learning content available')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning'),

            Stat::make('Active Enrollments', number_format($stats['active_enrollments']))
                ->description('Students currently enrolled')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Learning Paths', number_format($stats['learning_paths']))
                ->description('Structured learning journeys')
                ->descriptionIcon('heroicon-m-map')
                ->color('primary'),

            Stat::make('Lesson Sections', number_format($stats['lesson_sections']))
                ->description('Course sections available')
                ->descriptionIcon('heroicon-m-folder')
                ->color('gray'),

            Stat::make('Tasks', number_format($stats['tasks']))
                ->description('Assignments and activities')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('warning'),

            Stat::make('Task Submissions', number_format($stats['task_submissions']))
                ->description('Student submissions')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Progress Logs', number_format($stats['progress_logs']))
                ->description('Learning progress tracked')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Institutions', number_format($stats['institutions']))
                ->description('Partner institutions')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}