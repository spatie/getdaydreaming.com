<?php

namespace App\Http\Controllers;

use App\ApprovedReleaseUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReleaseDownloadController extends Controller
{
    public function __invoke(string $filename, ApprovedReleaseUrl $approvedReleaseUrl): RedirectResponse
    {
        $artifact = DB::table('release_artifacts')->where('filename', $filename)->first(['url']);

        abort_if($artifact === null || ! $approvedReleaseUrl->matches($filename, $artifact->url), 404);

        return redirect()->away($artifact->url)->header('Cache-Control', 'public, max-age=31536000, immutable');
    }
}
