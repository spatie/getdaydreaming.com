<?php

namespace Database\Factories;

use App\Models\PromptSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromptSubmission>
 */
class PromptSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => fake()->uuid(),
            'prompt' => fake()->sentence(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'app_version' => '0.1.0',
            'app_build' => '3',
        ];
    }
}
