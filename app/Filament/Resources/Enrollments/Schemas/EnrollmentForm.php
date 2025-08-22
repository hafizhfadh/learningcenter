<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enrollment Details')
                    ->columnSpanFull()
                    ->description('Manage student enrollment information')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Student')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a student')
                                    ->helperText('Choose the student to enroll'),
                                
                                Select::make('course_id')
                                    ->label('Course')
                                    ->relationship('course', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a course')
                                    ->helperText('Choose the course for enrollment'),
                            ]),
                        
                        Grid::make(3)
                            ->schema([
                                Select::make('enrollment_status')
                                    ->label('Status')
                                    ->options([
                                        'enrolled' => 'Enrolled',
                                        'completed' => 'Completed',
                                        'dropped' => 'Dropped',
                                    ])
                                    ->default('enrolled')
                                    ->required()
                                    ->native(false)
                                    ->helperText('Current enrollment status'),
                                
                                TextInput::make('progress')
                                    ->label('Progress (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->default(0)
                                    ->required()
                                    ->helperText('Completion percentage (0-100)'),
                                
                                DateTimePicker::make('enrolled_at')
                                    ->label('Enrollment Date')
                                    ->default(now())
                                    ->required()
                                    ->native(false)
                                    ->helperText('When the student enrolled'),
                            ]),
                    ]),
            ]);
    }
}
