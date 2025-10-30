<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskQuestion>
 */
class TaskQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionTypes = ['single_choice', 'multiple_choice', 'description'];
        $questionType = fake()->randomElement($questionTypes);
        
        $choices = [];
        $answerKey = [];
        
        if ($questionType === 'single_choice' || $questionType === 'multiple_choice') {
            // Generate 4 choices
            for ($i = 0; $i < 4; $i++) {
                $choices[] = [
                    'id' => $i + 1,
                    'text' => fake()->sentence(),
                ];
            }
            
            // For single choice, select one correct answer
            if ($questionType === 'single_choice') {
                $answerKey = [
                    'correct_choice' => fake()->numberBetween(1, 4),
                ];
            } else {
                // For multiple choice, select 1-3 correct answers
                $correctChoices = fake()->randomElements([1, 2, 3, 4], fake()->numberBetween(1, 3));
                $answerKey = [
                    'correct_choices' => $correctChoices,
                ];
            }
        } else {
            // For description type
            $answerKey = [
                'keywords' => [
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                ],
                'min_words' => fake()->numberBetween(50, 200),
            ];
        }
        
        return [
            'task_id' => Task::factory(),
            'question_type' => $questionType,
            'question_title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'choices' => json_encode($choices),
            'answer_key' => json_encode($answerKey),
        ];
    }
    
    /**
     * Configure the question to be a single choice type.
     */
    public function singleChoice(): static
    {
        return $this->state(function (array $attributes) {
            $choices = [];
            for ($i = 0; $i < 4; $i++) {
                $choices[] = [
                    'id' => $i + 1,
                    'text' => fake()->sentence(),
                ];
            }
            
            $answerKey = [
                'correct_choice' => fake()->numberBetween(1, 4),
            ];
            
            return [
                'question_type' => 'single_choice',
                'choices' => json_encode($choices),
                'answer_key' => json_encode($answerKey),
            ];
        });
    }
    
    /**
     * Configure the question to be a multiple choice type.
     */
    public function multipleChoice(): static
    {
        return $this->state(function (array $attributes) {
            $choices = [];
            for ($i = 0; $i < 4; $i++) {
                $choices[] = [
                    'id' => $i + 1,
                    'text' => fake()->sentence(),
                ];
            }
            
            $correctChoices = fake()->randomElements([1, 2, 3, 4], fake()->numberBetween(1, 3));
            $answerKey = [
                'correct_choices' => $correctChoices,
            ];
            
            return [
                'question_type' => 'multiple_choice',
                'choices' => json_encode($choices),
                'answer_key' => json_encode($answerKey),
            ];
        });
    }
    
    /**
     * Configure the question to be a description type.
     */
    public function description(): static
    {
        return $this->state(function (array $attributes) {
            $answerKey = [
                'keywords' => [
                    fake()->word(),
                    fake()->word(),
                    fake()->word(),
                ],
                'min_words' => fake()->numberBetween(50, 200),
            ];
            
            return [
                'question_type' => 'description',
                'choices' => json_encode([]),
                'answer_key' => json_encode($answerKey),
            ];
        });
    }
}
