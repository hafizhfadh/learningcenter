<?php

namespace App\Filament\Resources\LessonSections\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Models\Lesson;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('order_index')
                    ->badge()
                    ->label('Order')
                    ->sortable()
                    ->color('primary'),

                TextColumn::make('title')
                    ->label('Lesson Title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray')
                    ->fontFamily('mono'),

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
            ->recordActions([
                EditAction::make()
                    ->label('Edit Lesson')
                    ->url(fn (Lesson $record): string => LessonResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
                DeleteAction::make(),
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