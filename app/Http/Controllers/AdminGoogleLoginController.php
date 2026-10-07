<?php

namespace App\Http\Controllers;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AdminGoogleLoginController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless($this->isConfigured(), 404);

        return Socialite::driver('google')
            ->with(['hd' => 'spatie.be', 'prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->isConfigured(), 404);

        if ($request->has('error')) {
            return $this->deny();
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return $this->deny();
        }

        $email = strtolower((string) $googleUser->getEmail());
        $googleId = $googleUser->getId();
        $googleClaims = $googleUser->getRaw();

        if (! str_ends_with($email, '@spatie.be') || ! is_string($googleId) || $googleId === ''
            || ($googleClaims['email_verified'] ?? null) !== true || ($googleClaims['hd'] ?? null) !== 'spatie.be') {
            return $this->deny();
        }

        $panel = Filament::getPanel('admin');

        $user = DB::transaction(function () use ($email, $googleId, $panel): ?User {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

            if (! $user?->canAccessPanel($panel)) {
                return null;
            }

            if ($user->google_id !== null && ! hash_equals($user->google_id, $googleId)) {
                return null;
            }

            if ($user->google_id === null) {
                $user->forceFill(['google_id' => $googleId])->save();
            }

            return $user;
        });

        if ($user === null) {
            return $this->deny();
        }

        Auth::guard($panel->getAuthGuard())->login($user);
        $request->session()->regenerate();

        return redirect()->route('filament.admin.pages.dashboard');
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function deny(): RedirectResponse
    {
        return redirect()->route('filament.admin.auth.login')
            ->with('googleLoginError', 'Only approved @spatie.be admins can sign in with Google.');
    }
}
