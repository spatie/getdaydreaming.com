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
            ->assertSeeText('up to two new wallpapers a day')
            ->assertSeeText('5 minutes to 24 hours')
            ->assertSeeText('daily safety limit from 1 to 288')
            ->assertSeeText('new installs start at 24')
            ->assertSeeText('A 5-minute schedule can request up to 288 new wallpapers a day')
            ->assertSeeText('OpenAI bills your account directly for each new wallpaper')
            ->assertSeeText('billed separately from ChatGPT subscriptions')
            ->assertSeeText('leaving the other one for later')
            ->assertSeeText('macOS Keychain')
            ->assertSeeText('MET Norway')
            ->assertSeeText('not generated app output')
            ->assertSeeText('App design preview with sample artwork')
            ->assertDontSee('Download')
            ->assertDontSee('$', false)
            ->assertDontSee('Buy now')
            ->assertDontSee('paddle', false)
            ->assertDontSee('<form', false)
            ->assertDontSee('—', false)
            ->assertDontSee('–', false);
    }
}
