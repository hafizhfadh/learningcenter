<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@email.com',
            'password' => bcrypt('admin'),
        ]);
        
        // Create instructor users
        User::factory()->create([
            'name' => 'John Instructor',
            'email' => 'john@instructor.com',
            'password' => bcrypt('password'),
        ]);
        
        User::factory()->create([
            'name' => 'Jane Instructor',
            'email' => 'jane@instructor.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create student users
        $students = [
            [
                'name' => 'Alice Student',
                'email' => 'alice@student.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Bob Student',
                'email' => 'bob@student.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Charlie Student',
                'email' => 'charlie@student.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Diana Student',
                'email' => 'diana@student.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'Evan Student',
                'email' => 'evan@student.com',
                'password' => bcrypt('password'),
            ],
        ];
        
        foreach ($students as $student) {
            User::factory()->create($student);
        }
        
        // Create additional random users
        User::factory(10)->create();
    }
}