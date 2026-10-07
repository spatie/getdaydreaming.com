<?php

namespace App\Providers;

use App\AppcastFeed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('welcome', function (ViewInstance $view): void {
            $view->with('latestRelease', app(AppcastFeed::class)->latestRelease());
        });

        RateLimiter::for('installReports', function (Request $request): array {
            $token = $request->input('token');
            $tokenKey = is_string($token) ? strtolower($token) : '';

            return [
                Limit::perHour(12)->by('token:'.hash_hmac('sha256', $tokenKey, config('app.key'))),
                Limit::perHour(120)->by('source:'.hash_hmac('sha256', $request->ip() ?? '', config('app.key'))),
            ];
        });

        RateLimiter::for('promptSubmissions', function (Request $request): Limit {
            return Limit::perHour(20)
                ->by('source:'.hash_hmac('sha256', $request->ip() ?? '', config('app.key')));
        });
    }
}
