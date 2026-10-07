<?php

namespace App\Http\Controllers;

use App\AppcastFeed;
use Illuminate\Http\RedirectResponse;

class DownloadController extends Controller
{
    public function __invoke(AppcastFeed $appcastFeed): RedirectResponse
    {
        $release = $appcastFeed->latestRelease();

        abort_if($release === null, 404);

        return redirect()->away($release['downloadUrl'])->header('Cache-Control', 'no-store');
    }
}
