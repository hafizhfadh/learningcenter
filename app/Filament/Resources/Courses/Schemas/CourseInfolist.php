<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Course Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Course Title')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->columnSpan(1),
                                
                                TextEntry::make('slug')
                                    ->label('URL Slug')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->copyMessage('Slug copied!')
                                    ->columnSpan(1),
                            ]),
                        
                        ImageEntry::make('banner')
                            ->label('Course Banner')
                            ->disk('idcloudhost')
                            ->height(200)
                            ->defaultImageUrl('/images/placeholder-course.png')
                            ->columnSpanFull(),
                        
                        TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->columnSpanFull(),
                        
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tags')
                                    ->label('Tags')
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('No tags')
                                    ->columnSpan(1),
                                
                                TextEntry::make('estimated_time')
                                    ->label('Estimated Duration')
                                    ->formatStateUsing(fn (?int $state): string => $state ? $state . ' minutes' : 'Not specified')
                                    ->icon('heroicon-o-clock')
                                    ->columnSpan(1),
                                
                                IconEntry::make('is_published')
                                    ->label('Publication Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->columnSpan(1),
                            ]),
                    ]),
                
                Section::make('Course Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('lessons_count')
                                    ->label('Total Lessons')
                                    ->getStateUsing(fn ($record) => $record->lessons()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-play')
                                    ->columnSpan(1),
                                
                                TextEntry::make('lesson_sections_count')
                                    ->label('Sections')
                                    ->getStateUsing(fn ($record) => $record->lessonSections()->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-folder')
                                    ->columnSpan(1),
                                
                                TextEntry::make('enrollments_count')
                                    ->label('Enrollments')
                                    ->getStateUsing(fn ($record) => $record->enrollments()->count())
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-users')
                                    ->columnSpan(1),
                            ]),
                    ]),
                
                Section::make('Timestamps')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-plus-circle')
                                    ->columnSpan(1),
                                
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-pencil')
                                    ->columnSpan(1),
                                
                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-trash')
                                    ->placeholder('Not deleted')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
