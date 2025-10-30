<?php

namespace App\Filament\Resources\Enrollments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Student name copied')
                    ->copyMessageDuration(1500),
                
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
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),
                
                BadgeColumn::make('enrollment_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'enrolled',
                        'danger' => 'dropped',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-clock' => 'enrolled',
                        'heroicon-o-x-circle' => 'dropped',
                    ])
                    ->sortable(),
                
                TextColumn::make('progress')
                    ->label('Progress')
                    ->numeric()
                    ->sortable()
                    ->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 75 => 'info',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->weight(fn ($state) => $state >= 100 ? 'bold' : 'normal'),
                
                TextColumn::make('enrolled_at')
                    ->label('Enrolled')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \\a\\t g:i A')),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('enrollment_status')
                    ->label('Status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                    ])
                    ->multiple(),
                
                SelectFilter::make('progress_range')
                    ->label('Progress Range')
                    ->options([
                        '0-25' => '0-25%',
                        '26-50' => '26-50%',
                        '51-75' => '51-75%',
                        '76-99' => '76-99%',
                        '100' => '100% (Completed)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $value): Builder {
                                return match ($value) {
                                    '0-25' => $query->whereBetween('progress', [0, 25]),
                                    '26-50' => $query->whereBetween('progress', [26, 50]),
                                    '51-75' => $query->whereBetween('progress', [51, 75]),
                                    '76-99' => $query->whereBetween('progress', [76, 99]),
                                    '100' => $query->where('progress', 100),
                                    default => $query,
                                };
                            }
                        );
                    }),
                
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),
                
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
