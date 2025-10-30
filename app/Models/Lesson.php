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
use Illuminate\Support\Facades\DB;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson) {
            if ($lesson->order_index === null || $lesson->order_index === 0) {
                $lesson->order_index = static::getNextOrderIndex($lesson->course_id, $lesson->lesson_section_id);
            } else {
                static::reorderLessons($lesson->course_id, $lesson->lesson_section_id, $lesson->order_index, null, $lesson);
            }
        });
        
        static::updating(function (Lesson $lesson) {
            if ($lesson->isDirty('order_index')) {
                $oldOrderIndex = $lesson->getOriginal('order_index');
                static::reorderLessons($lesson->course_id, $lesson->lesson_section_id, $lesson->order_index, $oldOrderIndex, $lesson);
            }
        });
        
        static::deleted(function (Lesson $lesson) {
            static::reorderAfterDeletion($lesson->course_id, $lesson->lesson_section_id, $lesson->order_index);
        });
    }
    
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
    
    /**
     * Get the next available order index for a course and lesson section
     */
    public static function getNextOrderIndex($courseId, $lessonSectionId = null): int
    {
        $query = static::where('course_id', $courseId);
        
        if ($lessonSectionId) {
            $query->where('lesson_section_id', $lessonSectionId);
        } else {
            $query->whereNull('lesson_section_id');
        }
        
        return $query->max('order_index') + 1;
    }
    
    /**
     * Reorder lessons when order_index changes
     */
    protected static function reorderLessons($courseId, $lessonSectionId, $newOrderIndex, $oldOrderIndex = null, $currentLesson = null): void
    {
        DB::transaction(function () use ($courseId, $lessonSectionId, $newOrderIndex, $oldOrderIndex, $currentLesson) {
            $query = static::where('course_id', $courseId);
            
            if ($lessonSectionId) {
                $query->where('lesson_section_id', $lessonSectionId);
            } else {
                $query->whereNull('lesson_section_id');
            }
            
            // Exclude current lesson if updating
            if ($currentLesson && $currentLesson->exists) {
                $query->where('id', '!=', $currentLesson->id);
            }
            
            if ($oldOrderIndex === null) {
                // Creating new lesson - shift existing lessons down
                $query->where('order_index', '>=', $newOrderIndex)
                      ->increment('order_index');
            } else {
                // Updating existing lesson
                if ($newOrderIndex > $oldOrderIndex) {
                    // Moving down - shift lessons up
                    $query->whereBetween('order_index', [$oldOrderIndex + 1, $newOrderIndex])
                          ->decrement('order_index');
                } else {
                    // Moving up - shift lessons down
                    $query->whereBetween('order_index', [$newOrderIndex, $oldOrderIndex - 1])
                          ->increment('order_index');
                }
            }
        });
    }
    
    /**
     * Reorder lessons after deletion
     */
    protected static function reorderAfterDeletion($courseId, $lessonSectionId, $deletedOrderIndex): void
    {
        $query = static::where('course_id', $courseId);
        
        if ($lessonSectionId) {
            $query->where('lesson_section_id', $lessonSectionId);
        } else {
            $query->whereNull('lesson_section_id');
        }
        
        $query->where('order_index', '>', $deletedOrderIndex)
              ->decrement('order_index');
    }
    
    /**
     * Get available order positions for a course and lesson section
     */
    public static function getAvailableOrderPositions($courseId, $lessonSectionId = null, $excludeId = null): array
    {
        $query = static::where('course_id', $courseId);
        
        if ($lessonSectionId) {
            $query->where('lesson_section_id', $lessonSectionId);
        } else {
            $query->whereNull('lesson_section_id');
        }
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $maxOrder = $query->max('order_index') ?? 0;
        $positions = [];
        
        for ($i = 1; $i <= $maxOrder + 1; $i++) {
            $positions[$i] = "Position {$i}";
        }
        
        return $positions;
    }
}
