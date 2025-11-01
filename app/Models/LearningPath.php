<?php

namespace App\Models;

use Database\Factories\LearningPathFactory;
use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class LearningPath extends Model
{
    /** @use HasFactory<LearningPathFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'banner',
        'description',
        'is_active',
    ];

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) {
            return null;
        }
        
        // Use StorageHelper to generate URL without relying on the url() method
        if (Storage::disk('idcloudhost')->exists($this->banner)) {
            return StorageHelper::getFileUrl('idcloudhost', $this->banner);
        } else if (Storage::disk('public')->exists($this->banner)) {
            return StorageHelper::getFileUrl('public', $this->banner);
        }
        
        // If all else fails, just return the path
        return '/storage/' . $this->banner;
    }

    /**
     * The courses that belong to the learning path.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'learning_path_course')
            ->withPivot('order_index')
            ->orderBy('learning_path_course.order_index')
            ->withTimestamps();
    }

    /**
     * Get the count of courses in this learning path.
     */
    public function getCoursesCountAttribute(): int
    {
        return $this->courses()->count();
    }



    /**
     * Get the enrollments for this learning path.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the students enrolled in this learning path.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'learning_path_id', 'user_id')
            ->withPivot('enrolled_at', 'progress', 'status')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active learning paths.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope a query to include learning paths accessible by users with institution-bound roles.
     * Only school_teacher, school_admin, and student roles (who are bound to institutions) can access learning paths.
     */
    public function scopeAccessibleByUser($query, $user)
    {
        // Check if user has institution-bound roles
        $institutionBoundRoles = ['school_teacher', 'school_admin', 'student'];
        $hasInstitutionBoundRole = $user->roles()->whereIn('name', $institutionBoundRoles)->exists();
        
        if (!$hasInstitutionBoundRole) {
            // If user doesn't have institution-bound roles, return empty result
            return $query->whereRaw('1 = 0');
        }
        
        // Return active learning paths for users with institution-bound roles
        return $query->active();
    }

    /**
     * Get the total estimated time for all courses in this learning path.
     */
    public function getTotalEstimatedTimeAttribute(): int
    {
        return $this->courses()->sum('estimated_time') ?? 0;
    }

    /**
     * Get the progress percentage for a specific user.
     */
    public function getProgressForUser($userId): float
    {
        $enrollment = $this->enrollments()->where('user_id', $userId)->first();
        return $enrollment ? $enrollment->progress : 0;
    }

    /**
     * Check if a user is enrolled in this learning path.
     */
    public function isUserEnrolled($userId): bool
    {
        return $this->enrollments()->where('user_id', $userId)->exists();
    }
}
