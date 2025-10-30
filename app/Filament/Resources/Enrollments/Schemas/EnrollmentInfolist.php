<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student & Course Information')
                    ->description('Basic enrollment details')
                    ->icon(Heroicon::OutlinedUser)
                    ->iconPosition(IconPosition::Before)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('user.name')
                                        ->label('Student Name')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg')
                                        ->copyable()
                                        ->copyMessage('Student name copied')
                                        ->copyMessageDuration(1500),
                                    
                                    TextEntry::make('user.email')
                                        ->label('Student Email')
                                        ->icon(Heroicon::OutlinedEnvelope)
                                        ->copyable()
                                        ->copyMessage('Email copied')
                                        ->copyMessageDuration(1500),
                                ])->columnSpan(1),
                                
                                Group::make([
                                    TextEntry::make('course.title')
                                        ->label('Course Title')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg')
                                        ->copyable()
                                        ->copyMessage('Course title copied')
                                        ->copyMessageDuration(1500),
                                    
                                    TextEntry::make('course.description')
                                        ->label('Course Description')
                                        ->limit(100)
                                        ->tooltip(function (TextEntry $component): ?string {
                                            $state = $component->getState();
                                            if (strlen($state) <= 100) {
                                                return null;
                                            }
                                            return $state;
                                        }),
                                ])->columnSpan(1),
                            ]),
                    ]),
                
                Section::make('Enrollment Status & Progress')
                    ->description('Current enrollment status and learning progress')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->iconPosition(IconPosition::Before)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('enrollment_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'completed' => 'success',
                                        'enrolled' => 'warning',
                                        'dropped' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn ($state) => match ($state) {
                                        'completed' => 'heroicon-o-check-circle',
                                        'enrolled' => 'heroicon-o-clock',
                                        'dropped' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->size('lg')
                                    ->weight(FontWeight::Bold),
                                
                                TextEntry::make('progress')
                                    ->label('Progress')
                                    ->numeric()
                                    ->suffix('%')
                                    ->color(fn ($state) => match (true) {
                                        $state >= 100 => 'success',
                                        $state >= 75 => 'info',
                                        $state >= 50 => 'warning',
                                        default => 'danger',
                                    })
                                    ->weight(fn ($state) => $state >= 100 ? FontWeight::Bold : FontWeight::Medium)
                                    ->size('lg')
                                    ->icon(fn ($state) => match (true) {
                                        $state >= 100 => 'heroicon-o-check-circle',
                                        $state >= 75 => 'heroicon-o-arrow-trending-up',
                                        $state >= 50 => 'heroicon-o-arrow-right',
                                        default => 'heroicon-o-arrow-trending-down',
                                    }),
                                
                                IconEntry::make('enrollment_status')
                                    ->label('Completion Status')
                                    ->icon(fn ($state) => match ($state) {
                                        'completed' => 'heroicon-o-academic-cap',
                                        'enrolled' => 'heroicon-o-book-open',
                                        'dropped' => 'heroicon-o-x-mark',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        'completed' => 'success',
                                        'enrolled' => 'warning',
                                        'dropped' => 'danger',
                                        default => 'gray',
                                    })
                                    ->size('xl'),
                            ]),
                    ]),
                
                Section::make('Timeline Information')
                    ->description('Important dates and timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->iconPosition(IconPosition::Before)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('enrolled_at')
                                    ->label('Enrollment Date')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->since()
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->weight(FontWeight::Medium),
                                
                                TextEntry::make('created_at')
                                    ->label('Record Created')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->since()
                                    ->icon(Heroicon::OutlinedPlus)
                                    ->color('gray'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->since()
                                    ->icon(Heroicon::OutlinedPencil)
                                    ->color('gray'),
                                
                                TextEntry::make('deleted_at')
                                    ->label('Deleted At')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->since()
                                    ->icon(Heroicon::OutlinedTrash)
                                    ->color('danger')
                                    ->visible(fn ($record) => $record?->deleted_at !== null),
                            ]),
                    ]),
            ]);
    }
}
