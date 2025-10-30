<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\HtmlString;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enrollment_stats')
                ->label('View Statistics')
                ->icon('heroicon-o-chart-bar')
                ->color(Color::Blue)
                ->modalHeading('Enrollment Statistics')
                ->modalDescription('Overview of enrollment data and trends')
                ->schema([
                    Section::make('Overview')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    TextEntry::make('total_enrollments')
                                        ->label('Total Enrollments')
                                        ->state(Enrollment::count())
                                        ->color('primary')
                                        ->weight('bold'),

                                    TextEntry::make('active_enrollments')
                                        ->label('Active Enrollments')
                                        ->state(Enrollment::where('enrollment_status', 'active')->count())
                                        ->color('success')
                                        ->weight('bold'),

                                    TextEntry::make('completed_enrollments')
                                        ->label('Completed')
                                        ->state(Enrollment::where('enrollment_status', 'completed')->count())
                                        ->color('info')
                                        ->weight('bold'),

                                    TextEntry::make('dropped_enrollments')
                                        ->label('Dropped')
                                        ->state(Enrollment::where('enrollment_status', 'dropped')->count())
                                        ->color('danger')
                                        ->weight('bold'),
                                ])
                        ]),
                    
                    Section::make('Progress Distribution')
                        ->schema([
                            Placeholder::make('progress_chart')
                                ->label('')
                                ->content(function () {
                                    $progressRanges = [
                                        '0-25%' => Enrollment::whereBetween('progress', [0, 25])->count(),
                                        '26-50%' => Enrollment::whereBetween('progress', [26, 50])->count(),
                                        '51-75%' => Enrollment::whereBetween('progress', [51, 75])->count(),
                                        '76-100%' => Enrollment::whereBetween('progress', [76, 100])->count(),
                                    ];
                                    
                                    $total = array_sum($progressRanges);
                                    $html = '<div class="space-y-3">';
                                    
                                    foreach ($progressRanges as $range => $count) {
                                        $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                                        $width = $total > 0 ? ($count / $total) * 100 : 0;
                                        
                                        $html .= '<div class="flex items-center justify-between">';
                                        $html .= '<span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">' . $range . '</span>';
                                        $html .= '<div class="flex-1 mx-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">';
                                        $html .= '<div class="bg-primary-600 h-2 rounded-full" style="width: ' . $width . '%"></div>';
                                        $html .= '</div>';
                                        $html .= '<span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">' . $count . ' (' . $percentage . '%)</span>';
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                    return new HtmlString($html);
                                })
                        ]),
                    
                    Section::make('Recent Enrollments')
                        ->schema([
                            Placeholder::make('recent_enrollments')
                                ->label('')
                                ->content(function () {
                                    $recentEnrollments = Enrollment::with(['user', 'course'])
                                        ->latest('enrolled_at')
                                        ->limit(5)
                                        ->get();
                                    
                                    if ($recentEnrollments->isEmpty()) {
                                        return new HtmlString('<p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent enrollments found.</p>');
                                    }
                                    
                                    $html = '<div class="space-y-3">';
                                    
                                    foreach ($recentEnrollments as $enrollment) {
                                        $statusColor = match($enrollment->enrollment_status) {
                                            'active' => 'text-success-600 dark:text-success-400',
                                            'completed' => 'text-info-600 dark:text-info-400',
                                            'dropped' => 'text-danger-600 dark:text-danger-400',
                                            default => 'text-gray-600 dark:text-gray-400'
                                        };
                                        
                                        $html .= '<div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                                        $html .= '<div>';
                                        $html .= '<p class="font-medium text-gray-900 dark:text-gray-100">' . e($enrollment->user->name ?? 'Unknown User') . '</p>';
                                        $html .= '<p class="text-sm text-gray-600 dark:text-gray-400">' . e($enrollment->course->title ?? 'Unknown Course') . '</p>';
                                        $html .= '</div>';
                                        $html .= '<div class="text-right">';
                                        $html .= '<span class="text-sm font-medium ' . $statusColor . '">' . ucfirst($enrollment->enrollment_status) . '</span>';
                                        $html .= '<p class="text-xs text-gray-500 dark:text-gray-400">' . $enrollment->enrolled_at?->format('M j, Y') . '</p>';
                                        $html .= '</div>';
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div>';
                                    return new HtmlString($html);
                                })
                        ])
                ])
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            
            Action::make('bulk_progress_update')
                ->label('Bulk Progress Update')
                ->icon('heroicon-o-arrow-trending-up')
                ->color(Color::Orange)
                ->requiresConfirmation()
                ->modalHeading('Bulk Progress Update')
                ->modalDescription('Update progress for multiple enrollments at once')
                ->modalSubmitActionLabel('Update Progress')
                ->action(function () {
                    // This would typically open a form for bulk updates
                    Notification::make()
                        ->title('Feature Coming Soon')
                        ->body('Bulk progress update functionality will be available in the next release.')
                        ->info()
                        ->send();
                }),
            
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Enrollment')
                ->createAnother(false),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['user', 'course'])
            ->withCount(['user', 'course']);
    }

    public function getTitle(): string
    {
        return 'Student Enrollments';
    }

    public function getSubheading(): string
    {
        $totalEnrollments = Enrollment::count();
        $activeEnrollments = Enrollment::where('enrollment_status', 'enrolled')->count();
        $completedEnrollments = Enrollment::where('enrollment_status', 'completed')->count();
        
        return "Total: {$totalEnrollments} | Active: {$activeEnrollments} | Completed: {$completedEnrollments}";
    }
}
