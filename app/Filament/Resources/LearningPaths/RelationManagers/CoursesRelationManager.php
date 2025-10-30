<?php

namespace App\Filament\Resources\LearningPaths\RelationManagers;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('banner')
                    ->label('Banner')
                    ->disk('public')
                    ->height(50)
                    ->width(80)
                    ->defaultImageUrl('/images/placeholder-course.png')
                    ->extraAttributes(['class' => 'rounded-lg'])
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Course Title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->toggleable(),

                TextColumn::make('order_index')
                    ->label('Order')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),

                TextColumn::make('estimated_time')
                    ->label('Duration')
                    ->suffix(' hours')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Published',
                        '0' => 'Draft',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('View Course')
                    ->url(fn (Course $record): string => route('filament.admin.resources.courses.edit', ['record' => $record])),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('order_index', 'asc')
            ->reorderable('order_index')
            ->striped();
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}