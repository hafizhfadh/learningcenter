<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['enrolled', 'completed', 'dropped'];
        
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'enrollment_status' => fake()->randomElement($statuses),
            'progress' => fake()->randomFloat(2, 0, 100),
            'enrolled_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
    
    /**
     * Configure the enrollment to be in enrolled status.
     */
    public function enrolled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'enrolled',
            'progress' => fake()->randomFloat(2, 0, 70),
        ]);
    }
    
    /**
     * Configure the enrollment to be in completed status.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'completed',
            'progress' => 100,
        ]);
    }
    
    /**
     * Configure the enrollment to be in dropped status.
     */
    public function dropped(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_status' => 'dropped',
            'progress' => fake()->randomFloat(2, 0, 50),
        ]);
    }
}
