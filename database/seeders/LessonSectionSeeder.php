<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\LessonSection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all courses
        $courses = Course::all();
        
        // Create sections for each course
        foreach ($courses as $course) {
            // Generate 2-4 sections per course
            $sectionCount = rand(2, 4);
            
            for ($i = 1; $i <= $sectionCount; $i++) {
                LessonSection::create([
                    'course_id' => $course->id,
                    'title' => "Section {$i}: " . $this->getSectionTitle($i),
                    'description' => "This is section {$i} of the course {$course->title}.",
                    'order_index' => $i,
                ]);
            }
        }
    }
    
    /**
     * Get a section title based on section number.
     */
    private function getSectionTitle(int $sectionNumber): string
    {
        $titles = [
            'Introduction',
            'Fundamentals',
            'Advanced Concepts',
            'Practical Applications',
            'Review and Assessment'
        ];
        
        return $titles[($sectionNumber - 1) % count($titles)];
    }
}
