<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\ProgressLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

class ProgressLogRelationManager extends RelationManager
{
    protected static string $relationship = 'progressLogs';
    protected static ?string $recordTitleAttribute = 'id';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable(),
                TextColumn::make('lesson.title')
                    ->label('Lesson')
                    ->searchable(),
                BadgeColumn::make('action')
                    ->colors([
                        'viewed' => 'gray',
                        'started' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                    ])
                    ->label('Action'),
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'lesson']);
    }
}