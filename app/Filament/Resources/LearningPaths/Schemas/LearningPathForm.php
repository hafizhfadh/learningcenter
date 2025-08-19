<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LearningPathForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columnSpanFull()
                    ->description('Define the core details of your learning path')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
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
                
                Section::make('Visual Content')
                    ->columnSpanFull()
                    ->description('Add visual elements to make your learning path more engaging')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
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
                
                Section::make('Content Description')
                    ->columnSpanFull()
                    ->description('Provide detailed information about what learners will gain')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
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
            ])
            ->columns(2);
    }
}
