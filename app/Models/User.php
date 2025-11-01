<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bio',
        'institution_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Students cannot access admin panel
        if ($this->hasRole('student')) {
            return false;
        }
        
        // All other roles can access if they have the permission
        return $this->can('access_admin_panel');
    }
    
    /**
     * Get the institution for the user.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
    
    /**
     * Get the enrollments for the user.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    /**
     * Get the progress logs for the user.
     */
    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProgressLog::class);
    }
    
    /**
     * Get the task submissions for the user.
     */
    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'student_id');
    }

    /**
     * Get the courses assigned to this teacher.
     * Only available for users with the 'school_teacher' role.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teachers', 'teacher_id', 'course_id')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Get the courses assigned to this teacher (alias for courses()).
     * This provides a more semantic method name for teacher-specific contexts.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teachingCourses(): BelongsToMany
    {
        return $this->courses();
    }
}
