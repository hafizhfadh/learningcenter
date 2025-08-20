<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('lesson_tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('title')
                                    ->columnSpanFull()
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $state, callable $set) {
                                        $set('slug', str($state)->slug());
                                    })
                                    ->helperText('The lesson title will be displayed to students'),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('slug')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Auto-generated from title'),

                                        Select::make('lesson_type')
                                            ->required()
                                            ->options([
                                                'video' => 'Video Lesson',
                                                'pages' => 'Reading Material',
                                                'quiz' => 'Quiz/Assessment',
                                            ])
                                            ->default('video')
                                            ->live()
                                            ->helperText('Choose the type of lesson content')
                                            ->afterStateUpdated(function (callable $set) {
                                                // Clear video when not video type
                                                $set('lesson_video', null);
                                            }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Course & Organization')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                        Select::make('course_id')
                            ->columnSpanFull()
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set, Get $get) {
                                // Clear lesson section and reset order_index when course changes
                                $set('lesson_section_id', null);
                                $courseId = $get('course_id');
                                
                                if ($courseId) {
                                    $nextOrder = Lesson::getNextOrderIndex($courseId, null);
                                    $set('order_index', $nextOrder);
                                }
                            })
                            ->helperText('Select the course this lesson belongs to'),
                        
                        Select::make('lesson_section_id')
                            ->columnSpanFull()
                            ->relationship('lessonSection', 'title')
                            ->options(function (Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) {
                                    return [];
                                }
                                
                                return \App\Models\LessonSection::where('course_id', $courseId)
                                    ->orderBy('order_index')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (callable $set, Get $get) {
                                // Reset order_index when lesson section changes
                                $courseId = $get('course_id');
                                $lessonSectionId = $get('lesson_section_id');
                                
                                if ($courseId) {
                                    $nextOrder = Lesson::getNextOrderIndex($courseId, $lessonSectionId);
                                    $set('order_index', $nextOrder);
                                }
                            })
                            ->helperText('Choose the section within the course'),
                        
                        Select::make('order_index')
                            ->columnSpanFull()
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
                            ->live()
                            ->default(function (Get $get) {
                                $courseId = $get('course_id');
                                $lessonSectionId = $get('lesson_section_id');
                                
                                if (!$courseId) {
                                    return 1;
                                }
                                
                                return Lesson::getNextOrderIndex($courseId, $lessonSectionId);
                            })
                            ->helperText('Order within the section'),
                            ]),

                        Tabs\Tab::make('Media Content')
                            ->icon('heroicon-o-photo')
                            ->schema([
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
                            ->helperText('Recommended size: 1280x720px. Supported formats: JPEG, PNG, WebP. Maximum size: 5MB')
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('lesson_video')
                            ->label('Lesson Video')
                            ->disk('idcloudhost')
                            ->directory('lessons/videos')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov'])
                            ->maxSize(512000) // 500MB
                            ->helperText('Supported formats: MP4, WebM, OGG, AVI, MOV. Maximum size: 500MB')
                            ->columnSpanFull()
                            ->visible(fn(Get $get) => $get('lesson_type') === 'video')
                            ->required(fn(Get $get) => $get('lesson_type') === 'video'),
                            ]),

                        Tabs\Tab::make('Lesson Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Placeholder::make('content_help')
                                    ->content(function (Get $get) {
                                        $type = $get('lesson_type');
                                        return match ($type) {
                                            'video' => new HtmlString('<div class="text-sm text-gray-600"><strong>Video Lesson:</strong> Add video summary, key takeaways, and supplementary materials.</div>'),
                                            'pages' => new HtmlString('<div class="text-sm text-gray-600"><strong>Reading Material:</strong> Create comprehensive written content with headings, sections, and references.</div>'),
                                            'quiz' => new HtmlString('<div class="text-sm text-gray-600"><strong>Quiz/Assessment:</strong> Add instructions, questions, and assessment criteria.</div>'),
                                            default => new HtmlString('<div class="text-sm text-gray-600">Select a lesson type to see content guidelines.</div>'),
                                        };
                                    })
                                    ->columnSpanFull(),

                                RichEditor::make('content_body')
                                    ->required()
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'blockquote',
                                        'bold',
                                        'bulletList',
                                        'codeBlock',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'link',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'table',
                                        'undo',
                                    ])
                                    ->placeholder('Enter the lesson content here...')
                                    ->helperText('Use rich formatting to create engaging lesson content'),
                            ]),
                    ]),
            ]);
    }
}
