<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lesson_type',
        'lesson_banner',
        'lesson_video',
        'title',
        'slug',
        'content_body',
        'order_index',
        'course_id',
        'lesson_section_id',
    ];
    
    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['lessonSection'];

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->lesson_banner) {
            return null;
        }
        
        // Cache the URL to avoid repeated storage checks
        return Cache::remember('lesson_banner_' . $this->id, 3600, function () {
            // Use StorageHelper to generate URL without relying on the url() method
            if (Storage::disk('idcloudhost')->exists($this->lesson_banner)) {
                return StorageHelper::getFileUrl('idcloudhost', $this->lesson_banner);
            } else if (Storage::disk('public')->exists($this->lesson_banner)) {
                return StorageHelper::getFileUrl('public', $this->lesson_banner);
            }
            
            // If all else fails, just return the path
            return '/storage/' . $this->lesson_banner;
        });
    }
    
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->lesson_video) {
            return null;
        }
        
        // Cache the URL to avoid repeated storage checks
        return Cache::remember('lesson_video_' . $this->id, 3600, function () {
            // Use StorageHelper to generate URL without relying on the url() method
            if (Storage::disk('idcloudhost')->exists($this->lesson_video)) {
                return StorageHelper::getFileUrl('idcloudhost', $this->lesson_video);
            } else if (Storage::disk('public')->exists($this->lesson_video)) {
                return StorageHelper::getFileUrl('public', $this->lesson_video);
            }
            
            // If all else fails, just return the path
            return '/storage/' . $this->lesson_video;
        });
    }

    public function scopeInCourseWithSection($q, $courseId) {
        $cacheKey = 'lessons_in_course_' . $courseId;
        
        return Cache::remember($cacheKey, 3600, function () use ($q, $courseId) {
            return $q->where('course_id', $courseId)
                     ->with('lessonSection')
                     ->orderByRaw('COALESCE(lesson_section_id, 0), order_index');
        });
    }
    
    /**
     * Get adjacent lessons (next and previous) for the current lesson
     *
     * @param int $courseId
     * @param int $currentOrderIndex
     * @return array
     */
    public static function getAdjacentLessons($courseId, $currentOrderIndex)
    {
        $cacheKey = "adjacent_lessons_{$courseId}_{$currentOrderIndex}";
        
        return Cache::remember($cacheKey, 3600, function () use ($courseId, $currentOrderIndex) {
            $nextLesson = self::where('course_id', $courseId)
                ->where('order_index', '>', $currentOrderIndex)
                ->orderBy('order_index')
                ->select('id', 'title', 'slug', 'order_index')
                ->first();
                
            $previousLesson = self::where('course_id', $courseId)
                ->where('order_index', '<', $currentOrderIndex)
                ->orderBy('order_index', 'desc')
                ->select('id', 'title', 'slug', 'order_index')
                ->first();
                
            return [
                'next' => $nextLesson,
                'previous' => $previousLesson
            ];
        });
    }

    /**
     * Get the course that owns the lesson.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    /**
     * Get the lesson section that owns the lesson.
     */
    public function lessonSection(): BelongsTo
    {
        return $this->belongsTo(LessonSection::class);
    }
    
    /**
     * Get the tasks for the lesson.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * Get the progress logs for the lesson.
     */
    public function progressLogs(): HasMany
    {
        return $this->hasMany(ProgressLog::class);
    }
}
