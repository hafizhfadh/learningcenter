<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\ProgressLog;
use App\Models\Task;
use App\Models\TaskQuestion;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we already have users in the database
        if (User::count() === 0) {
            $this->call([
                InstitutionSeeder::class,
                UserSeeder::class,
                LearningPathSeeder::class,
                CourseSeeder::class,
                LearningPathCourseSeeder::class,
            ]);
        }
        
        // Check if we already have lesson sections in the database
        if (\App\Models\LessonSection::count() === 0) {
            $this->call([
                LessonSectionSeeder::class,
            ]);
        }
        
        // Check if we already have lessons in the database
        if (Lesson::count() === 0) {
            $this->call([
                LessonSeeder::class,
            ]);
        }
        
        // Always run the TaskSubmissionSeeder
        $this->call([
            TaskSubmissionSeeder::class,
        ]);
        
        // Get all users, courses and learning paths
        $users = User::all();
        $courses = Course::all();
        $learningPaths = LearningPath::all();
        
        // Check if learning paths already have courses
        $hasAssociations = DB::table('learning_path_course')->count() > 0;
        
        // Associate courses with learning paths only if no associations exist
        if (!$hasAssociations) {
            foreach ($learningPaths as $index => $learningPath) {
                // Each learning path gets 3-5 courses
                $pathCourses = $courses->random(rand(3, 5));
                
                foreach ($pathCourses as $i => $course) {
                    $learningPath->courses()->attach($course->id, [
                        'order_index' => $i + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        // Get lessons (now created by LessonSeeder)
        $lessons = Lesson::all();
        
        // Create tasks and enrollments for each course
        foreach ($courses as $course) {
            $courseLessons = $lessons->where('course_id', $course->id);
            
            // Create tasks for some lessons
            foreach ($courseLessons as $lesson) {
                // 70% chance of having a task
                if (rand(1, 10) <= 7) {
                    $task = Task::factory()->create([
                        'course_id' => $course->id,
                        'lesson_id' => $lesson->id,
                    ]);
                    
                    // Create questions for each task
                    TaskQuestion::factory(rand(3, 8))->create([
                        'task_id' => $task->id,
                    ]);
                }
            }
            
            // Create enrollments for users if they don't exist already
            $hasEnrollments = Enrollment::count() > 0;
            if (!$hasEnrollments) {
                foreach ($users as $user) {
                    // 40% chance of being enrolled in a course
                    if (rand(1, 10) <= 4) {
                        $enrollment = Enrollment::factory()->create([
                            'user_id' => $user->id,
                            'course_id' => $course->id,
                        ]);
                        
                        // Create progress logs for enrolled users
                        foreach ($courseLessons as $lesson) {
                            // 60% chance of having viewed a lesson
                            if (rand(1, 10) <= 6) {
                                ProgressLog::factory()->viewed()->create([
                                    'user_id' => $user->id,
                                    'course_id' => $course->id,
                                    'lesson_id' => $lesson->id,
                                ]);
                                
                                // 40% chance of having completed a viewed lesson
                                if (rand(1, 10) <= 4) {
                                    ProgressLog::factory()->completed()->create([
                                        'user_id' => $user->id,
                                        'course_id' => $course->id,
                                        'lesson_id' => $lesson->id,
                                        'created_at' => now()->addHours(rand(1, 24)),
                                    ]);
                                }
                            }
                        }
                        
                        // Update enrollment progress based on completed lessons
                        $completedLessonsCount = ProgressLog::where('user_id', $user->id)
                            ->where('course_id', $course->id)
                            ->where('action', 'completed')
                            ->count();
                        
                        $totalLessons = $courseLessons->count();
                        $progress = ($totalLessons > 0) ? ($completedLessonsCount / $totalLessons) * 100 : 0;
                        
                        $enrollment->update([
                            'progress' => $progress,
                            'enrollment_status' => $progress >= 100 ? 'completed' : 'enrolled',
                        ]);
                    }
                }
            }
        }
    }
}
