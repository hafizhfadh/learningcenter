<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Services\CaddyService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;

class ListInstitutions extends ListRecords
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->getCaddyStatusAction(),
        ];
    }

    protected function getCaddyStatusAction(): Action
    {
        return Action::make('caddyStatus')
            ->label('Caddy Status')
            ->icon('heroicon-o-server')
            ->color(Color::Blue)
            ->action(function () {
                $caddyService = app(CaddyService::class);
                
                if ($caddyService->isHealthy()) {
                    $domains = $caddyService->getConfiguredDomains();
                    $domainCount = count($domains);
                    
                    Notification::make()
                        ->title('Caddy API Status: Healthy')
                        ->body("Caddy is running with {$domainCount} configured domains.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Caddy API Status: Unavailable')
                        ->body('Cannot connect to Caddy API. Please check if Caddy is running.')
                        ->danger()
                        ->send();
                }
            });
    }
}
