<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TaskSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaskSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teachers for grading
        $teachers = User::factory()->count(3)->teacher()->create();
        
        // Create students for submissions
        $students = User::factory()->count(10)->student()->create();
        
        // Create a course with a lesson section and lessons
        $course = Course::factory()->create();
        
        // Create a lesson section for the course
        $lessonSection = \App\Models\LessonSection::create([
            'course_id' => $course->id,
            'title' => 'Main Section',
            'description' => 'Main content for the course',
            'order_index' => 1,
        ]);
        
        // Create lessons for the section
        $lessons = [];
        for ($i = 1; $i <= 3; $i++) {
            $lessons[] = Lesson::factory()->create([
                'course_id' => $course->id,
                'lesson_section_id' => $lessonSection->id,
                'order_index' => $i,
            ]);
        }
            
        // For each lesson, create different types of tasks
        foreach ($lessons as $lesson) {
            // Create one of each task type per lesson
            Task::factory()->mcq()->create([
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
            ]);
            
            Task::factory()->essay()->create([
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
            ]);
            
            Task::factory()->fileUpload()->create([
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
            ]);
        }
        
        // Get all tasks
        $tasks = Task::all();
        
        // For each student, create submissions for some tasks
        $students->each(function ($student) use ($tasks, $teachers) {
            // Select random tasks for this student to submit (60% of tasks)
            $studentTasks = $tasks->random(ceil($tasks->count() * 0.6));
            
            $studentTasks->each(function ($task) use ($student, $teachers) {
                // Create submission based on task type
                $submission = null;
                
                switch ($task->task_type) {
                    case 'mcq':
                        $submission = TaskSubmission::factory()
                            ->forMcqTask($task)
                            ->create(['student_id' => $student->id]);
                        break;
                        
                    case 'essay':
                        $submission = TaskSubmission::factory()
                            ->forEssayTask($task)
                            ->create(['student_id' => $student->id]);
                        break;
                        
                    case 'file_upload':
                        $submission = TaskSubmission::factory()
                            ->forFileUploadTask($task)
                            ->create(['student_id' => $student->id]);
                        break;
                }
                
                // 70% chance of being graded
                if ($submission && fake()->boolean(70)) {
                    $teacher = $teachers->random();
                    $submission->update([
                        'graded_by' => $teacher->id,
                        'grade' => fake()->numberBetween(0, 100),
                        'feedback_text' => fake()->paragraph(),
                    ]);
                }
            });
        });
        
        // Create some additional ungraded submissions for testing grading functionality
        $tasks->random(5)->each(function ($task) use ($students) {
            $student = $students->random();
            
            switch ($task->task_type) {
                case 'mcq':
                    TaskSubmission::factory()
                        ->forMcqTask($task)
                        ->create(['student_id' => $student->id]);
                    break;
                    
                case 'essay':
                    TaskSubmission::factory()
                        ->forEssayTask($task)
                        ->create(['student_id' => $student->id]);
                    break;
                    
                case 'file_upload':
                    TaskSubmission::factory()
                        ->forFileUploadTask($task)
                        ->create(['student_id' => $student->id]);
                    break;
            }
        });
    }
}
