<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class LearningPathSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $learningPaths = [
            [
                'name' => 'Web Development Fundamentals',
                'slug' => 'web-development-fundamentals',
                'banner' => '',
                'is_active' => 1,
                'description' => 'A comprehensive learning path covering the basics of web development, including HTML, CSS, JavaScript, and responsive design principles.',
            ],
            [
                'name' => 'Data Science Essentials',
                'slug' => 'data-science-essentials',
                'banner' => '',
                'is_active' => 1,
                'description' => 'Learn the core concepts and tools of data science, including data analysis, visualization, machine learning, and statistical modeling.',
            ],
            [
                'name' => 'Mobile App Development',
                'slug' => 'mobile-app-development',
                'banner' => '',
                'is_active' => 1,
                'description' => 'Master the skills needed to build mobile applications for iOS and Android platforms using modern frameworks and best practices.',
            ],
            [
                'name' => 'Cloud Computing and DevOps',
                'slug' => 'cloud-computing-and-devops',
                'banner' => '',
                'is_active' => 1,
                'description' => 'Explore cloud infrastructure, containerization, CI/CD pipelines, and DevOps methodologies for modern application deployment.',
            ],
            [
                'name' => 'Cybersecurity Fundamentals',
                'slug' => 'cybersecurity-fundamentals',
                'banner' => '',
                'is_active' => 1,
                'description' => 'Understand the principles of information security, threat detection, vulnerability assessment, and security best practices.',
            ],
        ];
        
        foreach ($learningPaths as $path) {
            LearningPath::create($path);
        }
    }
}
