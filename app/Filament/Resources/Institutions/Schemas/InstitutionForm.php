<?php

namespace App\Filament\Resources\Institutions\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InstitutionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Base Setting')
                            ->schema([
                                TextInput::make('name')->required(),
                                TextInput::make('slug')->required(),
                                TextInput::make('domain')->required(),
                                Select::make('settings.theme')
                                    ->label('Theme')
                                    ->options([
                                        'light' => 'Light',
                                        'dark' => 'Dark',
                                        'auto' => 'Auto',
                                    ])
                                    ->default('light')
                                    ->required(),
                                Toggle::make('settings.allow_public_registration')
                                    ->label('Allow Public Registration')
                                    ->default(false),
                                TextInput::make('settings.contact_email')
                                    ->label('Contact Email')
                                    ->email(),
                            ]),
                        Tab::make('Logo Upload')
                            ->schema([
                                FileUpload::make('settings.logo_url')
                                    ->label('Logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('institutions/logos')
                                    ->imageEditor()
                                    ->preserveFilenames()
                                    ->visibility('public')
                                    ->openable(),
                            ]),
                        Tab::make('Social Media')
                            ->schema([
                                TextInput::make('settings.social_links.facebook')
                                    ->label('Facebook')
                                    ->url(),
                                TextInput::make('settings.social_links.twitter')
                                    ->label('Twitter')
                                    ->url(),
                                TextInput::make('settings.social_links.linkedin')
                                    ->label('LinkedIn')
                                    ->url(),
                                TextInput::make('settings.social_links.github')
                                    ->label('GitHub')
                                    ->url(),
                            ]),
                    ]),
            ]);
    }
}
