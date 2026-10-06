<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::view('/', 'welcome', [
    'photoRevision' => 'ridge-20261006',
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
        ...array_merge(...array_map(function (string $weather): array {
            $hours = [0, 7, $weather === 'storm' ? 15 : 14, 19];
            $periods = ['night', 'morning', 'day', 'evening'];
            $adjective = ['rain' => 'Rainy', 'snow' => 'Snowy', 'fog' => 'Foggy', 'storm' => 'Stormy'][$weather];

            return array_map(function (int $index) use ($weather, $hours, $periods, $adjective): array {
                $hour = $hours[$index];
                $period = $periods[$index];
                $isExistingFrame = ($weather === 'fog' && $hour === 7) || ($weather !== 'fog' && $index === 2);
                $file = 'bridge-'.$weather.($isExistingFrame ? '' : '-'.$period);

                return [
                    'key' => $weather.'-'.$hour,
                    'file' => $file,
                    'minutes' => $hour * 60,
                    'weather' => $weather,
                    'label' => $adjective.' '.($period === 'day' ? 'afternoon' : $period),
                    'alt' => 'Golden Gate Bridge in '.$weather.' at '.$hour.':00 with '.$period.' light, AI-edited example',
                ];
            }, range(0, 3));
        }, ['rain', 'snow', 'fog', 'storm'])),
    ],
    'yosemiteFrames' => [
        ...array_map(fn (array $moment): array => [
            'key' => 'yosemite-clear-'.$moment['name'],
            'file' => 'yosemite-clear-'.$moment['name'],
            'minutes' => $moment['hour'] * 60,
            'weather' => 'clear',
            'label' => 'Clear '.$moment['name'],
            'alt' => 'Yosemite Valley under clear skies at '.$moment['hour'].':00, AI-edited example',
        ], [
            ['name' => 'night', 'hour' => 0],
            ['name' => 'dawn', 'hour' => 6],
            ['name' => 'day', 'hour' => 12],
            ['name' => 'evening', 'hour' => 19],
        ]),
        ...array_merge(...array_map(fn (string $weather): array => array_map(fn (array $moment): array => [
            'key' => 'yosemite-'.$weather.'-'.$moment['name'],
            'file' => 'yosemite-'.$weather.'-'.$moment['name'],
            'minutes' => $moment['hour'] * 60,
            'weather' => $weather,
            'label' => ucfirst($weather).' '.$moment['name'],
            'alt' => 'Yosemite Valley in '.$weather.' at '.$moment['hour'].':00, AI-edited example',
        ], [
            ['name' => 'night', 'hour' => 0],
            ['name' => 'morning', 'hour' => 7],
            ['name' => 'day', 'hour' => 12],
            ['name' => 'evening', 'hour' => 19],
        ]), ['rain', 'snow', 'fog', 'storm'])),
    ],
    'promptExamples' => [
        [
            'title' => 'Sky garden',
            'prompt' => 'Turn the bay into a sky garden. Keep the bridge. Let koi swim through clouds, with giant water lilies and floating islands.',
            'file' => 'bridge-wild-17',
            'alt' => 'Golden Gate Bridge above clouds, with floating koi and flower-covered islands, AI-edited example',
        ],
        [
            'title' => 'Luminous bay',
            'prompt' => 'Make the bay bioluminescent after dark. Keep the bridge. Let enormous whales swim beneath the surface, leaving trails of blue light.',
            'file' => 'bridge-prompt-luminous-bay',
            'alt' => 'Golden Gate Bridge over a glowing blue bay with whales beneath the surface, AI-edited example',
        ],
        [
            'title' => 'Cut-paper world',
            'prompt' => 'Make this a hand-cut paper diorama. Keep the bridge’s shape, and build the hills, water, and clouds from colored paper.',
            'file' => 'bridge-prompt-paper-world',
            'alt' => 'Golden Gate Bridge and bay made from layered colored paper, AI-edited example',
        ],
        [
            'title' => 'Desert crossing',
            'prompt' => 'Replace the bay with golden dunes and a winding turquoise river. Keep the bridge, with sunset light and sand drifting through the air.',
            'file' => 'bridge-prompt-desert-crossing',
            'alt' => 'Golden Gate Bridge crossing golden desert dunes beside a turquoise river, AI-edited example',
        ],
    ],
    'photoCredits' => [
        ['picture' => 'Bridge', 'author' => 'Edgar Chaparro', 'source' => 'https://commons.wikimedia.org/wiki/File:Golden_Gate_Bridge_in_sunlight_(Unsplash).jpg', 'license' => 'CC0', 'licenseUrl' => 'https://creativecommons.org/publicdomain/zero/1.0/', 'downloaded' => '2026-10-05'],
        ['picture' => 'Yosemite Valley', 'author' => 'NPS Photo / C. Jacoby', 'source' => 'https://www.nps.gov/places/000/tunnel-view.htm', 'license' => 'public domain', 'licenseUrl' => 'https://www.nps.gov/aboutus/disclaimer.htm', 'downloaded' => '2026-10-05'],
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
