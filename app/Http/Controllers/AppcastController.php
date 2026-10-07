<?php

namespace App\Http\Controllers;

use App\AppcastFeed;
use Illuminate\Http\Response;

class AppcastController extends Controller
{
    public function __invoke(AppcastFeed $appcastFeed): Response
    {
        return response($appcastFeed->body(), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
