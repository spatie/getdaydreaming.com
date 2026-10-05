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
}
