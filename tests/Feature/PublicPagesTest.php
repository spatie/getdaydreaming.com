<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_linked_from_the_footer_without_sessions(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('changelog').'"', false)
            ->assertSee('href="'.route('support').'"', false)
            ->assertSee('href="'.route('privacy').'"', false)
            ->assertSee('href="https://github.com/spatie/daydreaming-app"', false)
            ->assertSee('aria-label="Daydreaming on GitHub" title="Daydreaming on GitHub"', false)
            ->assertDontSee('<span>GitHub</span>', false)
            ->assertSeeText('Download soon')
            ->assertDontSee('href="'.route('download').'"', false);

        $this->get(route('support'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeText('support@spatie.be')
            ->assertSeeText('Submit a Prompt')
            ->assertSeeText('Kruikstraat 22, Box 12')
            ->assertSee('https://spatie.be/open-source/postcards')
            ->assertSee('href="'.route('privacy').'"', false);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeText('90 days')
            ->assertSeeText('macOS Keychain')
            ->assertSeeText('Feature requests')
            ->assertSeeText('Codex desktop app');

        $this->get(route('changelog'))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeText('No public releases yet.');

        $this->get(route('download'))->assertNotFound();
    }

    public function test_a_signed_feed_release_supplies_changelog_and_download(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('appcast.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle">
  <channel>
    <item>
      <title>Daydreaming 0.1.0</title>
      <pubDate>Tue, 06 Oct 2026 18:00:00 +0000</pubDate>
      <sparkle:version>3</sparkle:version>
      <sparkle:shortVersionString>0.1.0</sparkle:shortVersionString>
      <description><![CDATA[<p>First public release.</p><script>alert('bad')</script>]]></description>
      <enclosure url="https://getdaydreaming.com/releases/Daydreaming-0.1.0-3.dmg" />
    </item>
  </channel>
</rss>
XML);

        $this->get(route('changelog'))
            ->assertOk()
            ->assertSeeText('Version 0.1.0')
            ->assertSeeText('First public release.')
            ->assertDontSee('<script>', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('download').'"', false)
            ->assertSeeText('Download Daydreaming');

        $this->get(route('download'))
            ->assertRedirect('https://getdaydreaming.com/releases/Daydreaming-0.1.0-3.dmg');
    }

    public function test_download_ignores_an_enclosure_outside_the_release_directory(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('appcast.xml', <<<'XML'
<rss version="2.0" xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle">
  <channel>
    <item>
      <title>Bad release</title>
      <sparkle:version>3</sparkle:version>
      <sparkle:shortVersionString>0.1.0</sparkle:shortVersionString>
      <enclosure url="https://example.com/release.dmg" />
    </item>
    <item>
      <title>Wrong port</title>
      <sparkle:version>4</sparkle:version>
      <sparkle:shortVersionString>0.1.1</sparkle:shortVersionString>
      <enclosure url="https://getdaydreaming.com:8443/releases/Daydreaming-0.1.1.dmg" />
    </item>
  </channel>
</rss>
XML);

        $this->get(route('download'))->assertNotFound();
        $this->get(route('changelog'))->assertDontSeeText('Bad release');
        $this->get(route('changelog'))->assertDontSeeText('Wrong port');
    }
}
