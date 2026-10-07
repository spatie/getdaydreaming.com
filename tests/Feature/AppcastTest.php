<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_appcast_is_a_public_valid_empty_feed(): void
    {
        $response = $this->get(route('appcast'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertHeaderMissing('Set-Cookie');

        $feed = simplexml_load_string($response->getContent());

        $this->assertNotFalse($feed);
        $this->assertSame('rss', $feed->getName());
        $this->assertSame('Daydreaming', (string) $feed->channel->title);
        $this->assertCount(0, $feed->channel->item);
        $this->assertSame(file_get_contents(resource_path('appcast.xml')), $response->getContent());
    }

    public function test_appcast_serves_storage_bytes_verbatim_when_a_release_feed_is_present(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('appcast.xml', 'signed-feed-bytes');

        $this->get(route('appcast'))
            ->assertOk()
            ->assertContent('signed-feed-bytes');
    }
}
