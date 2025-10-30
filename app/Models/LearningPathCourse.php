<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LearningPathCourse extends Pivot
{
    protected $table = 'learning_path_courses';

    protected $fillable = [
        'learning_path_id',
        'course_id',
        'order',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the learning path that owns the course.
     */
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class);
    }

    /**
     * Get the order of the course in the learning path.
     */
    public function order()
    {
        return Attribute::make(
            get: fn ($value) => (int) $value,
        );
    }
}
