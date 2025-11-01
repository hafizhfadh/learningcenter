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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * This method orchestrates the complete database seeding process with proper
     * dependency management, error handling, and transaction management.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding process...');

        try {
            DB::transaction(function () {
                // Phase 1: Core Foundation Data
                $this->seedFoundationData();
                
                // Phase 2: User Management and Roles
                $this->seedUserManagement();
                
                // Phase 3: Course Structure and Content
                $this->seedCourseStructure();
                
                // Phase 4: Learning Relationships and Progress
                $this->seedLearningRelationships();
                
                // Phase 5: Task and Assessment Data
                $this->seedTasksAndAssessments();
                
                // Phase 6: Enrollment and Progress Data
                $this->seedEnrollmentAndProgress();
            });

            $this->command->info('✅ Database seeding completed successfully!');
            $this->displaySeedingSummary();

        } catch (Exception $e) {
            $this->command->error('❌ Database seeding failed: ' . $e->getMessage());
            Log::error('Database seeding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Seed foundation data (institutions and basic structure)
     */
    private function seedFoundationData(): void
    {
        $this->command->info('📋 Phase 1: Seeding foundation data...');
        
        // Only seed if no institutions exist
        if (!\App\Models\Institution::exists()) {
            $this->call([
                InstitutionSeeder::class,
            ]);
            $this->command->info('   ✓ Institutions created');
        } else {
            $this->command->info('   ↪ Institutions already exist, skipping');
        }
    }

    /**
     * Seed user management and role-based access control
     */
    private function seedUserManagement(): void
    {
        $this->command->info('👥 Phase 2: Seeding user management...');
        
        // Seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
        ]);
        $this->command->info('   ✓ Roles and permissions created');
        
        // Seed users with roles (UserRoleSeeder handles both users and role assignments)
        if (User::count() === 0) {
            $this->call([
                UserRoleSeeder::class,
            ]);
            $this->command->info('   ✓ Users and role assignments created');
        } else {
            // If users exist, still run UserRoleSeeder to ensure role assignments and course-teacher relationships
            $this->call([
                UserRoleSeeder::class,
            ]);
            $this->command->info('   ✓ User roles and course assignments updated');
        }
    }

    /**
     * Seed course structure and content
     */
    private function seedCourseStructure(): void
    {
        $this->command->info('📚 Phase 3: Seeding course structure...');
        
        // Seed learning paths first
        if (!\App\Models\LearningPath::exists()) {
            $this->call([
                LearningPathSeeder::class,
            ]);
            $this->command->info('   ✓ Learning paths created');
        } else {
            $this->command->info('   ↪ Learning paths already exist, skipping');
        }
        
        // Seed courses (UserRoleSeeder may have created some, but CourseSeeder adds more)
        if (Course::count() < 10) { // Threshold to determine if we need more courses
            $this->call([
                CourseSeeder::class,
            ]);
            $this->command->info('   ✓ Additional courses created');
        } else {
            $this->command->info('   ↪ Sufficient courses exist, skipping CourseSeeder');
        }
        
        // Seed lesson sections
        if (!\App\Models\LessonSection::exists()) {
            $this->call([
                LessonSectionSeeder::class,
            ]);
            $this->command->info('   ✓ Lesson sections created');
        } else {
            $this->command->info('   ↪ Lesson sections already exist, skipping');
        }
        
        // Seed lessons
        if (!Lesson::exists()) {
            $this->call([
                LessonSeeder::class,
            ]);
            $this->command->info('   ✓ Lessons created');
        } else {
            $this->command->info('   ↪ Lessons already exist, skipping');
        }
    }

    /**
     * Seed learning path relationships
     */
    private function seedLearningRelationships(): void
    {
        $this->command->info('🔗 Phase 4: Seeding learning relationships...');
        
        // Associate courses with learning paths
        $hasAssociations = DB::table('learning_path_course')->exists();
        
        if (!$hasAssociations) {
            $this->createLearningPathAssociations();
            $this->command->info('   ✓ Learning path-course associations created');
        } else {
            $this->command->info('   ↪ Learning path associations already exist, skipping');
        }
    }

    /**
     * Seed tasks and assessments
     */
    private function seedTasksAndAssessments(): void
    {
        $this->command->info('📝 Phase 5: Seeding tasks and assessments...');
        
        // Create tasks for lessons if they don't exist
        if (!Task::exists()) {
            $this->createTasksForLessons();
            $this->command->info('   ✓ Tasks and questions created');
        } else {
            $this->command->info('   ↪ Tasks already exist, skipping');
        }
        
        // Always run TaskSubmissionSeeder for fresh submission data
        $this->call([
            TaskSubmissionSeeder::class,
        ]);
        $this->command->info('   ✓ Task submissions created');
    }

    /**
     * Seed enrollment and progress data
     */
    private function seedEnrollmentAndProgress(): void
    {
        $this->command->info('📈 Phase 6: Seeding enrollment and progress...');
        
        // Create enrollments and progress if they don't exist
        if (!Enrollment::exists()) {
            $this->createEnrollmentsAndProgress();
            $this->command->info('   ✓ Enrollments and progress logs created');
        } else {
            $this->command->info('   ↪ Enrollments already exist, skipping');
        }
    }

    /**
     * Create learning path associations
     */
    private function createLearningPathAssociations(): void
    {
        $learningPaths = LearningPath::all();
        $courses = Course::all();
        
        if ($learningPaths->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('   ⚠ No learning paths or courses found for associations');
            return;
        }
        
        foreach ($learningPaths as $learningPath) {
            // Each learning path gets 3-5 courses
            $pathCourses = $courses->random(min(rand(3, 5), $courses->count()));
            
            foreach ($pathCourses as $i => $course) {
                $learningPath->courses()->attach($course->id, [
                    'order_index' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Create tasks for lessons
     */
    private function createTasksForLessons(): void
    {
        $lessons = Lesson::all();
        
        if ($lessons->isEmpty()) {
            $this->command->warn('   ⚠ No lessons found for task creation');
            return;
        }
        
        foreach ($lessons as $lesson) {
            // 70% chance of having a task
            if (rand(1, 10) <= 7) {
                $task = Task::factory()->create([
                    'course_id' => $lesson->course_id,
                    'lesson_id' => $lesson->id,
                ]);
                
                // Create questions for each task
                TaskQuestion::factory(rand(3, 8))->create([
                    'task_id' => $task->id,
                ]);
            }
        }
    }

    /**
     * Create enrollments and progress data
     */
    private function createEnrollmentsAndProgress(): void
    {
        $users = User::all();
        $courses = Course::all();
        
        if ($users->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('   ⚠ No users or courses found for enrollment creation');
            return;
        }
        
        foreach ($courses as $course) {
            $courseLessons = Lesson::where('course_id', $course->id)->get();
            
            foreach ($users as $user) {
                // 40% chance of being enrolled in a course
                if (rand(1, 10) <= 4) {
                    $enrollment = Enrollment::factory()->create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ]);
                    
                    // Create progress logs for enrolled users
                    $this->createProgressLogsForEnrollment($user, $course, $courseLessons, $enrollment);
                }
            }
        }
    }

    /**
     * Create progress logs for an enrollment
     */
    private function createProgressLogsForEnrollment(User $user, Course $course, $courseLessons, Enrollment $enrollment): void
    {
        $completedLessonsCount = 0;
        
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
                    $completedLessonsCount++;
                }
            }
        }
        
        // Update enrollment progress
        $totalLessons = $courseLessons->count();
        $progress = ($totalLessons > 0) ? ($completedLessonsCount / $totalLessons) * 100 : 0;
        
        $enrollment->update([
            'progress' => $progress,
            'enrollment_status' => $progress >= 100 ? 'completed' : 'enrolled',
        ]);
    }

    /**
     * Display seeding summary
     */
    private function displaySeedingSummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 Seeding Summary:');
        $this->command->info('   • Institutions: ' . \App\Models\Institution::count());
        $this->command->info('   • Users: ' . User::count());
        $this->command->info('   • Courses: ' . Course::count());
        $this->command->info('   • Lessons: ' . Lesson::count());
        $this->command->info('   • Tasks: ' . Task::count());
        $this->command->info('   • Enrollments: ' . Enrollment::count());
        $this->command->info('   • Learning Paths: ' . LearningPath::count());
        $this->command->info('   • Course-Teacher Assignments: ' . DB::table('course_teachers')->count());
        $this->command->info('');
    }
}
