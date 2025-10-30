<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class LearningPathInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Learning Path Overview')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Learning Path Name')
                                    ->weight(FontWeight::Bold)
                                    ->size('xl')
                                    ->color('primary')
                                    ->icon('heroicon-o-academic-cap')
                                    ->columnSpan(1),
                                
                                TextEntry::make('slug')
                                    ->label('URL Slug')
                                    ->copyable()
                                    ->copyMessage('Slug copied to clipboard!')
                                    ->copyMessageDuration(2000)
                                    ->fontFamily('mono')
                                    ->color('gray')
                                    ->prefix('/')
                                    ->icon('heroicon-o-link')
                                    ->badge()
                                    ->columnSpan(1),
                            ]),
                        
                        ImageEntry::make('banner')
                            ->label('Banner Image')
                            ->disk('public')
                            ->imageHeight(200)
                            ->defaultImageUrl(url('/images/placeholder.png'))
                            ->extraImgAttributes([
                                'class' => 'rounded-xl object-cover shadow-lg border-2 border-gray-100',
                                'loading' => 'lazy'
                            ])
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->banner)),
                    ])
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->persistCollapsed(),
                
                Section::make('Content Description')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Description')
                            ->html()
                            ->prose()
                            ->columnSpanFull()
                            ->placeholder('No description provided')
                            ->helperText('The detailed description of this learning path'),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->persistCollapsed()
                    ->compact(),
                
                Grid::make(3)
                    ->schema([
                        Section::make('Course Statistics')
                            ->schema([
                                TextEntry::make('courses_count')
                                    ->label('Total Courses')
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state === 0 => 'danger',
                                        $state <= 3 => 'warning',
                                        $state <= 10 => 'success',
                                        default => 'primary',
                                    })
                                    ->icon('heroicon-o-academic-cap')
                                    ->formatStateUsing(fn ($state) => $state . ' course' . ($state !== 1 ? 's' : ''))
                                    ->placeholder('0 courses'),
                            ])
                            ->columnSpan(1)
                            ->compact(),
                        
                        Section::make('Creation Info')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M j, Y')
                                    ->icon('heroicon-o-calendar')
                                    ->color('success')
                                    ->tooltip(fn ($record) => $record->created_at->format('F j, Y \\a\\t g:i A')),
                            ])
                            ->columnSpan(1)
                            ->compact(),
                        
                        Section::make('Last Update')
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->label('Modified')
                                    ->dateTime('M j, Y')
                                    ->icon('heroicon-o-pencil-square')
                                    ->color('warning')
                                    ->tooltip(fn ($record) => $record->updated_at->format('F j, Y \\a\\t g:i A')),
                            ])
                            ->columnSpan(1)
                            ->compact(),
                    ]),
                
                Section::make('System Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn ($state) => "#{$state}")
                                    ->columnSpan(1),
                                
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->color('gray')
                                    ->columnSpan(1),
                                
                                TextEntry::make('updated_at')
                                    ->label('Last Updated At')
                                    ->dateTime('F j, Y \\a\\t g:i A')
                                    ->color('gray')
                                    ->columnSpan(1),
                            ]),
                        
                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->dateTime('F j, Y \\a\\t g:i A')
                            ->color('danger')
                            ->icon('heroicon-o-trash')
                            ->badge()
                            ->visible(fn ($record) => !empty($record->deleted_at)),
                    ])
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->compact(),
            ]);
    }
}
