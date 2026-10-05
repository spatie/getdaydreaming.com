<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::view('/', 'welcome', [
    'photoFrames' => [
        ...array_map(function (int $hour): array {
            $files = array_map(fn (int $hour): string => 'bridge-hour-'.str_pad((string) $hour, 2, '0', STR_PAD_LEFT), range(0, 23));
            $files[7] = 'bridge-morning';
            $files[13] = 'bridge-noon';
            $files[19] = 'bridge-evening';
            $files[23] = 'bridge-night';
            $light = ['midnight moonlight', 'cool early-night moonlight', 'silver moonlight over the dark bay', 'deep blue predawn moonlight', 'a faint violet predawn horizon', 'pink first light before sunrise', 'warm sunrise and long shadows', 'golden early-morning light', 'fresh warm morning sunlight', 'bright clear morning light', 'high late-morning sunlight', 'near-noon sunlight on the blue bay', 'bright noon light over turquoise water', 'sparkling afternoon sunlight', 'warm early-afternoon sunlight', 'clear afternoon light', 'mellow late-afternoon light', 'amber late-afternoon light', 'low sunset light and long shadows', 'rich golden evening light', 'violet blue-hour light', 'deep twilight with bridge lamps', 'blue-black night with bridge lights', 'silver moonlight and reflections'];
            $period = match (true) {
                $hour < 5, $hour >= 21 => 'night',
                $hour < 12 => 'morning',
                $hour < 18 => 'afternoon',
                default => 'evening',
            };

            return ['key' => 'hour-'.$hour, 'file' => $files[$hour], 'minutes' => $hour * 60, 'weather' => 'clear', 'label' => 'Clear '.$period, 'alt' => 'Golden Gate Bridge in '.$light[$hour].' at '.$hour.':00, AI-edited example'];
        }, range(0, 23)),
        ['key' => 'rain', 'file' => 'bridge-rain', 'minutes' => 840, 'weather' => 'rain', 'label' => 'Rainy afternoon', 'alt' => 'Golden Gate Bridge with wet roads and afternoon rain, AI-edited example'],
        ['key' => 'snow', 'file' => 'bridge-snow', 'minutes' => 840, 'weather' => 'snow', 'label' => 'Snowy afternoon', 'alt' => 'Golden Gate Bridge with an imagined light afternoon snowfall, AI-edited example'],
        ['key' => 'fog', 'file' => 'bridge-fog', 'minutes' => 420, 'weather' => 'fog', 'label' => 'Foggy morning', 'alt' => 'Golden Gate Bridge emerging from morning sea mist, AI-edited example'],
        ['key' => 'storm', 'file' => 'bridge-storm', 'minutes' => 900, 'weather' => 'storm', 'label' => 'Stormy afternoon', 'alt' => 'Golden Gate Bridge in heavy rain beneath storm clouds and a distant lightning bolt, static AI-edited example'],
    ],
    'wildPrompt' => 'Turn the bay into a sky garden. Keep the bridge. Let koi swim through clouds, with giant water lilies and floating islands.',
    'wildFrames' => array_map(fn (int $hour): array => [
        'hour' => $hour,
        'file' => 'bridge-wild-'.str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
        'alt' => 'Golden Gate Bridge in a sky garden at '.$hour.':00, with floating koi, giant water lilies and islands above clouds, AI-edited example',
    ], range(0, 23)),
    'photoCredits' => [
        ['picture' => 'Bridge', 'author' => 'Edgar Chaparro', 'source' => 'https://commons.wikimedia.org/wiki/File:Golden_Gate_Bridge_in_sunlight_(Unsplash).jpg', 'license' => 'CC0', 'licenseUrl' => 'https://creativecommons.org/publicdomain/zero/1.0/', 'downloaded' => '2026-10-05'],
    ],
], headers: ['Cache-Control' => 'public, max-age=300, s-maxage=3600'])
    ->withoutMiddleware([
        StartSession::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
        ShareErrorsFromSession::class,
    ])
    ->name('home');
