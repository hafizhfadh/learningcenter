<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Services\CaddyService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            $this->getCaddyDomainAction(),
            $this->getRemoveDomainAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getCaddyDomainAction(): Action
    {
        return Action::make('addToCaddy')
            ->label('Add Domain to Caddy')
            ->icon('heroicon-o-globe-alt')
            ->color(Color::Green)
            ->requiresConfirmation()
            ->modalHeading('Add Domain to Caddy')
            ->modalDescription('This will configure the domain in Caddy for this institution.')
            ->action(function () {
                $caddyService = app(CaddyService::class);
                
                if (!$caddyService->isHealthy()) {
                    Notification::make()
                        ->title('Caddy API Unavailable')
                        ->body('Cannot connect to Caddy API. Please check if Caddy is running.')
                        ->danger()
                        ->send();
                    return;
                }

                if (!$caddyService->isValidDomain($this->record->domain)) {
                    Notification::make()
                        ->title('Invalid Domain')
                        ->body('The domain format is invalid.')
                        ->danger()
                        ->send();
                    return;
                }

                if ($caddyService->addDomain($this->record->domain)) {
                    Notification::make()
                        ->title('Domain Added Successfully')
                        ->body("Domain {$this->record->domain} has been added to Caddy.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to Add Domain')
                        ->body('There was an error adding the domain to Caddy. Check the logs for details.')
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn () => !empty($this->record->domain));
    }

    protected function getRemoveDomainAction(): Action
    {
        return Action::make('removeFromCaddy')
            ->label('Remove Domain from Caddy')
            ->icon('heroicon-o-trash')
            ->color(Color::Red)
            ->requiresConfirmation()
            ->modalHeading('Remove Domain from Caddy')
            ->modalDescription('This will remove the domain configuration from Caddy.')
            ->action(function () {
                $caddyService = app(CaddyService::class);
                
                if (!$caddyService->isHealthy()) {
                    Notification::make()
                        ->title('Caddy API Unavailable')
                        ->body('Cannot connect to Caddy API. Please check if Caddy is running.')
                        ->danger()
                        ->send();
                    return;
                }

                if ($caddyService->removeDomain($this->record->domain)) {
                    Notification::make()
                        ->title('Domain Removed Successfully')
                        ->body("Domain {$this->record->domain} has been removed from Caddy.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to Remove Domain')
                        ->body('There was an error removing the domain from Caddy. Check the logs for details.')
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn () => !empty($this->record->domain));
    }
}
