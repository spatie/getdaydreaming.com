<?php

namespace Tests\Feature;

use App\Filament\Resources\Installations\Pages\ListInstallations;
use App\Filament\Widgets\InstallationStats;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_requires_login_an_allowlisted_email_and_an_admin_flag(): void
    {
        Config::set('services.admin.emails', ['owner@spatie.be']);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/register')->assertNotFound();

        $user = User::factory()->create(['email' => 'owner@spatie.be']);
        $this->actingAs($user)->get('/admin')->assertForbidden();

        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user)->get('/admin')->assertOk();

        $user->forceFill(['email' => 'other@example.com'])->save();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_shows_installation_and_upgrade_counts(): void
    {
        Config::set('services.admin.emails', ['owner@spatie.be']);
        $user = User::factory()->create(['email' => 'owner@spatie.be', 'is_admin' => true]);
        Installation::factory()->create(['upgrade_count' => 1]);

        $this->actingAs($user);

        Livewire::test(InstallationStats::class)
            ->assertSee('Installations seen')
            ->assertSee('Active in 7 days')
            ->assertSee('Installations upgraded')
            ->assertSee('1 version changes reported');
    }

    public function test_admin_can_find_an_installation_by_mac_name(): void
    {
        Config::set('services.admin.emails', ['owner@spatie.be']);
        $user = User::factory()->create(['email' => 'owner@spatie.be', 'is_admin' => true]);
        Installation::factory()->create(['mac_name' => 'Freek’s MacBook Pro']);

        $this->actingAs($user);

        Livewire::test(ListInstallations::class)
            ->assertSee('Freek’s MacBook Pro')
            ->assertSee('Last seen');
    }

    public function test_admin_command_refuses_emails_outside_the_allowlist(): void
    {
        Config::set('services.admin.emails', ['owner@spatie.be', 'outsider@example.com']);
        $user = User::factory()->create(['email' => 'other@example.com']);

        $this->artisan('daydreaming:admin', ['email' => $user->email])->assertFailed();
        $this->assertFalse($user->fresh()->is_admin);

        $outsider = User::factory()->create(['email' => 'outsider@example.com']);
        $this->artisan('daydreaming:admin', ['email' => $outsider->email])->assertFailed();

        $owner = User::factory()->create(['email' => 'owner@spatie.be']);
        $this->artisan('daydreaming:admin', ['email' => $owner->email])->assertSuccessful();
        $this->assertTrue($owner->fresh()->is_admin);
    }
}
