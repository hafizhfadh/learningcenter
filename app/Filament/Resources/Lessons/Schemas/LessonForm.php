<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set) {
                        $set('slug', str($state)->slug());
                    }),
                TextInput::make('slug')
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('lesson_type')
                    ->required()
                    ->default('video'),
                FileUpload::make('lesson_banner')
                    ->label('Lesson Banner')
                    ->disk('idcloudhost')
                    ->directory('lessons/banners')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->maxSize(5120) // 5MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Supported formats: JPEG, PNG, WebP. Maximum size: 5MB')
                    ->required(),
                FileUpload::make('lesson_video')
                    ->label('Lesson Video')
                    ->disk('idcloudhost')
                    ->directory('lessons/videos')
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'])
                    ->maxSize(512000) // 500MB
                    ->required()
                    ->helperText('Supported formats: MP4, WebM, OGG, AVI, MOV. Maximum size: 500MB'),
                RichEditor::make('content_body')
                    ->required()
                    ->columnSpanFull(),
                Select::make('order_index')
                    ->label('Position')
                    ->required()
                    ->options(function (Get $get, ?Lesson $record) {
                        $courseId = $get('course_id');
                        $lessonSectionId = $get('lesson_section_id');
                        
                        if (!$courseId) {
                            return [];
                        }
                        
                        return Lesson::getAvailableOrderPositions(
                            $courseId, 
                            $lessonSectionId, 
                            $record?->id
                        );
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, Get $get, callable $set) {
                        // Refresh options when course or lesson section changes
                    })
                    ->default(function (Get $get) {
                        $courseId = $get('course_id');
                        $lessonSectionId = $get('lesson_section_id');
                        
                        if (!$courseId) {
                            return 1;
                        }
                        
                        return Lesson::getNextOrderIndex($courseId, $lessonSectionId);
                    }),
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, Get $get) {
                        // Clear lesson section and reset order_index when course changes
                        $set('lesson_section_id', null);
                        $courseId = $get('course_id');
                        
                        if ($courseId) {
                            $nextOrder = Lesson::getNextOrderIndex($courseId, null);
                            $set('order_index', $nextOrder);
                        }
                    }),
                Select::make('lesson_section_id')
                    ->relationship('lessonSection', 'title')
                    ->options(function (Get $get) {
                        $courseId = $get('course_id');
                        if (!$courseId) {
                            return [];
                        }
                        
                        return \App\Models\LessonSection::where('course_id', $courseId)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, Get $get) {
                        // Reset order_index when lesson section changes
                        $courseId = $get('course_id');
                        $lessonSectionId = $get('lesson_section_id');
                        
                        if ($courseId) {
                            $nextOrder = Lesson::getNextOrderIndex($courseId, $lessonSectionId);
                            $set('order_index', $nextOrder);
                        }
                    }),
            ]);
    }
}
