<?php

namespace App\Filament\Resources\Institutions\Tables;

use App\Services\CaddyService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InstitutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('domain')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Domain copied to clipboard')
                    ->icon('heroicon-m-globe-alt'),
                IconColumn::make('caddy_status')
                    ->label('Caddy')
                    ->boolean()
                    ->state(function ($record) {
                        $caddyService = app(CaddyService::class);
                        if (!$caddyService->isHealthy()) {
                            return false;
                        }
                        $domains = $caddyService->getConfiguredDomains();
                        return in_array($record->domain, $domains);
                    })
                    ->tooltip(fn ($record) => 
                        app(CaddyService::class)->isHealthy() 
                            ? (in_array($record->domain, app(CaddyService::class)->getConfiguredDomains()) 
                                ? 'Domain configured in Caddy' 
                                : 'Domain not configured in Caddy')
                            : 'Caddy API unavailable'
                    ),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::getAddDomainsToCaddyBulkAction(),
                    static::getRemoveDomainsFromCaddyBulkAction(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    protected static function getAddDomainsToCaddyBulkAction(): BulkAction
    {
        return BulkAction::make('addToCaddy')
            ->label('Add to Caddy')
            ->icon('heroicon-o-plus-circle')
            ->color(Color::Green)
            ->requiresConfirmation()
            ->modalHeading('Add Domains to Caddy')
            ->modalDescription('This will add the selected institution domains to Caddy configuration.')
            ->action(function (Collection $records) {
                $caddyService = app(CaddyService::class);
                
                if (!$caddyService->isHealthy()) {
                    Notification::make()
                        ->title('Caddy API Unavailable')
                        ->body('Cannot connect to Caddy API. Please check if Caddy is running.')
                        ->danger()
                        ->send();
                    return;
                }

                $successCount = 0;
                $failureCount = 0;
                
                foreach ($records as $record) {
                    if (empty($record->domain)) {
                        $failureCount++;
                        continue;
                    }
                    
                    if (!$caddyService->isValidDomain($record->domain)) {
                        $failureCount++;
                        continue;
                    }
                    
                    if ($caddyService->addDomain($record->domain)) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                }
                
                if ($successCount > 0) {
                    Notification::make()
                        ->title('Domains Added')
                        ->body("{$successCount} domain(s) added to Caddy successfully." . 
                               ($failureCount > 0 ? " {$failureCount} failed." : ''))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('No Domains Added')
                        ->body('All domain additions failed. Check the logs for details.')
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getRemoveDomainsFromCaddyBulkAction(): BulkAction
    {
        return BulkAction::make('removeFromCaddy')
            ->label('Remove from Caddy')
            ->icon('heroicon-o-minus-circle')
            ->color(Color::Red)
            ->requiresConfirmation()
            ->modalHeading('Remove Domains from Caddy')
            ->modalDescription('This will remove the selected institution domains from Caddy configuration.')
            ->action(function (Collection $records) {
                $caddyService = app(CaddyService::class);
                
                if (!$caddyService->isHealthy()) {
                    Notification::make()
                        ->title('Caddy API Unavailable')
                        ->body('Cannot connect to Caddy API. Please check if Caddy is running.')
                        ->danger()
                        ->send();
                    return;
                }

                $successCount = 0;
                $failureCount = 0;
                
                foreach ($records as $record) {
                    if (empty($record->domain)) {
                        $failureCount++;
                        continue;
                    }
                    
                    if ($caddyService->removeDomain($record->domain)) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                }
                
                if ($successCount > 0) {
                    Notification::make()
                        ->title('Domains Removed')
                        ->body("{$successCount} domain(s) removed from Caddy successfully." . 
                               ($failureCount > 0 ? " {$failureCount} failed." : ''))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('No Domains Removed')
                        ->body('All domain removals failed. Check the logs for details.')
                        ->danger()
                        ->send();
                }
            });
    }
}
