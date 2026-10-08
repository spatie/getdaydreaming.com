<?php

namespace Tests\Feature;

use App\Models\Installation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_report_is_accepted_without_storing_the_raw_token_or_other_client_data(): void
    {
        $token = (string) Str::uuid();

        $this->postJson(route('installReports.store'), $this->report(['token' => $token]))
            ->assertStatus(202)
            ->assertNoContent(202);

        $this->assertDatabaseCount('installations', 1);
        $this->assertDatabaseCount('install_reports', 1);
        $installation = Installation::query()->firstOrFail();
        $this->assertSame(hash_hmac('sha256', $token, config('app.key')), $installation->token_hash);
        $this->assertSame('1.0.0', $installation->app_version);
        $this->assertSame(1, $installation->report_count);
        $this->assertNotContains('token', Schema::getColumnListing('installations'));
        $this->assertNotContains('ip_address', Schema::getColumnListing('install_reports'));
    }

    public function test_reports_are_idempotent_per_token_day_and_version_and_track_upgrades(): void
    {
        $token = (string) Str::uuid();
        $initial = $this->report(['token' => $token]);

        $this->postJson(route('installReports.store'), $initial)->assertStatus(202);
        $this->postJson(route('installReports.store'), $initial)->assertStatus(202);
        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'app_version' => '1.1.0',
            'app_build' => '2',
        ]))->assertStatus(202);

        $this->travel(1)->day();
        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'app_version' => '1.1.0',
            'app_build' => '2',
        ]))->assertStatus(202);

        $this->assertDatabaseCount('install_reports', 3);
        $installation = Installation::query()->firstOrFail();
        $this->assertSame(3, $installation->report_count);
        $this->assertSame(1, $installation->upgrade_count);
        $this->assertSame('1.1.0', $installation->app_version);
    }

    public function test_new_reports_store_the_mac_name_while_older_reports_keep_working(): void
    {
        $token = (string) Str::uuid();

        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
        ]))->assertStatus(202);

        $installation = Installation::query()->firstOrFail();
        $this->assertNull($installation->mac_name);

        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'app_version' => '0.9.0',
            'app_build' => '53',
            'mac_name' => 'Freek’s MacBook Pro',
            'schema_version' => 2,
        ]))->assertStatus(202);

        $this->assertSame('Freek’s MacBook Pro', $installation->fresh()->mac_name);

        $this->travel(1)->day();
        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'app_version' => '0.9.1',
            'app_build' => '54',
        ]))->assertStatus(202);

        $this->assertSame('Freek’s MacBook Pro', $installation->fresh()->mac_name);
    }

    public function test_invalid_or_extra_fields_are_rejected(): void
    {
        $this->postJson(route('installReports.store'), $this->report([
            'macos_version' => '26',
            'architecture' => 'other',
            'image' => 'private',
        ]))->assertStatus(422)->assertJsonValidationErrors(['macos_version', 'architecture', 'payload']);

        $this->postJson(route('installReports.store'), $this->report([
            'reported_at' => now()->format('Y-m-d\TH:i:sP'),
            'schema_version' => 3,
        ]))->assertStatus(422)->assertJsonValidationErrors(['reported_at', 'schema_version']);

        $this->postJson(route('installReports.store'), $this->report([
            'schema_version' => 2,
        ]))->assertStatus(422)->assertJsonValidationErrors('mac_name');

        $this->postJson(route('installReports.store'), $this->report([
            'schema_version' => 2,
            'mac_name' => "Mac\nName",
        ]))->assertStatus(422)->assertJsonValidationErrors('mac_name');

        $this->postJson(route('installReports.store'), $this->report([
            'mac_name' => 'Unexpected Mac',
        ]))->assertStatus(422)->assertJsonValidationErrors('mac_name');

        $this->postJson(route('installReports.store'), $this->report([
            'token' => '123e4567-e89b-12d3-a456-426614174000',
        ]))->assertStatus(422)->assertJsonValidationErrors(['token']);

        $this->assertDatabaseCount('install_reports', 0);
    }

    public function test_an_older_report_does_not_replace_the_latest_version(): void
    {
        $token = (string) Str::uuid();

        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'app_version' => '2.0.0',
            'app_build' => '2',
        ]))->assertStatus(202);

        $this->postJson(route('installReports.store'), $this->report([
            'token' => $token,
            'reported_at' => now()->subDay()->utc()->format('Y-m-d\TH:i:s\Z'),
        ]))->assertStatus(202);

        $installation = Installation::query()->firstOrFail();
        $this->assertSame('2.0.0', $installation->app_version);
        $this->assertSame(0, $installation->upgrade_count);
        $this->assertSame(2, $installation->report_count);
    }

    public function test_large_and_non_json_requests_are_rejected_before_validation(): void
    {
        $this->call('POST', route('installReports.store'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([...$this->report(), 'padding' => str_repeat('x', 2048)]))->assertStatus(413);

        $this->post(route('installReports.store'), $this->report())->assertStatus(415);
    }

    public function test_a_token_is_rate_limited(): void
    {
        $report = $this->report();

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $this->postJson(route('installReports.store'), $report)->assertStatus(202);
        }

        $this->postJson(route('installReports.store'), $report)->assertStatus(429);
        $this->assertDatabaseCount('install_reports', 1);
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function report(array $overrides = []): array
    {
        return [
            'token' => (string) Str::uuid(),
            'app_version' => '1.0.0',
            'app_build' => '1',
            'macos_version' => '26.0.0',
            'architecture' => 'arm64',
            'reported_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'schema_version' => 1,
            ...$overrides,
        ];
    }
}
