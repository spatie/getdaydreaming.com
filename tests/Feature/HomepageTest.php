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
            ->assertSeeText('Change the atmosphere.')
            ->assertSeeText('Coming soon for Mac')
            ->assertSeeText('macOS 26 and later')
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
            ->assertSeeText('fixed weather condition in Customize')
            ->assertSeeText('Not created by the Daydreaming app')
            ->assertSeeText('App design preview. Example wallpaper edited for this website')
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

    public function test_homepage_has_a_full_day_storm_and_custom_prompt_examples(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $frames = $response->viewData('photoFrames');
        $clearFrames = array_values(array_filter($frames, fn (array $frame): bool => $frame['weather'] === 'clear'));
        $this->assertCount(24, $clearFrames);
        $this->assertCount(24, array_unique(array_column($clearFrames, 'file')));
        $this->assertSame(range(0, 23), array_map(fn (array $frame): int => $frame['minutes'] / 60, $clearFrames));
        $this->assertContains('storm', array_column($frames, 'weather'));
        $wildFrames = $response->viewData('wildFrames');
        $this->assertCount(24, $wildFrames);
        $this->assertCount(24, array_unique(array_column($wildFrames, 'file')));
        foreach ([...$frames, ...$wildFrames] as $frame) {
            $this->assertFileExists(public_path('examples/'.$frame['file'].'-1280.avif'));
            $this->assertFileExists(public_path('examples/'.$frame['file'].'-1280.webp'));
        }
        $response->assertSee('max="23" step="1"', false)
            ->assertSeeText('Turn the bay into a sky garden')
            ->assertSeeText('each new wallpaper is a separate OpenAI request');
    }
}
