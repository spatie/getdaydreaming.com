<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.admin.emails', ['owner@spatie.be']);
    }

    public function test_google_login_is_available_only_when_configured(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Continue with Google');

        $redirect = $this->get(route('admin.google.redirect'));
        $redirect->assertRedirect();
        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $parameters);
        $this->assertSame('spatie.be', $parameters['hd']);
        $this->assertNotEmpty($parameters['state']);
        $this->assertSame(route('admin.google.callback'), $parameters['redirect_uri']);

        config()->set('services.google.client_secret', null);

        $this->get('/admin/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get(route('admin.google.redirect'))->assertNotFound();
        $this->get(route('admin.google.callback'))->assertNotFound();
    }

    public function test_verified_spatie_workspace_admin_can_sign_in_and_is_bound_to_google_subject(): void
    {
        $user = User::factory()->create(['email' => 'owner@spatie.be', 'is_admin' => true]);
        Socialite::fake('google', $this->googleUser());

        $this->get(route('admin.google.callback'))
            ->assertRedirect(route('filament.admin.pages.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-subject-123', $user->fresh()->google_id);
        $this->get('/admin')->assertOk();
    }

    public function test_google_login_rejects_an_unapproved_or_unverified_identity(): void
    {
        User::factory()->create(['email' => 'owner@spatie.be', 'is_admin' => true]);

        foreach ([
            ['email_verified' => false],
            ['hd' => 'other.example'],
            ['hd' => null],
            ['email' => 'owner@spatie.be.evil.example'],
        ] as $claims) {
            Socialite::fake('google', $this->googleUser($claims));

            $this->get(route('admin.google.callback'))
                ->assertRedirect(route('filament.admin.auth.login'))
                ->assertSessionHas('googleLoginError');

            $this->assertGuest();
        }

        $this->assertNull(User::query()->where('email', 'owner@spatie.be')->value('google_id'));
    }

    public function test_google_login_requires_an_existing_allowlisted_admin_and_matching_google_subject(): void
    {
        Socialite::fake('google', $this->googleUser());
        $this->get(route('admin.google.callback'))->assertRedirect(route('filament.admin.auth.login'));
        $this->assertDatabaseCount('users', 0);

        $user = User::factory()->create(['email' => 'owner@spatie.be']);
        $this->get(route('admin.google.callback'))->assertRedirect(route('filament.admin.auth.login'));

        $user->forceFill(['is_admin' => true])->save();
        config()->set('services.admin.emails', []);
        $this->get(route('admin.google.callback'))->assertRedirect(route('filament.admin.auth.login'));

        config()->set('services.admin.emails', ['owner@spatie.be']);
        $user->forceFill(['is_admin' => true, 'google_id' => 'another-google-subject'])->save();
        $this->get(route('admin.google.callback'))->assertRedirect(route('filament.admin.auth.login'));
        $this->assertSame('another-google-subject', $user->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_google_login_rejects_a_cancelled_callback(): void
    {
        $this->get(route('admin.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHas('googleLoginError');

        $this->assertGuest();
    }

    public function test_google_login_rejects_a_callback_without_oauth_state(): void
    {
        $this->get(route('admin.google.callback', ['code' => 'untrusted-code']))
            ->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionHas('googleLoginError');

        $this->assertGuest();
    }

    private function googleUser(array $claims = []): GoogleUser
    {
        return GoogleUser::fake(array_replace([
            'id' => 'google-subject-123',
            'email' => 'owner@spatie.be',
            'email_verified' => true,
            'hd' => 'spatie.be',
        ], $claims));
    }
}
