<?php

namespace App\Http\Controllers;

use App\AppcastFeed;
use Illuminate\Http\Response;

class ChangelogController extends Controller
{
    public function __invoke(AppcastFeed $appcastFeed): Response
    {
        $cacheControl = app()->environment('local') ? 'no-store' : 'public, max-age=60, s-maxage=60';

        return response()->view('changelog', [
            'releases' => $appcastFeed->releases(),
        ], 200, ['Cache-Control' => $cacheControl]);
    }
}
