<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasInstitutionScope
{
    /**
     * Boot the trait
     */
    protected static function bootHasInstitutionScope(): void
    {
        // Apply global scope when creating/updating records
        static::creating(function (Model $model) {
            if (!$model->institution_id && static::shouldApplyInstitutionScope()) {
                $model->institution_id = static::getCurrentInstitutionId();
            }
        });

        // Apply global scope for queries
        static::addGlobalScope('institution', function (Builder $builder) {
            if (static::shouldApplyInstitutionScope()) {
                $institutionId = static::getCurrentInstitutionId();
                if ($institutionId) {
                    $builder->where('institution_id', $institutionId);
                }
            }
        });
    }

    /**
     * Determine if institution scope should be applied
     */
    protected static function shouldApplyInstitutionScope(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        // Apply scope for school_admin and school_teacher roles
        return $user->hasAnyRole(['school_admin', 'school_teacher']);
    }

    /**
     * Get the current institution ID
     */
    protected static function getCurrentInstitutionId(): ?int
    {
        // Try to get from app instance first
        if (app()->bound('current_institution_id')) {
            return app('current_institution_id');
        }

        // Try to get from session
        if (session()->has('current_institution_id')) {
            return session('current_institution_id');
        }

        // Get from authenticated user
        if (Auth::check()) {
            return Auth::user()->institution_id;
        }

        return null;
    }

    /**
     * Scope query to specific institution
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope query to current user's institution
     */
    public function scopeForCurrentInstitution(Builder $query): Builder
    {
        $institutionId = static::getCurrentInstitutionId();
        
        if ($institutionId) {
            return $query->where('institution_id', $institutionId);
        }

        return $query;
    }

    /**
     * Scope query without institution restrictions (for super_admin)
     */
    public function scopeWithoutInstitutionScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('institution');
    }

    /**
     * Get all records across institutions (for super_admin)
     */
    public static function allInstitutions()
    {
        return static::withoutGlobalScope('institution');
    }

    /**
     * Check if current user can access this record
     */
    public function canAccess(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Super admin can access everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // School admin and teacher can only access their institution's data
        if ($user->hasAnyRole(['school_admin', 'school_teacher'])) {
            return $this->institution_id === $user->institution_id;
        }

        return false;
    }

    /**
     * Institution relationship
     */
    public function institution()
    {
        return $this->belongsTo(\App\Models\Institution::class);
    }
}