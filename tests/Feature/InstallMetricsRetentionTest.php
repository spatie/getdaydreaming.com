<?php

namespace Tests\Feature;

use App\Models\Installation;
use App\Models\InstallReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallMetricsRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_reports_and_inactive_installations_are_pruned(): void
    {
        InstallReport::factory()->create(['created_at' => now()->subDays(91)]);
        InstallReport::factory()->create(['created_at' => now()->subDays(89)]);
        Installation::factory()->create(['last_seen_at' => now()->subYear()->subDay()]);
        Installation::factory()->create(['last_seen_at' => now()->subMonths(11)]);

        $this->artisan('daydreaming:prune-install-metrics')->assertSuccessful();

        $this->assertDatabaseCount('install_reports', 1);
        $this->assertDatabaseCount('installations', 1);
    }
}
