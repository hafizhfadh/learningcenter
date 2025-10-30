<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first institution for assigning to users
        $institution = Institution::first();
        
        // Create admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@email.com',
            'password' => bcrypt('admin'),
            'bio' => 'System administrator with full access to all features.',
            'institution_id' => $institution?->id,
        ]);
        
        // Create instructor users
        User::factory()->teacher()->create([
            'name' => 'John Instructor',
            'email' => 'john@instructor.com',
            'password' => bcrypt('password'),
            'bio' => 'Experienced instructor specializing in programming fundamentals.',
            'institution_id' => $institution?->id,
        ]);
        
        User::factory()->teacher()->create([
            'name' => 'Jane Instructor',
            'email' => 'jane@instructor.com',
            'password' => bcrypt('password'),
            'bio' => 'Senior instructor with expertise in advanced web development.',
            'institution_id' => $institution?->id,
        ]);
        
        // Create student users
        $students = [
            [
                'name' => 'Alice Student',
                'email' => 'alice@student.com',
                'password' => bcrypt('password'),
                'bio' => 'First-year student interested in web development.',
                'institution_id' => $institution?->id,
            ],
            [
                'name' => 'Bob Student',
                'email' => 'bob@student.com',
                'password' => bcrypt('password'),
                'bio' => 'Second-year student focusing on mobile app development.',
                'institution_id' => $institution?->id,
            ],
            [
                'name' => 'Charlie Student',
                'email' => 'charlie@student.com',
                'password' => bcrypt('password'),
                'bio' => 'Third-year student specializing in data science.',
                'institution_id' => $institution?->id,
            ],
            [
                'name' => 'Diana Student',
                'email' => 'diana@student.com',
                'password' => bcrypt('password'),
                'bio' => 'Graduate student researching AI applications.',
                'institution_id' => $institution?->id,
            ],
            [
                'name' => 'Evan Student',
                'email' => 'evan@student.com',
                'password' => bcrypt('password'),
                'bio' => 'Exchange student with background in cybersecurity.',
                'institution_id' => $institution?->id,
            ],
        ];
        
        foreach ($students as $student) {
            User::factory()->student()->create($student);
        }
        
        // Create additional random users with random institutions
        $institutions = Institution::pluck('id')->toArray();
        User::factory(10)->student()->create([
            'institution_id' => fn() => $institutions ? $institutions[array_rand($institutions)] : null,
            'bio' => fn() => fake()->paragraph(2),
        ]);
        
        // Create a few users without institutions
        User::factory(3)->student()->create([
            'institution_id' => null,
            'bio' => fn() => fake()->paragraph(1),
        ]);
    }
}