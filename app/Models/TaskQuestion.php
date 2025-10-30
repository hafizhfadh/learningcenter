<?php

namespace App\Models;

use Database\Factories\TaskQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskQuestion extends Model
{
    /** @use HasFactory<TaskQuestionFactory> */
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'question_type',
        'question_title',
        'description',
        'choices',
        'answer_key',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'choices' => 'array',
        'answer_key' => 'array',
    ];
    
    /**
     * Get the task that owns the question.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
