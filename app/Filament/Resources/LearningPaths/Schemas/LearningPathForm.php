<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LearningPathForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null)
                    ->label('Learning Path Name')
                    ->placeholder('Enter the learning path name')
                    ->columnSpanFull(),
                
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['alpha_dash'])
                    ->label('URL Slug')
                    ->placeholder('auto-generated-from-name')
                    ->helperText('Used in URLs. Will be auto-generated from name if left empty.')
                    ->columnSpanFull(),
                
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
                        '1:1',
                    ])
                    ->maxSize(5120) // 5MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Upload a banner image for the learning path. Max size: 5MB. Recommended aspect ratio: 16:9')
                    ->columnSpanFull(),
                
                Textarea::make('description')
                    ->required()
                    ->maxLength(1000)
                    ->rows(4)
                    ->label('Description')
                    ->placeholder('Describe what students will learn in this learning path...')
                    ->helperText('Provide a comprehensive description of the learning path content and objectives.')
                    ->columnSpanFull(),
            ]);
    }
}
