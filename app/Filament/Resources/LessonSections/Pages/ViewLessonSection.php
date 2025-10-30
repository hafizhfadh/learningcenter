<?php

namespace App\Filament\Resources\LessonSections\Pages;

use App\Filament\Resources\LessonSections\LessonSectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLessonSection extends ViewRecord
{
    protected static string $resource = LessonSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
