<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_progress')
                ->label('Update Progress')
                ->icon('heroicon-o-arrow-trending-up')
                ->color(Color::Green)
                ->schema([
                    TextInput::make('progress')
                        ->label('Progress (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%')
                        ->default(fn () => $this->record->progress)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'progress' => $data['progress'],
                    ]);
                    
                    Notification::make()
                        ->title('Progress Updated')
                        ->body("Progress updated to {$data['progress']}%")
                        ->success()
                        ->send();
                }),
            
            Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color(Color::Orange)
                ->schema([
                    Select::make('enrollment_status')
                        ->label('New Status')
                        ->options([
                            'enrolled' => 'Enrolled',
                            'completed' => 'Completed',
                            'dropped' => 'Dropped',
                        ])
                        ->default(fn () => $this->record->enrollment_status)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $oldStatus = $this->record->enrollment_status;
                    $newStatus = $data['enrollment_status'];
                    
                    $this->record->update([
                        'enrollment_status' => $newStatus,
                        'progress' => $newStatus === 'completed' ? 100 : $this->record->progress,
                    ]);
                    
                    Notification::make()
                        ->title('Status Changed')
                        ->body("Status changed from {$oldStatus} to {$newStatus}")
                        ->success()
                        ->send();
                }),
            
            Action::make('view_course')
                ->label('View Course')
                ->icon('heroicon-o-book-open')
                ->color(Color::Blue)
                ->url(fn () => route('filament.admin.resources.courses.view', $this->record->course))
                ->openUrlInNewTab(),
            
            Action::make('view_student')
                ->label('View Student')
                ->icon('heroicon-o-user')
                ->color(Color::Purple)
                ->url(fn () => route('filament.admin.resources.users.view', $this->record->user))
                ->openUrlInNewTab(),
            
            EditAction::make()
                ->icon('heroicon-o-pencil'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->user?->name . ' - ' . $this->record->course?->title;
    }

    public function getSubheading(): string
    {
        $status = ucfirst($this->record->enrollment_status);
        $progress = $this->record->progress;
        $enrolledDate = $this->record->enrolled_at?->format('M j, Y');
        
        return "Status: {$status} | Progress: {$progress}% | Enrolled: {$enrolledDate}";
    }
}
