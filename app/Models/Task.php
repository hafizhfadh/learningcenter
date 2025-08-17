<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_type',
        'title',
        'description',
        'settings',
        'course_id',
        'lesson_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
    ];
    
    /**
     * Get the course that owns the task.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    /**
     * Get the lesson that owns the task.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    /**
     * Get the questions for the task.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(TaskQuestion::class);
    }
    
    /**
     * Get the submissions for the task.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class);
    }
}
