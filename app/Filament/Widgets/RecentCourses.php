<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentCourses extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        $courses = Cache::remember('recent_courses_widget', 300, function () {
            return Course::query()
                ->withCount(['lessonSections', 'lessons', 'enrollments'])
                ->latest()
                ->limit(5)
                ->get();
        });

        return $table
            ->query(
                Course::query()
                    ->whereIn('id', $courses->pluck('id'))
                    ->withCount(['lessonSections', 'lessons', 'enrollments'])
                    ->latest()
            )
            ->columns([
                ImageColumn::make('banner')
                    ->label('Banner')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('/images/course-placeholder.png'),

                TextColumn::make('title')
                    ->label('Course Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Course $record): string => CourseResource::getUrl('view', ['record' => $record])),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estimated_time')
                    ->label('Duration')
                    ->suffix(' hours')
                    ->sortable(),

                TextColumn::make('lesson_sections_count')
                    ->label('Sections')
                    ->sortable(),

                TextColumn::make('lessons_count')
                    ->label('Lessons')
                    ->sortable(),

                TextColumn::make('enrollments_count')
                    ->label('Enrollments')
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
            ->heading('Recent Courses')
            ->description('Latest 5 courses added to the system')
            ->emptyStateHeading('No courses found')
            ->emptyStateDescription('Create your first course to get started.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->defaultSort('created_at', 'desc');
    }
}