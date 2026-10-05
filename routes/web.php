<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome', [
    'photoFrames' => [
        ['key' => 'morning', 'file' => 'bridge-morning', 'minutes' => 420, 'weather' => 'clear', 'label' => 'Clear morning', 'alt' => 'Golden Gate Bridge in warm dawn light, AI-edited example'],
        ['key' => 'day', 'file' => 'bridge-day', 'minutes' => 780, 'weather' => 'clear', 'label' => 'Clear afternoon', 'alt' => 'Original photograph of the Golden Gate Bridge over a turquoise bay in daylight'],
        ['key' => 'evening', 'file' => 'bridge-evening', 'minutes' => 1140, 'weather' => 'clear', 'label' => 'Clear evening', 'alt' => 'Golden Gate Bridge in golden evening light, AI-edited example'],
        ['key' => 'night', 'file' => 'bridge-night', 'minutes' => 1380, 'weather' => 'clear', 'label' => 'Clear night', 'alt' => 'Golden Gate Bridge under moonlight with bridge lights reflected in the bay, AI-edited example'],
        ['key' => 'rain', 'file' => 'bridge-rain', 'minutes' => 840, 'weather' => 'rain', 'label' => 'Rainy afternoon', 'alt' => 'Golden Gate Bridge with wet roads and afternoon rain, AI-edited example'],
        ['key' => 'snow', 'file' => 'bridge-snow', 'minutes' => 840, 'weather' => 'snow', 'label' => 'Snowy afternoon', 'alt' => 'Golden Gate Bridge with an imagined light afternoon snowfall, AI-edited example'],
        ['key' => 'fog', 'file' => 'bridge-fog', 'minutes' => 420, 'weather' => 'fog', 'label' => 'Foggy morning', 'alt' => 'Golden Gate Bridge emerging from morning sea mist, AI-edited example'],
    ],
    'photoExamples' => [
        ['name' => 'Yosemite Valley', 'height' => 720, 'original' => 'yosemite-original', 'edited' => 'yosemite-evening', 'label' => 'Golden hour', 'alt' => 'Yosemite Valley with warm sunset light on the granite cliffs, AI-edited example'],
    ],
    'photoCredits' => [
        ['author' => 'Edgar Chaparro', 'source' => 'https://commons.wikimedia.org/wiki/File:Golden_Gate_Bridge_in_sunlight_(Unsplash).jpg', 'license' => 'CC0', 'downloaded' => '2026-10-05'],
        ['author' => 'NPS Photo / C. Jacoby', 'source' => 'https://www.nps.gov/places/000/tunnel-view.htm', 'license' => 'public domain', 'downloaded' => '2026-10-05'],
    ],
])->name('home');
