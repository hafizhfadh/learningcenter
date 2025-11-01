<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\Course;
use App\Models\TaskSubmission;
use App\Models\ProgressLog;
use App\Models\Enrollment;
use BackedEnum;

class TeachingDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationLabel = 'Teaching Dashboard';
    
    protected static ?string $title = 'Teaching Dashboard';
    
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.teaching-dashboard';

    /**
     * Check if this page should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->hasRole('school_teacher');
    }

    /**
     * Check if user can access this page
     */
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('access_teaching_dashboard');
    }

    /**
     * Get the page title
     */
    public function getTitle(): string|Htmlable
    {
        $user = Auth::user();
        return "Welcome back, {$user->name}!";
    }

    /**
     * Get the page heading
     */
    public function getHeading(): string|Htmlable
    {
        return 'Teaching Dashboard';
    }

    /**
     * Get the page subheading
     */
    public function getSubheading(): ?string
    {
        $user = Auth::user();
        $institution = $user->institution;
        
        if ($institution) {
            return "Managing courses at {$institution->name}";
        }
        
        return 'Manage your courses and track student progress';
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            // Header actions removed temporarily until proper routes are available
            // You can add them back when the corresponding resources are created
        ];
    }

    /**
     * Get view data for the page
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        // Get courses (simplified for current model structure - all courses for now)
        $courses = Course::withCount(['enrollments', 'lessons'])
            ->latest()
            ->take(5)
            ->get();

        // Get pending submissions (simplified for current model structure)
        $pendingSubmissions = TaskSubmission::with(['student', 'task'])
            ->latest()
            ->take(10)
            ->get();

        // Get recent progress logs (simplified for current model structure)
        $recentProgress = ProgressLog::with(['user', 'enrollment.course'])
            ->latest()
            ->take(10)
            ->get();

        // Get statistics (simplified for current model structure)
        $stats = [
            'total_courses' => $courses->count(),
            'total_students' => Enrollment::distinct('user_id')->count(),
            'pending_submissions' => $pendingSubmissions->count(),
            'active_enrollments' => Enrollment::count(),
        ];

        return [
            'courses' => $courses,
            'pendingSubmissions' => $pendingSubmissions,
            'recentProgress' => $recentProgress,
            'stats' => $stats,
            'user' => $user,
        ];
    }
}