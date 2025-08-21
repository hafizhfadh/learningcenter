<?php

namespace App\Filament\Widgets;

use App\Models\Enrollment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentEnrollments extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        $enrollments = Cache::remember('recent_enrollments_widget', 300, function () {
            return Enrollment::query()
                ->with([
                    'user:id,name,email',
                    'course:id,title'
                ])
                ->select(['id', 'user_id', 'course_id', 'enrollment_status', 'progress', 'enrolled_at', 'created_at'])
                ->latest('enrolled_at')
                ->limit(5)
                ->get();
        });

        return $table
            ->query(
                Enrollment::query()
                    ->whereIn('id', $enrollments->pluck('id'))
                    ->with([
                        'user:id,name,email',
                        'course:id,title'
                    ])
                    ->select(['id', 'user_id', 'course_id', 'enrollment_status', 'progress', 'enrolled_at', 'created_at'])
                    ->latest('enrolled_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('enrollment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        $state >= 25 => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('enrolled_at')
                    ->label('Enrolled')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn (Enrollment $record): string => $record->enrolled_at->format('M j, Y g:i A')),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->heading('Recent Enrollments')
            ->description('Latest 5 course enrollments in the system')
            ->emptyStateHeading('No enrollments found')
            ->emptyStateDescription('No students have enrolled in courses yet.')
            ->emptyStateIcon('heroicon-o-user-plus')
            ->defaultSort('enrolled_at', 'desc');
    }
}