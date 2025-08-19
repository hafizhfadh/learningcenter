<?php

namespace App\Filament\Resources\LessonSections\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LessonSectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('course.title'),
                TextEntry::make('title'),
                TextEntry::make('order_index')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
