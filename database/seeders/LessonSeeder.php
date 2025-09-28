<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all courses
        $courses = Course::all();
        
        // Define lesson types
        $lessonTypes = ['video', 'pages', 'quiz'];
        
        // Create lessons for each course
        foreach ($courses as $course) {
            // Get all sections for this course
            $sections = \App\Models\LessonSection::where('course_id', $course->id)->get();
            
            if ($sections->isEmpty()) {
                // If no sections exist, create a default section
                $defaultSection = \App\Models\LessonSection::create([
                    'course_id' => $course->id,
                    'title' => 'Main Content',
                    'description' => 'Main content for ' . $course->title,
                    'order_index' => 1,
                ]);
                $sections = collect([$defaultSection]);
            }
            
            // Generate 2-4 lessons per section
            foreach ($sections as $section) {
                $lessonCount = rand(2, 4);
                
                for ($i = 1; $i <= $lessonCount; $i++) {
                    // Randomly select a lesson type
                    $lessonType = $lessonTypes[array_rand($lessonTypes)];
                    
                    // Create lesson title based on course and lesson number
                    $title = "Lesson {$i}: " . $this->getLessonTitle($course->title, $i, $lessonType);
                    
                    // Create a unique slug by including section id and a random string
                    $uniqueSlug = Str::slug("{$course->title}-section-{$section->id}-lesson-{$i}-" . Str::random(5));
                    
                    // Create the lesson
                    Lesson::create([
                        'lesson_type' => $lessonType,
                        'lesson_banner' => "lessons/default-lesson-banner.jpg", // Use local file path
                        'title' => $title,
                        'slug' => $uniqueSlug,
                        'content_body' => $this->getLessonContent($lessonType),
                        'order_index' => $i,
                        'course_id' => $course->id,
                        'is_published' => true,
                        'lesson_section_id' => $section->id,
                    ]);
                }
            }
        }
    }
    
    /**
     * Generate a lesson title based on course title and lesson number.
     *
     * @param string $courseTitle
     * @param int $lessonNumber
     * @param string $lessonType
     * @return string
     */
    private function getLessonTitle(string $courseTitle, int $lessonNumber, string $lessonType): string
    {
        // Extract the main topic from the course title
        $courseTopic = explode(' ', $courseTitle)[0];
        
        // Generate titles based on lesson type and number
        $titles = [
            'video' => [
                'Introduction to',
                'Understanding',
                'Exploring',
                'Mastering',
                'Deep Dive into',
            ],
            'pages' => [
                'Guide to',
                'Documentation on',
                'Reference for',
                'Handbook of',
                'Manual for',
            ],
            'quiz' => [
                'Test Your Knowledge on',
                'Quiz: ',
                'Assessment: ',
                'Challenge: ',
                'Review: ',
            ],
        ];
        
        // Select a title prefix based on lesson type and position
        $titlePrefix = $titles[$lessonType][$lessonNumber % count($titles[$lessonType])];
        
        // Generate topic based on course and lesson number
        $topics = [
            'HTML' => ['Elements', 'Attributes', 'Forms', 'Tables', 'Semantic Tags', 'Structure'],
            'CSS' => ['Selectors', 'Box Model', 'Flexbox', 'Grid', 'Animations', 'Media Queries'],
            'JavaScript' => ['Variables', 'Functions', 'Objects', 'Arrays', 'DOM Manipulation', 'Events'],
            'Python' => ['Syntax', 'Data Types', 'Functions', 'Classes', 'Modules', 'Libraries'],
            'Data' => ['Cleaning', 'Visualization', 'Analysis', 'Structures', 'Manipulation', 'Modeling'],
            'Machine' => ['Algorithms', 'Models', 'Training', 'Evaluation', 'Deployment', 'Ethics'],
            'Web' => ['Components', 'Layouts', 'Frameworks', 'Optimization', 'Accessibility', 'Performance'],
            'Responsive' => ['Breakpoints', 'Mobile First', 'Layouts', 'Images', 'Typography', 'Navigation'],
        ];
        
        // Find matching topic or use default
        $topicKey = 'Web'; // Default topic
        foreach (array_keys($topics) as $key) {
            if (stripos($courseTitle, $key) !== false) {
                $topicKey = $key;
                break;
            }
        }
        
        // Get subtopic based on lesson number
        $subtopic = $topics[$topicKey][$lessonNumber % count($topics[$topicKey])];
        
        return "{$titlePrefix} {$topicKey} {$subtopic}";
    }
    
    /**
     * Generate lesson content based on lesson type.
     *
     * @param string $lessonType
     * @return string
     */
    private function getLessonContent(string $lessonType): string
    {
        switch ($lessonType) {
            case 'video':
                return "<p>In this video lesson, you will learn the key concepts and practical applications. Watch the video below:</p>\n\n<div class=\"video-container\">\n    <iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/dQw4w9WgXcQ\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>\n</div>\n\n<h3>Video Summary</h3>\n<p>" . fake()->paragraphs(3, true) . "</p>\n\n<h3>Key Takeaways</h3>\n<ul>\n    <li>" . fake()->sentence() . "</li>\n    <li>" . fake()->sentence() . "</li>\n    <li>" . fake()->sentence() . "</li>\n    <li>" . fake()->sentence() . "</li>\n</ul>";
                
            case 'pages':
                return "<h2>Introduction</h2>\n<p>" . fake()->paragraphs(2, true) . "</p>\n\n<h2>Main Content</h2>\n<p>" . fake()->paragraphs(3, true) . "</p>\n\n<h3>Section 1</h3>\n<p>" . fake()->paragraphs(2, true) . "</p>\n\n<h3>Section 2</h3>\n<p>" . fake()->paragraphs(2, true) . "</p>\n\n<h2>Summary</h2>\n<p>" . fake()->paragraphs(1, true) . "</p>\n\n<h3>Further Reading</h3>\n<ul>\n    <li><a href=\"#\">" . fake()->sentence() . "</a></li>\n    <li><a href=\"#\">" . fake()->sentence() . "</a></li>\n    <li><a href=\"#\">" . fake()->sentence() . "</a></li>\n</ul>";
                
            case 'quiz':
                return "<h2>Quiz Instructions</h2>\n<p>Complete the following questions to test your knowledge. You need to score at least 70% to mark this lesson as complete.</p>\n\n<div class=\"quiz-question\">\n    <h3>Question 1</h3>\n    <p>" . fake()->sentence() . "</p>\n    <ul class=\"quiz-options\">\n        <li><label><input type=\"radio\" name=\"q1\" value=\"a\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q1\" value=\"b\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q1\" value=\"c\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q1\" value=\"d\"> " . fake()->sentence() . "</label></li>\n    </ul>\n</div>\n\n<div class=\"quiz-question\">\n    <h3>Question 2</h3>\n    <p>" . fake()->sentence() . "</p>\n    <ul class=\"quiz-options\">\n        <li><label><input type=\"radio\" name=\"q2\" value=\"a\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q2\" value=\"b\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q2\" value=\"c\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q2\" value=\"d\"> " . fake()->sentence() . "</label></li>\n    </ul>\n</div>\n\n<div class=\"quiz-question\">\n    <h3>Question 3</h3>\n    <p>" . fake()->sentence() . "</p>\n    <ul class=\"quiz-options\">\n        <li><label><input type=\"radio\" name=\"q3\" value=\"a\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q3\" value=\"b\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q3\" value=\"c\"> " . fake()->sentence() . "</label></li>\n        <li><label><input type=\"radio\" name=\"q3\" value=\"d\"> " . fake()->sentence() . "</label></li>\n    </ul>\n</div>\n\n<button class=\"submit-quiz\">Submit Quiz</button>";
                
            default:
                return fake()->paragraphs(5, true);
        }
    }
}
