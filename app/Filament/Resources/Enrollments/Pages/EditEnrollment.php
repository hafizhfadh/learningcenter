<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_completed')
                ->label('Mark as Completed')
                ->icon('heroicon-o-check-circle')
                ->color(Color::Green)
                ->requiresConfirmation()
                ->modalHeading('Mark Enrollment as Completed')
                ->modalDescription('This will set the progress to 100% and status to completed.')
                ->modalSubmitActionLabel('Mark Completed')
                ->visible(fn () => $this->record->enrollment_status !== 'completed')
                ->action(function () {
                    $this->record->update([
                        'enrollment_status' => 'completed',
                        'progress' => 100,
                    ]);
                    
                    Notification::make()
                        ->title('Enrollment Completed')
                        ->body('The enrollment has been marked as completed with 100% progress.')
                        ->success()
                        ->send();
                    
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
            
            Action::make('reset_progress')
                ->label('Reset Progress')
                ->icon('heroicon-o-arrow-path')
                ->color(Color::Orange)
                ->requiresConfirmation()
                ->modalHeading('Reset Enrollment Progress')
                ->modalDescription('This will reset the progress to 0% and status to enrolled.')
                ->modalSubmitActionLabel('Reset Progress')
                ->visible(fn () => $this->record->progress > 0)
                ->action(function () {
                    $this->record->update([
                        'enrollment_status' => 'enrolled',
                        'progress' => 0,
                    ]);
                    
                    Notification::make()
                        ->title('Progress Reset')
                        ->body('The enrollment progress has been reset to 0%.')
                        ->success()
                        ->send();
                }),
            
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Enrollment')
                ->modalDescription('Are you sure you want to delete this enrollment? This action can be undone.')
                ->successNotificationTitle('Enrollment deleted successfully'),
            
            ForceDeleteAction::make()
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Enrollment')
                ->modalDescription('Are you sure you want to permanently delete this enrollment? This action cannot be undone.')
                ->successNotificationTitle('Enrollment permanently deleted'),
            
            RestoreAction::make()
                ->icon('heroicon-o-arrow-uturn-left')
                ->successNotificationTitle('Enrollment restored successfully'),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit: ' . $this->record->user?->name . ' - ' . $this->record->course?->title;
    }

    public function getSubheading(): string
    {
        $status = ucfirst($this->record->enrollment_status);
        $progress = $this->record->progress;
        
        return "Current Status: {$status} | Progress: {$progress}%";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Enrollment updated successfully';
    }
}
