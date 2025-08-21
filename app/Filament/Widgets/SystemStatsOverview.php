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

class SystemStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Courses', Course::count())
                ->description('Available courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Total Lessons', Lesson::count())
                ->description('Learning content')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),

            Stat::make('Active Enrollments', Enrollment::where('enrollment_status', 'active')->count())
                ->description('Students enrolled')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Learning Paths', LearningPath::count())
                ->description('Structured paths')
                ->descriptionIcon('heroicon-m-map')
                ->color('secondary'),

            Stat::make('Lesson Sections', LessonSection::count())
                ->description('Course sections')
                ->descriptionIcon('heroicon-m-folder')
                ->color('gray'),

            Stat::make('Tasks', Task::count())
                ->description('Assignments & quizzes')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('purple'),

            Stat::make('Task Submissions', TaskSubmission::count())
                ->description('Student submissions')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('emerald'),

            Stat::make('Progress Logs', ProgressLog::count())
                ->description('Learning progress')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('blue'),

            Stat::make('Institutions', Institution::count())
                ->description('Partner institutions')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('indigo'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}