<?php

namespace App\Filament\Resources\Institutions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InstitutionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('domain'),
                TextEntry::make('settings.theme')->label('Theme'),
                TextEntry::make('settings.logo_url')->label('Logo URL'),
                TextEntry::make('settings.contact_email')->label('Contact Email'),
                TextEntry::make('settings.social_links.facebook')->label('Facebook'),
                TextEntry::make('settings.social_links.twitter')->label('Twitter'),
                TextEntry::make('settings.social_links.linkedin')->label('LinkedIn'),
                TextEntry::make('settings.social_links.github')->label('GitHub'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
                TextEntry::make('deleted_at')->dateTime(),
            ]);
    }
}
