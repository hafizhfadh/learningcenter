<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Introduction to HTML and CSS',
                'slug' => Str::slug('Introduction to HTML and CSS'),
                'banner' => '',
                'description' => 'Learn the fundamentals of HTML and CSS to build and style web pages from scratch.',
                'is_published' => true,
            ],
            [
                'title' => 'JavaScript Essentials',
                'slug' => Str::slug('JavaScript Essentials'),
                'banner' => '',
                'description' => 'Master the core concepts of JavaScript programming language for dynamic web development.',
                'is_published' => true,
            ],
            [
                'title' => 'Responsive Web Design',
                'slug' => Str::slug('Responsive Web Design'),
                'banner' => '',
                'description' => 'Learn techniques and best practices for creating websites that work well on all devices and screen sizes.',
                'is_published' => true,
            ],
            [
                'title' => 'Python Programming Basics',
                'slug' => Str::slug('Python Programming Basics'),
                'banner' => '',
                'description' => 'An introduction to Python programming language, covering syntax, data types, control structures, and functions.',
                'is_published' => true,
            ],
            [
                'title' => 'Data Analysis with Python',
                'slug' => Str::slug('Data Analysis with Python'),
                'banner' => '',
                'description' => 'Learn how to use Python libraries like Pandas and NumPy for data manipulation and analysis.',
                'is_published' => true,
            ],
            [
                'title' => 'Machine Learning Fundamentals',
                'slug' => Str::slug('Machine Learning Fundamentals'),
                'banner' => '',
                'description' => 'Understand the basic concepts and algorithms of machine learning for predictive modeling and data analysis.',
                'is_published' => true,
            ],
            [
                'title' => 'React.js for Beginners',
                'slug' => Str::slug('React.js for Beginners'),
                'banner' => '',
                'description' => 'Learn how to build interactive user interfaces using the React JavaScript library.',
                'is_published' => true,
            ],
            [
                'title' => 'Node.js Backend Development',
                'slug' => Str::slug('Node.js Backend Development'),
                'banner' => '',
                'description' => 'Build scalable server-side applications using Node.js and Express framework.',
                'is_published' => true,
            ],
            [
                'title' => 'Database Design and SQL',
                'slug' => Str::slug('Database Design and SQL'),
                'banner' => '',
                'description' => 'Learn relational database concepts and how to write SQL queries for data manipulation and retrieval.',
                'is_published' => true,
            ],
            [
                'title' => 'Mobile App Development with React Native',
                'slug' => Str::slug('Mobile App Development with React Native'),
                'banner' => '',
                'description' => 'Build cross-platform mobile applications using React Native framework.',
                'is_published' => true,
            ],
            [
                'title' => 'iOS Development with Swift',
                'slug' => Str::slug('iOS Development with Swift'),
                'banner' => '',
                'description' => 'Learn how to develop native iOS applications using Swift programming language and Xcode.',
                'is_published' => true,
            ],
            [
                'title' => 'Android Development with Kotlin',
                'slug' => Str::slug('Android Development with Kotlin'),
                'banner' => '',
                'description' => 'Master the skills needed to build Android applications using Kotlin programming language.',
                'is_published' => true,
            ],
            [
                'title' => 'Cloud Computing with AWS',
                'slug' => Str::slug('Cloud Computing with AWS'),
                'banner' => '',
                'description' => 'Learn how to use Amazon Web Services for cloud infrastructure and application deployment.',
                'is_published' => true,
            ],
            [
                'title' => 'Docker and Containerization',
                'slug' => Str::slug('Docker and Containerization'),
                'banner' => '',
                'description' => 'Understand container technology and how to use Docker for application packaging and deployment.',
                'is_published' => true,
            ],
            [
                'title' => 'Cybersecurity Basics',
                'slug' => Str::slug('Cybersecurity Basics'),
                'banner' => '',
                'description' => 'Learn the fundamental concepts of cybersecurity, including threat detection and prevention.',
                'is_published' => true,
            ],
        ];
        
        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}
