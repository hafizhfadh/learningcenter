<?php

namespace App\Models;

use Database\Factories\LearningPathFactory;
use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
}
