<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_homepage_introduces_daydreaming_without_a_dead_download_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Daydreaming for Mac');
        $response->assertSee('in step with the day.');
        $response->assertSee('Coming soon for Mac');
        $response->assertDontSee('Download now');
    }
}
