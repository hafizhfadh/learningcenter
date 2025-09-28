<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test data
$exam = 'test-exam';
$courseSlug = 'introduction-to-html-and-css';

echo "Testing lesson views...\n";

try {
    // Test 1: Check if LessonService can be instantiated
    $lessonService = $app->make(App\Services\LessonService::class);
    echo "✓ LessonService instantiated successfully\n";

    // Test 2: Check if we can get a course
    $course = App\Models\Course::where('slug', $courseSlug)->first();
    if ($course) {
        echo "✓ Course found: {$course->title}\n";
        
        // Test 3: Check if course has lessons
        $lessons = $course->lessons()->count();
        echo "✓ Course has {$lessons} lessons\n";
        
        // Test 4: Check if course has lesson sections
        $sections = $course->lessonSections()->count();
        echo "✓ Course has {$sections} lesson sections\n";
        
        // Test 5: Test LessonService methods
        try {
            $courseWithLessons = $lessonService->getCourseWithLessons($courseSlug);
            echo "✓ getCourseWithLessons works\n";
        } catch (Exception $e) {
            echo "✗ getCourseWithLessons failed: " . $e->getMessage() . "\n";
        }
        
        try {
            $groupedLessons = $lessonService->getGroupedLessons($course);
            echo "✓ getGroupedLessons works\n";
        } catch (Exception $e) {
            echo "✗ getGroupedLessons failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ Course not found\n";
    }

    // Test 6: Check if views exist
    $viewPaths = [
        'user.lesson.index',
        'user.lesson.partials.course-header',
        'user.lesson.partials.lesson-sections',
        'user.lesson.partials.no-lessons',
        'user.lesson.partials.lesson-timer',
        'user.lesson.partials.content.quiz',
        'user.lesson.partials.content.interactive'
    ];
    
    foreach ($viewPaths as $viewPath) {
        if (view()->exists($viewPath)) {
            echo "✓ View exists: {$viewPath}\n";
        } else {
            echo "✗ View missing: {$viewPath}\n";
        }
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";