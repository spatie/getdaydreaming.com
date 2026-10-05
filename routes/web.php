<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome', [
    'moments' => [
        ['key' => 'morning', 'label' => 'Morning', 'time' => '07:00', 'weather' => 'Clear skies', 'alt' => 'An illustrated mountain landscape in soft morning light', 'picture' => null, 'sources' => []],
        ['key' => 'rain', 'label' => 'Rain', 'time' => '12:00', 'weather' => 'An afternoon shower', 'alt' => 'The same illustrated mountain landscape under a rainy sky', 'picture' => null, 'sources' => []],
        ['key' => 'golden', 'label' => 'Golden hour', 'time' => '19:00', 'weather' => 'The last of the sun', 'alt' => 'The same illustrated mountain landscape in warm evening light', 'picture' => null, 'sources' => []],
        ['key' => 'night', 'label' => 'Night', 'time' => '23:00', 'weather' => 'A quiet, clear night', 'alt' => 'The same illustrated mountain landscape at night under the moon and stars', 'picture' => null, 'sources' => []],
    ],
])->name('home');
