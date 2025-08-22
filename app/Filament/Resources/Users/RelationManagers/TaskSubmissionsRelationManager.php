<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\TaskSubmission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'taskSubmissions';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->label('Task')
                    ->relationship('task', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Textarea::make('response_text')
                    ->label('Response')
                    ->rows(4)
                    ->columnSpanFull(),
                
                TextInput::make('file_path')
                    ->label('File Path')
                    ->maxLength(255),
                
                DateTimePicker::make('submitted_at')
                    ->label('Submitted At')
                    ->default(now()),
                
                Select::make('graded_by')
                    ->label('Graded By')
                    ->relationship('grader', 'name')
                    ->searchable()
                    ->preload(),
                
                TextInput::make('grade')
                    ->label('Grade')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                
                Textarea::make('feedback_text')
                    ->label('Feedback')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('task.title')
                    ->label('Task')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('task.course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('response_text')
                    ->label('Response')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                BadgeColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . '%' : 'Not Graded')
                    ->colors([
                        'danger' => fn ($state) => $state !== null && $state < 60,
                        'warning' => fn ($state) => $state !== null && $state >= 60 && $state < 80,
                        'success' => fn ($state) => $state !== null && $state >= 80,
                        'gray' => fn ($state) => $state === null,
                    ])
                    ->sortable(),
                
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('grader.name')
                    ->label('Graded By')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not graded'),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['task.course', 'grader']);
    }
}