<?php

namespace Database\Factories;

use App\Models\Installation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installation>
 */
class InstallationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token_hash' => hash('sha256', fake()->uuid()),
            'app_version' => '1.0.0',
            'app_build' => '1',
            'macos_version' => '26.0.0',
            'architecture' => 'arm64',
            'report_count' => 1,
            'upgrade_count' => 0,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'last_reported_at' => now(),
        ];
    }
}
