<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'banner',
        'description',
        'tags',
        'estimated_time',
        'is_published',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
    ];
    
    /**
     * Get the lesson sections for the course.
     */
    public function lessonSections() {
        return $this->hasMany(LessonSection::class)->orderBy('order_index');
    }

    // retrieve all lessons grouped
    public function lessonsGroupedBySection() {
        return $this->sections()->with('lessons')
            ->get()
            ->map(fn($sec) => [
                'section' => $sec,
                'lessons' => $sec->lessons
            ]);
    }

    // fallback for standalone lessons
    public function unsectionedLessons() {
        return $this->lessons()->whereNull('section_id')->orderBy('order_index');
    }
    
    /**
     * Get the lessons for the course.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order_index');
    }
    
    /**
     * Get the tasks for the course.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * Get the enrollments for the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    /**
     * The learning paths that belong to the course.
     */
    public function learningPaths(): BelongsToMany
    {
        return $this->belongsToMany(LearningPath::class, 'learning_path_course')
            ->withPivot('order_index')
            ->orderBy('learning_path_course.order_index')
            ->withTimestamps();
    }
}
