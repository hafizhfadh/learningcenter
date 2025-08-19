<?php

namespace App\Filament\Resources\LearningPaths\Pages;

use App\Filament\Resources\LearningPaths\LearningPathResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLearningPath extends ViewRecord
{
    protected static string $resource = LearningPathResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
