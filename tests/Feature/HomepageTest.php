<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_homepage_explains_the_product_and_its_costs_before_release(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Daydreaming for Mac')
            ->assertSeeText('See your favorite wallpaper in a different light.')
            ->assertSeeText('Meet the dreamer.')
            ->assertSee('spatie-logo.svg')
            ->assertSee('href="https://runbloom.app"', false)
            ->assertSee('href="https://freeksrecords.com"', false)
            ->assertSee('href="https://x.com/freekmurze"', false)
            ->assertSee('href="https://www.linkedin.com/in/freek-van-der-herten-3487a7181"', false)
            ->assertDontSeeText('Coming soon for Mac')
            ->assertSeeText('macOS 26 or later')
            ->assertSeeText('your own OpenAI API key')
            ->assertSeeText('5 minutes to 24 hours')
            ->assertSeeText('daily safety limit from 1 to 288')
            ->assertSeeText('new installs start at 24')
            ->assertSeeText('A 5-minute schedule can request up to 288 new wallpapers a day')
            ->assertSeeText('OpenAI bills your account directly for each new wallpaper')
            ->assertSeeText('billed separately from ChatGPT subscriptions')
            ->assertSeeText('Daydreaming is free while in preview')
            ->assertSeeText('Create a wallpaper anytime')
            ->assertSeeText('Matching saved wallpapers are reused at no cost')
            ->assertSeeText('each billed by OpenAI')
            ->assertDontSeeText('Free and Pro')
            ->assertDontSeeText('Pro license')
            ->assertSeeText('macOS Keychain')
            ->assertSeeText('MET Norway')
            ->assertSee('href="'.route('credits').'"', false)
            ->assertDontSeeText('Edgar Chaparro')
            ->assertDontSeeText('NPS Photo / C. Jacoby')
            ->assertSeeText('fixed weather condition in Customize')
            ->assertSeeText('Not created by the Daydreaming app')
            ->assertSeeText('Luminous bay')
            ->assertSeeText('Cut-paper world')
            ->assertSeeText('Desert crossing')
            ->assertSee('favicon.svg')
            ->assertSee('site.webmanifest')
            ->assertSee('apple-touch-icon.png')
            ->assertSee('daydreaming-social.jpg')
            ->assertDontSee('Download')
            ->assertDontSee('$', false)
            ->assertDontSee('Buy now')
            ->assertDontSee('paddle', false)
            ->assertDontSee('<form', false)
            ->assertDontSee('—', false)
            ->assertDontSee('–', false);
    }

    public function test_homepage_is_publicly_cacheable_without_laravel_cookies(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=3600');
    }

    public function test_credits_page_preserves_weather_and_photo_attributions(): void
    {
        $response = $this->get(route('credits'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeText('MET Norway')
            ->assertSeeText('CC BY 4.0')
            ->assertSeeText('Edgar Chaparro')
            ->assertSeeText('CC0')
            ->assertSeeText('NPS Photo / C. Jacoby')
            ->assertSeeText('public domain');

        foreach ([
            'https://www.met.no/en',
            'https://creativecommons.org/licenses/by/4.0/',
            'https://commons.wikimedia.org/wiki/File:Golden_Gate_Bridge_in_sunlight_(Unsplash).jpg',
            'https://creativecommons.org/publicdomain/zero/1.0/',
            'https://www.nps.gov/places/000/tunnel-view.htm',
            'https://www.nps.gov/aboutus/disclaimer.htm',
        ] as $url) {
            $response->assertSee($url, false);
        }
    }

    public function test_homepage_has_a_full_day_storm_and_custom_prompt_examples(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $frames = $response->viewData('photoFrames');
        $clearFrames = array_values(array_filter($frames, fn (array $frame): bool => $frame['weather'] === 'clear'));
        $this->assertCount(24, $clearFrames);
        $this->assertCount(24, array_unique(array_column($clearFrames, 'file')));
        $this->assertSame(range(0, 23), array_map(fn (array $frame): int => $frame['minutes'] / 60, $clearFrames));
        foreach (['rain', 'snow', 'fog', 'storm'] as $weather) {
            $weatherFrames = array_values(array_filter($frames, fn (array $frame): bool => $frame['weather'] === $weather));
            $this->assertCount(4, $weatherFrames);
            $this->assertCount(4, array_unique(array_column($weatherFrames, 'file')));
            $this->assertSame(0, $weatherFrames[0]['minutes']);
            $this->assertSame(1140, $weatherFrames[3]['minutes']);
        }
        $promptExamples = $response->viewData('promptExamples');
        $this->assertCount(4, $promptExamples);
        $this->assertCount(4, array_unique(array_column($promptExamples, 'file')));
        foreach ($frames as $frame) {
            $this->assertFileExists(public_path('examples/'.$frame['file'].'-640.avif'));
            $this->assertLessThan(50000, filesize(public_path('examples/'.$frame['file'].'-640.avif')));
            $this->assertFileExists(public_path('examples/'.$frame['file'].'-1280.avif'));
            $this->assertFileExists(public_path('examples/'.$frame['file'].'-1280.webp'));
        }
        foreach ($promptExamples as $example) {
            foreach ([640, 960, 1280, 1536] as $width) {
                $this->assertFileExists(public_path('examples/'.$example['file'].'-'.$width.'.webp'));
            }
        }
        $yosemiteFrames = $response->viewData('yosemiteFrames');
        $this->assertCount(20, $yosemiteFrames);
        $this->assertCount(20, array_unique(array_column($yosemiteFrames, 'file')));
        foreach (['clear', 'rain', 'snow', 'fog', 'storm'] as $weather) {
            $this->assertCount(4, array_filter($yosemiteFrames, fn (array $frame): bool => $frame['weather'] === $weather));
        }
        foreach ($yosemiteFrames as $frame) {
            foreach ([640, 960, 1280, 1536] as $width) {
                $this->assertFileExists(public_path('examples/'.$frame['file'].'-'.$width.'.avif'));
                $this->assertFileExists(public_path('examples/'.$frame['file'].'-'.$width.'.webp'));
            }
            $this->assertLessThan(50000, filesize(public_path('examples/'.$frame['file'].'-640.avif')));
        }
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('icon-maskable-512.png'));
        $this->assertFileExists(public_path('daydreaming-social.jpg'));
        $this->assertFileExists(public_path('imac-frame.webp'));
        $this->assertFileExists(public_path('macbook-pro-frame.webp'));
        $response->assertSee('max="24" step="any"', false)
            ->assertSee('id="scene" data-weather="clear" data-picture="yosemite"', false)
            ->assertSee('id="bridge-scene" data-weather="clear" data-picture="bridge"', false)
            ->assertSee('aria-controls="scene bridge-scene"', false)
            ->assertSee('examples/yosemite-clear-day-1280.webp', false)
            ->assertSee('examples/bridge-noon-1280.webp', false)
            ->assertDontSee('data-picture-choice=', false)
            ->assertDontSee('original-photo', false)
            ->assertDontSee('id="scene-weather"', false)
            ->assertDontSee('id="wild-scrubber"', false)
            ->assertDontSee('data-play=', false)
            ->assertDontSee('class="app-window', false)
            ->assertDontSeeText('This demo blends example images')
            ->assertSeeText('Turn the bay into a sky garden')
            ->assertSeeText('each new wallpaper is a separate OpenAI request');
    }
}
