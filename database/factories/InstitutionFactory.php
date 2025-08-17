<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $slug = Str::slug($name);
        return [
            'name' => $name,
            'slug' => $slug,
            'domain' => $slug . '.' . fake()->domainWord() . '.com',
            'settings' => json_encode([
                'theme' => fake()->randomElement(['light', 'dark', 'blue']),
                'allow_public_registration' => fake()->boolean(),
            ]),
        ];
    }
}
