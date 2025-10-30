<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\TaskSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskSubmission>
 */
class TaskSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'student_id' => User::factory()->student(),
            'response_text' => null,
            'file_path' => null,
            'submitted_at' => now(),
            'graded_by' => null,
            'grade' => null,
            'feedback_text' => null,
        ];
    }
    
    /**
     * Configure the submission for an MCQ task.
     */
    public function forMcqTask(Task $task = null): static
    {
        return $this->state(function (array $attributes) use ($task) {
            // If a specific task is provided, use it
            if ($task) {
                $attributes['task_id'] = $task->id;
            } else {
                // Otherwise create a new MCQ task
                $attributes['task_id'] = Task::factory()->mcq()->create()->id;
            }
            
            // Generate a random MCQ response (comma-separated option IDs)
            $attributes['response_text'] = implode(',', [
                fake()->numberBetween(1, 4),
                fake()->boolean(30) ? fake()->numberBetween(1, 4) : null, // 30% chance of multiple selection
            ]);
            
            return $attributes;
        });
    }
    
    /**
     * Configure the submission for an essay task.
     */
    public function forEssayTask(Task $task = null): static
    {
        return $this->state(function (array $attributes) use ($task) {
            // If a specific task is provided, use it
            if ($task) {
                $attributes['task_id'] = $task->id;
            } else {
                // Otherwise create a new essay task
                $attributes['task_id'] = Task::factory()->essay()->create()->id;
            }
            
            // Generate a random essay response
            $attributes['response_text'] = fake()->paragraphs(fake()->numberBetween(2, 5), true);
            
            return $attributes;
        });
    }
    
    /**
     * Configure the submission for a file upload task.
     */
    public function forFileUploadTask(Task $task = null): static
    {
        return $this->state(function (array $attributes) use ($task) {
            // If a specific task is provided, use it
            if ($task) {
                $attributes['task_id'] = $task->id;
            } else {
                // Otherwise create a new file upload task
                $attributes['task_id'] = Task::factory()->fileUpload()->create()->id;
            }
            
            // Generate a fake file path (in a real scenario, this would be an actual uploaded file)
            $attributes['file_path'] = 'submissions/' . fake()->uuid() . '.pdf';
            
            return $attributes;
        });
    }
    
    /**
     * Mark the submission as graded.
     */
    public function graded(User $teacher = null): static
    {
        return $this->state(function (array $attributes) use ($teacher) {
            // If a specific teacher is provided, use them as the grader
            if ($teacher) {
                $attributes['graded_by'] = $teacher->id;
            } else {
                // Otherwise create a new teacher
                $attributes['graded_by'] = User::factory()->teacher()->create()->id;
            }
            
            // Generate a random grade (0-100)
            $attributes['grade'] = fake()->numberBetween(0, 100);
            
            // Generate random feedback
            $attributes['feedback_text'] = fake()->paragraph();
            
            return $attributes;
        });
    }
}
