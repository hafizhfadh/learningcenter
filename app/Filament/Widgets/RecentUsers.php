<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentUsers extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email address copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'instructor' => 'warning',
                        'student' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->counts('enrollments')
                    ->sortable(),

                TextColumn::make('progress_logs_count')
                    ->label('Progress Logs')
                    ->counts('progressLogs')
                    ->sortable(),

                TextColumn::make('task_submissions_count')
                    ->label('Submissions')
                    ->counts('taskSubmissions')
                    ->sortable(),

                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state): string => $state ? 'Verified' : 'Unverified')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->heading('Recent Users')
            ->description('Latest 5 users registered in the system')
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('No users have registered yet.')
            ->emptyStateIcon('heroicon-o-users')
            ->defaultSort('created_at', 'desc');
    }
}