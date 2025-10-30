<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LearningPathForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Learning Path Form')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null)
                                            ->label('Learning Path Name')
                                            ->placeholder('e.g., Web Development Fundamentals')
                                            ->helperText('A clear, descriptive name for your learning path')
                                            ->prefixIcon('heroicon-o-academic-cap')
                                            ->columnSpan(1),
                                        
                                        TextInput::make('slug')
                                             ->required()
                                             ->maxLength(255)
                                             ->unique(ignoreRecord: true)
                                             ->rules(['alpha_dash'])
                                             ->label('URL Slug')
                                             ->placeholder('web-development-fundamentals')
                                             ->helperText('Used in URLs. Auto-generated from name if left empty')
                                             ->prefixIcon('heroicon-o-link')
                                             ->columnSpan(1),
                                    ]),
                            ]),
                        
                        Tab::make('Visual Content')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('banner')
                                    ->label('Banner Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('learning-paths/banners')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '3:2',
                                        '1:1',
                                    ])
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('16:9')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->maxSize(5120) // 5MB
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Upload a high-quality banner image. Recommended: 1920x1080px (16:9 ratio), max 5MB')
                                    ->hint('Optimal size: 1920x1080px')
                                    ->hintColor('success')
                                    ->columnSpanFull(),
                            ]),
                        
                        Tab::make('Content Description')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                RichEditor::make('description')
                                    ->required()
                                    ->maxLength(2000)
                                    ->label('Learning Path Description')
                                    ->placeholder('Describe what students will learn, the skills they will gain, and the outcomes they can expect...')
                                    ->helperText('Provide a comprehensive description using rich text formatting. Include learning objectives, prerequisites, and expected outcomes.')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                        'blockquote',
                                        'codeBlock',
                                        'link',
                                        'undo',
                                        'redo',
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(2);
    }
}
