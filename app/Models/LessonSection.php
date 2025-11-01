<?php

namespace App\Models;

use Database\Factories\LessonSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonSection extends Model
{
    /** @use HasFactory<LessonSectionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order_index',
    ];

    /**
     * Get the course that owns the lesson section.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons for the lesson section.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order_index');
    }
}
