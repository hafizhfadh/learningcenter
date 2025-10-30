<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class LessonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lesson Overview')
                    ->description('Basic lesson information and metadata')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Group::make([
                                    ImageEntry::make('lesson_banner')
                                        ->label('Banner Image')
                                        ->disk('idcloudhost')
                                        ->height(200)
                                        ->width('100%')
                                        ->defaultImageUrl('/images/default-lesson.png'),
                                ])
                                ->columnSpan(1),
                                
                                Group::make([
                                    TextEntry::make('title')
                                        ->label('Lesson Title')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),
                                    
                                    TextEntry::make('slug')
                                        ->label('URL Slug')
                                        ->copyable()
                                        ->copyMessage('Slug copied!')
                                        ->copyMessageDuration(1500),
                                    
                                    TextEntry::make('lesson_type')
                                        ->label('Lesson Type')
                                        ->badge()
                                        ->icon(fn (string $state): string => match ($state) {
                                            'video' => 'heroicon-o-play-circle',
                                            'pages' => 'heroicon-o-document-text',
                                            'quiz' => 'heroicon-o-question-mark-circle',
                                            default => 'heroicon-o-document',
                                        })
                                        ->color(fn (string $state): string => match ($state) {
                                            'video' => 'success',
                                            'pages' => 'info',
                                            'quiz' => 'warning',
                                            default => 'gray',
                                        }),
                                    
                                    TextEntry::make('order_index')
                                        ->label('Order Index')
                                        ->badge()
                                        ->color('gray'),
                                ])
                                ->columnSpan(2),
                            ]),
                    ]),
                
                Section::make('Course & Section Information')
                    ->description('Related course and section details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('course.title')
                                    ->label('Course')
                                    ->weight(FontWeight::SemiBold)
                                    ->icon('heroicon-o-academic-cap'),
                                
                                TextEntry::make('lessonSection.title')
                                    ->label('Section')
                                    ->weight(FontWeight::SemiBold)
                                    ->icon('heroicon-o-folder'),
                            ]),
                    ])
                    ->collapsible(),
                
                Section::make('Media & Content')
                    ->description('Video and content information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('lesson_video')
                                    ->label('Has Video Content')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-video-camera')
                                    ->falseIcon('heroicon-o-video-camera-slash')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                                
                                TextEntry::make('lesson_video')
                                    ->label('Video URL')
                                    ->url(fn ($record) => $record->video_url ?? null)
                                    ->openUrlInNewTab()
                                    ->placeholder('No video uploaded')
                                    ->visible(fn ($record) => !empty($record->lesson_video)),
                            ]),
                        
                        TextEntry::make('content_body')
                            ->label('Content Body')
                            ->html()
                            ->limit(200)
                            ->placeholder('No content available')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Section::make('Timestamps')
                    ->description('Creation and modification dates')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-plus-circle')
                                    ->color('success'),
                                
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-pencil-square')
                                    ->color('warning'),
                                
                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->placeholder('Not deleted')
                                    ->visible(fn ($record) => $record->deleted_at !== null),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
