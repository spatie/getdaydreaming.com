<?php

namespace Database\Factories;

use App\Models\InstallReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallReport>
 */
class InstallReportFactory extends Factory
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
            'report_date' => today()->toDateString(),
            'app_version' => '1.0.0',
            'app_build' => '1',
            'macos_version' => '26.0.0',
            'architecture' => 'arm64',
            'reported_at' => now(),
        ];
    }
}
