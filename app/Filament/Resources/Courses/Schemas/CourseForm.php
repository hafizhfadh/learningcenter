<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Course Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Course Information')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'edit' ? null : $set('slug', Str::slug($state)))
                                            ->columnSpan(1),
                                        
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->rules(['alpha_dash'])
                                            ->helperText('URL-friendly version of the title')
                                            ->columnSpan(1),
                                    ]),
                                
                                RichEditor::make('description')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'blockquote',
                                    ])
                                    ->columnSpanFull(),
                                
                                Grid::make(2)
                                    ->schema([
                                        TagsInput::make('tags')
                                            ->separator(',')
                                            ->placeholder('Add tags...')
                                            ->helperText('Press Enter to add each tag')
                                            ->columnSpan(1),
                                        
                                        TextInput::make('estimated_time')
                                            ->numeric()
                                            ->suffix('minutes')
                                            ->placeholder('120')
                                            ->helperText('Estimated completion time in minutes')
                                            ->columnSpan(1),
                                    ]),
                            ]),
                        
                        Tab::make('Course Media')
                            ->schema([
                                FileUpload::make('banner')
                                    ->label('Course Banner')
                                    ->disk('idcloudhost')
                                    ->directory('courses/banners')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->maxSize(5120) // 5MB
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Upload a banner image for the course (max 5MB)')
                                    ->columnSpanFull(),
                            ]),
                        
                        Tab::make('Publishing')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->helperText('Make this course visible to students')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }
}
