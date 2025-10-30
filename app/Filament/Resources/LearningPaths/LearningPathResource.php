<?php

namespace App\Filament\Resources\LearningPaths;

use App\Filament\Resources\LearningPaths\Pages\CreateLearningPath;
use App\Filament\Resources\LearningPaths\Pages\EditLearningPath;
use App\Filament\Resources\LearningPaths\Pages\ListLearningPaths;
use App\Filament\Resources\LearningPaths\Pages\ViewLearningPath;
use App\Filament\Resources\LearningPaths\RelationManagers\CoursesRelationManager;
use App\Filament\Resources\LearningPaths\Schemas\LearningPathForm;
use App\Filament\Resources\LearningPaths\Schemas\LearningPathInfolist;
use App\Filament\Resources\LearningPaths\Tables\LearningPathsTable;
use App\Models\LearningPath;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Navigation\NavigationGroup;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LearningPathResource extends Resource
{
    protected static ?string $model = LearningPath::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Learning Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return LearningPathForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LearningPathInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningPathsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CoursesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningPaths::route('/'),
            'create' => CreateLearningPath::route('/create'),
            'view' => ViewLearningPath::route('/{record}'),
            'edit' => EditLearningPath::route('/{record}/edit'),
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
