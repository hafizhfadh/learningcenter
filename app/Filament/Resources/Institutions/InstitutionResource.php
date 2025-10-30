<?php

namespace App\Filament\Resources\Institutions;

use App\Filament\Resources\Institutions\Pages\CreateInstitution;
use App\Filament\Resources\Institutions\Pages\EditInstitution;
use App\Filament\Resources\Institutions\Pages\ListInstitutions;
use App\Filament\Resources\Institutions\Pages\ViewInstitution;
use App\Filament\Resources\Institutions\Schemas\InstitutionForm;
use App\Filament\Resources\Institutions\Schemas\InstitutionInfolist;
use App\Filament\Resources\Institutions\Tables\InstitutionsTable;
use App\Models\Institution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return InstitutionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstitutionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstitutionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstitutions::route('/'),
            'create' => CreateInstitution::route('/create'),
            'view' => ViewInstitution::route('/{record}'),
            'edit' => EditInstitution::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
