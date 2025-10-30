<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentLessons extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        $lessons = Cache::remember('recent_lessons_widget', 300, function () {
            return Lesson::query()
                ->with(['course:id,title', 'lessonSection:id,title'])
                ->withCount(['tasks', 'progressLogs'])
                ->latest()
                ->limit(5)
                ->get();
        });

        return $table
            ->query(
                Lesson::query()
                    ->whereIn('id', $lessons->pluck('id'))
                    ->with(['course:id,title', 'lessonSection:id,title'])
                    ->withCount(['tasks', 'progressLogs'])
                    ->latest()
            )
            ->columns([
                ImageColumn::make('lesson_banner')
                    ->label('Banner')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/lesson-placeholder.png'),

                TextColumn::make('title')
                    ->label('Lesson Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('view', ['record' => $record])),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('lessonSection.title')
                    ->label('Section')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('lesson_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'info',
                        'text' => 'success',
                        'quiz' => 'warning',
                        'assignment' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('order_index')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->sortable(),

                TextColumn::make('progress_logs_count')
                    ->label('Progress')
                    ->sortable(),

                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Published' : 'Draft'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->heading('Recent Lessons')
            ->description('Latest 5 lessons added to the system')
            ->emptyStateHeading('No lessons found')
            ->emptyStateDescription('Create your first lesson to get started.')
            ->emptyStateIcon('heroicon-o-book-open')
            ->defaultSort('created_at', 'desc');
    }
}