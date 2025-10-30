<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\LearningPath;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LearningPathCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the learning path to course mappings with order indexes
        $pathMappings = [
            'web-development-fundamentals' => [
                'introduction-to-html-and-css' => 1,
                'javascript-essentials' => 2,
                'responsive-web-design' => 3,
                'introduction-to-react' => 4,
            ],
            'data-science-essentials' => [
                'introduction-to-python' => 1,
                'data-analysis-with-pandas' => 2,
                'data-visualization-techniques' => 3,
                'introduction-to-machine-learning' => 4,
            ],
            'mobile-app-development' => [
                'introduction-to-mobile-development' => 1,
                'ui-ux-design-principles' => 2,
                'cross-platform-development-with-react-native' => 3,
                'mobile-app-testing-and-deployment' => 4,
            ],
            'cybersecurity-fundamentals' => [
                'introduction-to-cybersecurity' => 1,
                'network-security-essentials' => 2,
                'ethical-hacking-and-penetration-testing' => 3,
            ],
            'cloud-computing-and-devops' => [
                'introduction-to-cloud-computing' => 1,
                'devops-practices-and-tools' => 2,
                'containerization-with-docker' => 3,
                'kubernetes-for-container-orchestration' => 4,
            ],
        ];
        
        // Iterate through each learning path
        foreach ($pathMappings as $pathSlug => $courses) {
            $learningPath = LearningPath::where('slug', $pathSlug)->first();
            
            if (!$learningPath) {
                continue; // Skip if learning path not found
            }
            
            // Attach courses to the learning path with order index
            foreach ($courses as $courseSlug => $orderIndex) {
                $course = Course::where('slug', $courseSlug)->first();
                
                if (!$course) {
                    continue; // Skip if course not found
                }
                
                // Attach course to learning path with order index
                $learningPath->courses()->attach($course->id, ['order_index' => $orderIndex]);
            }
        }
        
        // For any learning paths without courses, add some random courses
        $learningPathsWithoutCourses = LearningPath::whereDoesntHave('courses')->get();
        $allCourses = Course::all();
        
        foreach ($learningPathsWithoutCourses as $learningPath) {
            // Get 3-5 random courses
            $randomCourses = $allCourses->random(rand(3, 5));
            
            // Attach random courses with sequential order indexes
            $orderIndex = 1;
            foreach ($randomCourses as $course) {
                $learningPath->courses()->attach($course->id, ['order_index' => $orderIndex]);
                $orderIndex++;
            }
        }
    }
}
