<?php

namespace App\Filament\Resources\LearningPaths\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LearningPathsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Learning Path')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->size('sm')
                    ->description(fn ($record) => "/{$record->slug}")
                    ->wrap()
                    ->tooltip(fn ($record) => $record->name),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        
                        return strip_tags($state);
                    })
                    ->html()
                    ->wrap()
                    ->toggleable()
                    ->searchable(),
                
                TextColumn::make('courses_count')
                    ->badge()
                    ->counts('courses')
                    ->label('Courses')
                    ->colors([
                        'danger' => 0,
                        'warning' => fn ($state) => $state > 0 && $state <= 3,
                        'success' => fn ($state) => $state > 3 && $state <= 10,
                        'primary' => fn ($state) => $state > 10,
                    ])
                    ->icons([
                        'heroicon-o-academic-cap',
                    ])
                    ->sortable()
                    ->tooltip(fn ($record) => "Total courses: {$record->courses_count}"),
                
                TextColumn::make('slug')
                    ->label('URL Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('Slug copied to clipboard!')
                    ->copyMessageDuration(2000)
                    ->prefix('/')
                    ->fontFamily('mono')
                    ->size('xs'),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->created_at->format('F j, Y \\a\\t g:i A')),
                
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->updated_at->format('F j, Y \\a\\t g:i A')),
                
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('danger'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Archived Learning Paths')
                    ->placeholder('All Learning Paths')
                    ->trueLabel('Only Archived')
                    ->falseLabel('Without Archived'),
                
                SelectFilter::make('courses_count')
                    ->label('Course Count')
                    ->options([
                        '0' => 'No Courses',
                        '1-3' => '1-3 Courses',
                        '4-10' => '4-10 Courses',
                        '11+' => '11+ Courses',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $value): Builder {
                                return match ($value) {
                                    '0' => $query->withCount('courses')->having('courses_count', '=', 0),
                                    '1-3' => $query->withCount('courses')->having('courses_count', '>=', 1)->having('courses_count', '<=', 3),
                                    '4-10' => $query->withCount('courses')->having('courses_count', '>=', 4)->having('courses_count', '<=', 10),
                                    '11+' => $query->withCount('courses')->having('courses_count', '>', 10),
                                    default => $query,
                                };
                            }
                        );
                    }),
                
                Filter::make('recent')
                    ->label('Recently Created')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),
                
                Filter::make('has_banner')
                    ->label('Has Banner Image')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('banner'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View Learning Path'),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Learning Path'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these learning paths? This action cannot be undone.'),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to permanently delete these learning paths? This action cannot be undone.'),
                    RestoreBulkAction::make(),
                ])
                    ->label('Bulk Actions')
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->searchPlaceholder('Search learning paths...')
            ->emptyStateHeading('No Learning Paths Found')
            ->emptyStateDescription('Create your first learning path to get started with organizing courses.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
