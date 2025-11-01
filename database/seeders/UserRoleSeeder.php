<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Institution;
use App\Models\Course;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure institutions exist
        $this->createInstitutions();
        
        // Create users with specific roles
        $this->createSuperAdminUsers();
        $this->createSchoolAdminUsers();
        $this->createSchoolTeacherUsers();
        $this->createStudentUsers();
        $this->createPanelUsers();
        
        // Assign teachers to courses
        $this->assignTeachersToCourses();
    }

    /**
     * Create institutions for testing
     */
    private function createInstitutions(): void
    {
        $institutions = [
            [
                'name' => 'Harvard University',
                'slug' => 'harvard-university',
                'domain' => 'harvard.edu',
                'settings' => [
                    'description' => 'Prestigious university in Cambridge, Massachusetts',
                    'address' => 'Cambridge, MA 02138, USA',
                    'phone' => '+1-617-495-1000',
                    'email' => 'info@harvard.edu',
                    'website' => 'https://www.harvard.edu',
                ],
            ],
            [
                'name' => 'Stanford University',
                'slug' => 'stanford-university',
                'domain' => 'stanford.edu',
                'settings' => [
                    'description' => 'Leading research university in Stanford, California',
                    'address' => 'Stanford, CA 94305, USA',
                    'phone' => '+1-650-723-2300',
                    'email' => 'info@stanford.edu',
                    'website' => 'https://www.stanford.edu',
                ],
            ],
            [
                'name' => 'MIT',
                'slug' => 'mit',
                'domain' => 'mit.edu',
                'settings' => [
                    'description' => 'Massachusetts Institute of Technology',
                    'address' => 'Cambridge, MA 02139, USA',
                    'phone' => '+1-617-253-1000',
                    'email' => 'info@mit.edu',
                    'website' => 'https://www.mit.edu',
                ],
            ],
        ];

        foreach ($institutions as $institutionData) {
            Institution::firstOrCreate(
                ['name' => $institutionData['name']],
                $institutionData
            );
        }
    }

    /**
     * Create super admin users
     */
    private function createSuperAdminUsers(): void
    {
        $superAdmins = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@learningcenter.com',
                'password' => Hash::make('password'),
                'bio' => 'System administrator with full access to all features and institutions.',
            ],
            [
                'name' => 'Platform Manager',
                'email' => 'manager@learningcenter.com',
                'password' => Hash::make('password'),
                'bio' => 'Platform manager responsible for overseeing all institutions.',
            ],
        ];

        foreach ($superAdmins as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if (!$user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
            }
        }
    }

    /**
     * Create school admin users
     */
    private function createSchoolAdminUsers(): void
    {
        $institutions = Institution::all();
        
        $schoolAdmins = [
            [
                'name' => 'Dr. Sarah Johnson',
                'email' => 'sarah.johnson@harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Dean of Academic Affairs at Harvard University with over 15 years of experience in educational administration.',
                'institution' => 'Harvard University',
            ],
            [
                'name' => 'Prof. Michael Chen',
                'email' => 'michael.chen@stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Associate Dean of Student Affairs at Stanford University, specializing in curriculum development.',
                'institution' => 'Stanford University',
            ],
            [
                'name' => 'Dr. Emily Rodriguez',
                'email' => 'emily.rodriguez@mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Director of Academic Programs at MIT, focused on innovative learning technologies.',
                'institution' => 'MIT',
            ],
        ];

        foreach ($schoolAdmins as $userData) {
            $institution = $institutions->where('name', $userData['institution'])->first();
            
            if ($institution) {
                $userData['institution_id'] = $institution->id;
                unset($userData['institution']);
                
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );

                if (!$user->hasRole('school_admin')) {
                    $user->assignRole('school_admin');
                }
            }
        }
    }

    /**
     * Create school teacher users
     */
    private function createSchoolTeacherUsers(): void
    {
        $institutions = Institution::all();
        
        $teachers = [
            // Harvard Teachers
            [
                'name' => 'Prof. David Wilson',
                'email' => 'david.wilson@harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Professor of Computer Science at Harvard, specializing in algorithms and data structures.',
                'institution' => 'Harvard University',
            ],
            [
                'name' => 'Dr. Lisa Thompson',
                'email' => 'lisa.thompson@harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Associate Professor of Mathematics, expert in calculus and linear algebra.',
                'institution' => 'Harvard University',
            ],
            
            // Stanford Teachers
            [
                'name' => 'Prof. James Anderson',
                'email' => 'james.anderson@stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Professor of Physics at Stanford, researcher in quantum mechanics and particle physics.',
                'institution' => 'Stanford University',
            ],
            [
                'name' => 'Dr. Maria Garcia',
                'email' => 'maria.garcia@stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Assistant Professor of Chemistry, specializing in organic chemistry and biochemistry.',
                'institution' => 'Stanford University',
            ],
            
            // MIT Teachers
            [
                'name' => 'Prof. Robert Kim',
                'email' => 'robert.kim@mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Professor of Electrical Engineering at MIT, expert in signal processing and machine learning.',
                'institution' => 'MIT',
            ],
            [
                'name' => 'Dr. Jennifer Lee',
                'email' => 'jennifer.lee@mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Associate Professor of Mechanical Engineering, researcher in robotics and automation.',
                'institution' => 'MIT',
            ],
        ];

        foreach ($teachers as $userData) {
            $institution = $institutions->where('name', $userData['institution'])->first();
            
            if ($institution) {
                $userData['institution_id'] = $institution->id;
                unset($userData['institution']);
                
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );

                if (!$user->hasRole('school_teacher')) {
                    $user->assignRole('school_teacher');
                }
            }
        }
    }

    /**
     * Create student users
     */
    private function createStudentUsers(): void
    {
        $institutions = Institution::all();
        
        $students = [
            // Harvard Students
            [
                'name' => 'Alice Johnson',
                'email' => 'alice.johnson@student.harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Computer Science major at Harvard University, interested in artificial intelligence.',
                'institution' => 'Harvard University',
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob.smith@student.harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Mathematics major at Harvard University, focusing on applied mathematics.',
                'institution' => 'Harvard University',
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol.davis@student.harvard.edu',
                'password' => Hash::make('password'),
                'bio' => 'Physics major at Harvard University, interested in theoretical physics.',
                'institution' => 'Harvard University',
            ],
            
            // Stanford Students
            [
                'name' => 'Daniel Brown',
                'email' => 'daniel.brown@student.stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Engineering major at Stanford University, specializing in software engineering.',
                'institution' => 'Stanford University',
            ],
            [
                'name' => 'Emma Wilson',
                'email' => 'emma.wilson@student.stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Chemistry major at Stanford University, interested in pharmaceutical research.',
                'institution' => 'Stanford University',
            ],
            [
                'name' => 'Frank Miller',
                'email' => 'frank.miller@student.stanford.edu',
                'password' => Hash::make('password'),
                'bio' => 'Business major at Stanford University, focusing on entrepreneurship.',
                'institution' => 'Stanford University',
            ],
            
            // MIT Students
            [
                'name' => 'Grace Taylor',
                'email' => 'grace.taylor@student.mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Electrical Engineering major at MIT, interested in renewable energy systems.',
                'institution' => 'MIT',
            ],
            [
                'name' => 'Henry Anderson',
                'email' => 'henry.anderson@student.mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Mechanical Engineering major at MIT, focusing on robotics and automation.',
                'institution' => 'MIT',
            ],
            [
                'name' => 'Ivy Chen',
                'email' => 'ivy.chen@student.mit.edu',
                'password' => Hash::make('password'),
                'bio' => 'Computer Science major at MIT, interested in machine learning and data science.',
                'institution' => 'MIT',
            ],
        ];

        foreach ($students as $userData) {
            $institution = $institutions->where('name', $userData['institution'])->first();
            
            if ($institution) {
                $userData['institution_id'] = $institution->id;
                unset($userData['institution']);
                
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );

                if (!$user->hasRole('student')) {
                    $user->assignRole('student');
                }
            }
        }
    }

    /**
     * Create panel users (basic access)
     */
    private function createPanelUsers(): void
    {
        $panelUsers = [
            [
                'name' => 'Guest User',
                'email' => 'guest@learningcenter.com',
                'password' => Hash::make('password'),
                'bio' => 'Guest user with basic panel access for demonstration purposes.',
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@learningcenter.com',
                'password' => Hash::make('password'),
                'bio' => 'Demo user account for testing basic functionality.',
            ],
        ];

        foreach ($panelUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if (!$user->hasRole('panel_user')) {
                $user->assignRole('panel_user');
            }
        }
    }

    /**
     * Assign teachers to courses based on their institutions and subjects
     */
    private function assignTeachersToCourses(): void
    {
        // Get all teachers
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'school_teacher');
        })->get();

        // Get all courses
        $courses = Course::all();

        if ($courses->isEmpty()) {
            // If no courses exist, create some sample courses for demonstration
            $this->createSampleCourses();
            $courses = Course::all();
        }

        // Define course-teacher assignments based on expertise
        $assignments = [
            // Harvard University assignments
            'david.wilson@harvard.edu' => [
                'Introduction to Computer Science',
                'Data Structures and Algorithms',
                'Web Development Fundamentals',
            ],
            'lisa.thompson@harvard.edu' => [
                'Calculus I',
                'Linear Algebra',
                'Statistics for Data Science',
            ],
            
            // Stanford University assignments
            'james.anderson@stanford.edu' => [
                'Physics I: Mechanics',
                'Quantum Physics',
                'Advanced Physics Laboratory',
            ],
            'maria.garcia@stanford.edu' => [
                'Organic Chemistry',
                'Biochemistry',
                'Chemical Analysis',
            ],
            
            // MIT assignments
            'robert.kim@mit.edu' => [
                'Signal Processing',
                'Machine Learning',
                'Digital Systems Design',
            ],
            'jennifer.lee@mit.edu' => [
                'Robotics Engineering',
                'Mechanical Design',
                'Automation Systems',
            ],
        ];

        foreach ($assignments as $teacherEmail => $courseNames) {
            $teacher = $teachers->where('email', $teacherEmail)->first();
            
            if (!$teacher) {
                continue;
            }

            foreach ($courseNames as $courseName) {
                // Find or create course
                $course = $courses->where('title', $courseName)->first();
                
                if (!$course) {
                    // Create course if it doesn't exist
                    $course = Course::create([
                         'title' => $courseName,
                         'slug' => Str::slug($courseName),
                        'banner' => 'default-banner.jpg',
                        'description' => "Comprehensive course on {$courseName} taught by experienced faculty.",
                        'tags' => $this->generateTagsForCourse($courseName),
                        'estimated_time' => rand(20, 60), // Random hours between 20-60
                        'is_published' => true,
                        'created_by' => $teacher->id, // Set the teacher as the creator
                    ]);
                    
                    $courses->push($course);
                }

                // Assign teacher to course if not already assigned
                if (!$course->teachers()->where('teacher_id', $teacher->id)->exists()) {
                    $course->teachers()->attach($teacher->id, [
                        'assigned_at' => now()->subDays(rand(1, 30)), // Random assignment date within last 30 days
                    ]);
                }
            }
        }

        // Add some additional random assignments for variety
        $this->createAdditionalRandomAssignments($teachers, $courses);
    }

    /**
     * Create sample courses if none exist
     */
    private function createSampleCourses(): void
    {
        $sampleCourses = [
            [
                'title' => 'Introduction to Computer Science',
                'slug' => 'intro-computer-science',
                'banner' => 'cs-intro-banner.jpg',
                'description' => 'A comprehensive introduction to computer science fundamentals including programming, algorithms, and data structures.',
                'tags' => 'computer science,programming,algorithms',
                'estimated_time' => 40,
                'is_published' => true,
            ],
            [
                'title' => 'Calculus I',
                'slug' => 'calculus-1',
                'banner' => 'calculus-banner.jpg',
                'description' => 'Introduction to differential and integral calculus with applications.',
                'tags' => 'mathematics,calculus,derivatives,integrals',
                'estimated_time' => 45,
                'is_published' => true,
            ],
            [
                'title' => 'Physics I: Mechanics',
                'slug' => 'physics-mechanics',
                'banner' => 'physics-banner.jpg',
                'description' => 'Classical mechanics covering motion, forces, energy, and momentum.',
                'tags' => 'physics,mechanics,motion,forces',
                'estimated_time' => 50,
                'is_published' => true,
            ],
            [
                'title' => 'Organic Chemistry',
                'slug' => 'organic-chemistry',
                'banner' => 'chemistry-banner.jpg',
                'description' => 'Study of carbon-based compounds and their reactions.',
                'tags' => 'chemistry,organic,molecules,reactions',
                'estimated_time' => 55,
                'is_published' => true,
            ],
        ];

        // Get a default admin user to assign as creator for sample courses
        $defaultCreator = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        foreach ($sampleCourses as $courseData) {
            // Add created_by to course data if we have a default creator
            if ($defaultCreator) {
                $courseData['created_by'] = $defaultCreator->id;
            }
            
            Course::firstOrCreate(
                ['slug' => $courseData['slug']],
                $courseData
            );
        }
    }

    /**
     * Generate appropriate tags for a course based on its name
     */
    private function generateTagsForCourse(string $courseName): string
    {
        $tagMap = [
            'Computer Science' => 'computer science,programming,software',
            'Data Structures' => 'computer science,algorithms,data structures',
            'Web Development' => 'web development,html,css,javascript',
            'Calculus' => 'mathematics,calculus,derivatives,integrals',
            'Linear Algebra' => 'mathematics,linear algebra,matrices,vectors',
            'Statistics' => 'mathematics,statistics,data analysis,probability',
            'Physics' => 'physics,science,mechanics,laboratory',
            'Quantum' => 'physics,quantum mechanics,advanced physics',
            'Chemistry' => 'chemistry,science,laboratory,molecules',
            'Organic' => 'chemistry,organic chemistry,reactions',
            'Biochemistry' => 'chemistry,biochemistry,biology,molecules',
            'Signal Processing' => 'engineering,signal processing,digital systems',
            'Machine Learning' => 'computer science,machine learning,ai,data science',
            'Robotics' => 'engineering,robotics,automation,mechanical',
            'Mechanical' => 'engineering,mechanical engineering,design',
        ];

        $tags = [];
        foreach ($tagMap as $keyword => $tagString) {
            if (stripos($courseName, $keyword) !== false) {
                $tags[] = $tagString;
            }
        }

        return !empty($tags) ? implode(',', $tags) : 'general,education,course';
    }

    /**
     * Create additional random assignments for variety
     */
    private function createAdditionalRandomAssignments($teachers, $courses): void
    {
        // Assign some teachers to multiple courses and some courses to multiple teachers
        $additionalAssignments = min(10, $teachers->count() * 2); // Limit to reasonable number

        for ($i = 0; $i < $additionalAssignments; $i++) {
            $randomTeacher = $teachers->random();
            $randomCourse = $courses->random();

            // Only assign if not already assigned
            if (!$randomCourse->teachers()->where('teacher_id', $randomTeacher->id)->exists()) {
                $randomCourse->teachers()->attach($randomTeacher->id, [
                    'assigned_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }
}