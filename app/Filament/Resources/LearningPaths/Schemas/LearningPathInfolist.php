<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LearningPathInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Learning Path Name')
                    ->weight('bold')
                    ->size('lg'),
                
                TextEntry::make('slug')
                    ->label('URL Slug')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Slug copied')
                    ->copyMessageDuration(1500),
                
                ImageEntry::make('banner')
                    ->label('Banner Image')
                    ->disk('public')
                    ->imageHeight(200)
                    ->imageWidth(400)
                    ->defaultImageUrl('/images/placeholder-banner.png')
                    ->extraAttributes(['class' => 'rounded-lg'])
                    ->columnSpanFull(),
                
                TextEntry::make('description')
                    ->label('Description')
                    ->prose()
                    ->columnSpanFull(),
                
                TextEntry::make('courses_count')
                    ->label('Total Courses')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state . ' course' . ($state != 1 ? 's' : '')),
                
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime('F j, Y \\a\\t g:i A'),
                
                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('F j, Y \\a\\t g:i A'),
                
                TextEntry::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('F j, Y \\a\\t g:i A')
                    ->placeholder('Not deleted')
                    ->visible(fn ($record) => $record->deleted_at !== null),
            ]);
    }
}
