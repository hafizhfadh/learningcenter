<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\Institution;
use BackedEnum;

class InstitutionSelector extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationLabel = 'Switch Institution';
    
    protected static ?string $title = 'Institution Selector';
    
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.institution-selector';

    /**
     * Check if this page should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->hasRole('super_admin');
    }

    /**
     * Check if user can access this page
     */
    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->can('access_institution_selector');
    }

    /**
     * Get the page title
     */
    public function getTitle(): string|Htmlable
    {
        return 'Institution Selector';
    }

    /**
     * Get the page heading
     */
    public function getHeading(): string|Htmlable
    {
        return 'Switch Institution Context';
    }

    /**
     * Get the page subheading
     */
    public function getSubheading(): ?string
    {
        return 'Select an institution to manage its data and users';
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_selection')
                ->label('Clear Selection')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action('clearSelection')
                ->visible(fn () => session()->has('current_institution_id')),
        ];
    }

    /**
     * Switch to the selected institution
     */
    public function switchInstitution(int $institutionId): void
    {
        $institution = Institution::find($institutionId);
        
        if (!$institution) {
            Notification::make()
                ->title('Error')
                ->body('Selected institution not found.')
                ->danger()
                ->send();
            return;
        }

        // Check if user has access to this institution
        $user = Auth::user();
        if (!$user->hasRole('super_admin') && $user->institution_id !== $institutionId) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to access this institution.')
                ->danger()
                ->send();
            return;
        }

        // Set the institution context
        session(['current_institution_id' => $institutionId]);
        app()->instance('current_institution_id', $institutionId);

        Notification::make()
            ->title('Institution Switched')
            ->body("Successfully switched to: {$institution->name}")
            ->success()
            ->send();

        // Redirect to dashboard to refresh the context
        $this->redirect(route('filament.admin.pages.dashboard'));
    }

    /**
     * Clear the institution selection
     */
    public function clearSelection(): void
    {
        session()->forget('current_institution_id');
        
        if (app()->bound('current_institution_id')) {
            app()->forgetInstance('current_institution_id');
        }

        Notification::make()
            ->title('Selection Cleared')
            ->body('Institution context has been cleared. You will now see data based on your default permissions.')
            ->info()
            ->send();
    }

    /**
     * Get view data for the page
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $currentInstitutionId = session('current_institution_id');
        $currentInstitution = $currentInstitutionId ? Institution::find($currentInstitutionId) : null;
        
        // Get available institutions
        $availableInstitutions = collect();
        if ($user->hasRole('super_admin')) {
            $availableInstitutions = Institution::withCount(['users', 'courses'])->get();
        } elseif ($user->institution_id) {
            $availableInstitutions = Institution::where('id', $user->institution_id)
                ->withCount(['users', 'courses'])
                ->get();
        }

        return [
            'user' => $user,
            'currentInstitution' => $currentInstitution,
            'availableInstitutions' => $availableInstitutions,
            'hasSelection' => !is_null($currentInstitutionId),
        ];
    }
}
