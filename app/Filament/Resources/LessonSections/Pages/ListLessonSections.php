<?php

namespace App\Filament\Resources\LessonSections\Pages;

use App\Filament\Resources\LessonSections\LessonSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLessonSections extends ListRecords
{
    protected static string $resource = LessonSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
