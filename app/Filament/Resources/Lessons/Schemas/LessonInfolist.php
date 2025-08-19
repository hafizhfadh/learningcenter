<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LessonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('lesson_type'),
                TextEntry::make('lesson_banner'),
                TextEntry::make('lesson_video'),
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('order_index')
                    ->numeric(),
                TextEntry::make('course.title'),
                TextEntry::make('lessonSection.title'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
