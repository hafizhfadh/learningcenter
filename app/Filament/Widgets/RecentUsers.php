<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentUsers extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        $users = Cache::remember('recent_users_widget', 300, function () {
            return User::query()
                ->withCount(['enrollments', 'progressLogs', 'taskSubmissions'])
                ->latest()
                ->limit(5)
                ->get();
        });

        return $table
            ->query(
                User::query()
                    ->whereIn('id', $users->pluck('id'))
                    ->withCount(['enrollments', 'progressLogs', 'taskSubmissions'])
                    ->latest()
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
                    ->sortable(),

                TextColumn::make('progress_logs_count')
                    ->label('Progress Logs')
                    ->sortable(),

                TextColumn::make('task_submissions_count')
                    ->label('Submissions')
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