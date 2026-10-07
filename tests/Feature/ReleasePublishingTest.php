<?php

namespace Tests\Feature;

use App\SignedAppcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReleasePublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.release.token', str_repeat('t', 64));
        config()->set('services.release.object_base_url', 'https://objects.example.test/daydreaming');
    }

    public function test_actual_empty_appcast_has_a_valid_sparkle_signature(): void
    {
        $body = file_get_contents(resource_path('appcast.xml'));

        $this->assertTrue(app(SignedAppcast::class)->isValid($body));
        $this->assertFalse(app(SignedAppcast::class)->isValid(str_replace('Daydreaming updates.', 'Changed updates.', $body)));
        $this->assertFalse(app(SignedAppcast::class)->isValid(substr($body, 0, -1).'x'));
    }

    public function test_release_uploads_require_a_configured_bearer_token_and_small_bodies(): void
    {
        $this->postJson(route('releaseArtifacts.store'), $this->artifact())->assertUnauthorized();

        $this->withToken('wrong-token')
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertUnauthorized();

        config()->set('services.release.token', null);

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertUnauthorized();

        config()->set('services.release.token', str_repeat('t', 64));

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), [...$this->artifact(), 'extra' => str_repeat('x', 2048)])
            ->assertStatus(413);

        config()->set('services.release.object_base_url', null);

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertStatus(503);
    }

    public function test_artifacts_are_reachable_immutable_and_redirect_from_the_canonical_site(): void
    {
        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1234'])]);

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertCreated()
            ->assertJsonPath('filename', 'Daydreaming-0.1.0-3.dmg');

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertOk();

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), [...$this->artifact(), 'sha256' => str_repeat('b', 64)])
            ->assertStatus(409);

        $this->get(route('releaseArtifacts.download', 'Daydreaming-0.1.0-3.dmg'))
            ->assertRedirect('https://objects.example.test/daydreaming/Daydreaming-0.1.0-3.dmg')
            ->assertHeaderMissing('Set-Cookie');

        $this->call('HEAD', route('releaseArtifacts.download', 'Daydreaming-0.1.0-3.dmg'))
            ->assertRedirect('https://objects.example.test/daydreaming/Daydreaming-0.1.0-3.dmg');

        $zip = [
            ...$this->artifact(),
            'filename' => 'Daydreaming-0.1.0-3.zip',
            'url' => 'https://objects.example.test/daydreaming/Daydreaming-0.1.0-3.zip',
        ];

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $zip)
            ->assertCreated();

        $this->call('HEAD', route('releaseArtifacts.download', 'Daydreaming-0.1.0-3.zip'))
            ->assertRedirect($zip['url']);

        config()->set('services.release.object_base_url', 'https://other.example.test/releases');
        $this->get(route('releaseArtifacts.download', 'Daydreaming-0.1.0-3.dmg'))->assertNotFound();

        $this->assertDatabaseCount('release_artifacts', 2);
    }

    public function test_artifacts_reject_other_locations_wrong_names_and_wrong_remote_sizes(): void
    {
        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1'])]);

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), [...$this->artifact(), 'url' => 'https://evil.example/Daydreaming-0.1.0-3.dmg'])
            ->assertUnprocessable();

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), [...$this->artifact(), 'filename' => 'Daydreaming-0.1.0-4.dmg'])
            ->assertUnprocessable();

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertUnprocessable();

        $this->assertDatabaseCount('release_artifacts', 0);
        $this->get(route('releaseArtifacts.download', 'Daydreaming-0.1.0-3.dmg'))->assertNotFound();
    }

    public function test_signed_appcast_promotion_requires_staged_artifacts_and_preserves_exact_bytes(): void
    {
        $feed = $this->signedFeed();

        $this->postAppcast($feed)->assertUnprocessable();

        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1234'])]);

        $this->withToken(str_repeat('t', 64))
            ->postJson(route('releaseArtifacts.store'), $this->artifact())
            ->assertCreated();

        $this->postAppcast($feed)
            ->assertStatus(202)
            ->assertJsonPath('version', '0.1.0')
            ->assertJsonPath('build', 3);

        $this->get(route('appcast'))->assertOk()->assertContent($feed);
        $this->get(route('changelog'))->assertOk()->assertSeeText('First public release.');
        $this->get(route('download'))->assertRedirect('https://getdaydreaming.com/releases/Daydreaming-0.1.0-3.dmg');
        $this->postAppcast($feed)->assertOk();

        $this->assertSame(hash('sha256', $feed), DB::table('published_appcasts')->where('id', 1)->value('sha256'));
    }

    public function test_appcast_rejects_tampering_missing_signatures_and_changes_to_a_published_build(): void
    {
        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1234'])]);
        $this->withToken(str_repeat('t', 64))->postJson(route('releaseArtifacts.store'), $this->artifact())->assertCreated();

        $feed = $this->signedFeed();
        $this->postAppcast(str_replace('First public release.', 'Changed release.', $feed))->assertUnprocessable();
        $this->postAppcast(substr($feed, 0, strpos($feed, '<!-- sparkle-signatures:')))->assertUnprocessable();

        $this->postAppcast($feed)->assertStatus(202);
        $this->postAppcast($this->signedFeed('Different notes.'))->assertStatus(409);
    }

    public function test_appcast_rejects_noncanonical_urls_and_archive_size_mismatches(): void
    {
        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1234'])]);
        $this->withToken(str_repeat('t', 64))->postJson(route('releaseArtifacts.store'), $this->artifact())->assertCreated();

        $this->postAppcast($this->signedFeed(
            url: 'https://evil.example/Daydreaming-0.1.0-3.dmg',
        ))->assertUnprocessable();

        $this->postAppcast($this->signedFeed(length: '1'))->assertUnprocessable();
        $this->assertNull(DB::table('published_appcasts')->where('id', 1)->value('body'));
    }

    public function test_a_published_build_cannot_be_replaced_by_an_older_feed(): void
    {
        Http::fake(['objects.example.test/*' => Http::response('', 200, ['Content-Length' => '1234'])]);
        $this->withToken(str_repeat('t', 64))->postJson(route('releaseArtifacts.store'), $this->artifact())->assertCreated();
        DB::table('published_appcasts')->where('id', 1)->update(['latest_build' => 4]);

        $this->postAppcast($this->signedFeed())->assertStatus(409);
    }

    /** @return array{filename: string, url: string, sha256: string, size: int, version: string, build: int} */
    private function artifact(): array
    {
        return [
            'filename' => 'Daydreaming-0.1.0-3.dmg',
            'url' => 'https://objects.example.test/daydreaming/Daydreaming-0.1.0-3.dmg',
            'sha256' => str_repeat('a', 64),
            'size' => 1234,
            'version' => '0.1.0',
            'build' => 3,
        ];
    }

    private function signedFeed(
        string $notes = 'First public release.',
        string $url = 'https://getdaydreaming.com/releases/Daydreaming-0.1.0-3.dmg',
        string $length = '1234',
    ): string {
        $keyPair = sodium_crypto_sign_keypair();
        config()->set('services.appcast.public_key', base64_encode(sodium_crypto_sign_publickey($keyPair)));
        $archiveSignature = base64_encode(str_repeat('a', SODIUM_CRYPTO_SIGN_BYTES));

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle">
  <channel>
    <title>Daydreaming</title>
    <item>
      <title>Daydreaming 0.1.0</title>
      <pubDate>Tue, 06 Oct 2026 18:00:00 +0000</pubDate>
      <sparkle:version>3</sparkle:version>
      <sparkle:shortVersionString>0.1.0</sparkle:shortVersionString>
      <description><![CDATA[<p>{$notes}</p>]]></description>
      <enclosure url="{$url}" length="{$length}" sparkle:edSignature="{$archiveSignature}" />
    </item>
  </channel>
</rss>
XML;

        $signature = base64_encode(sodium_crypto_sign_detached($xml, sodium_crypto_sign_secretkey($keyPair)));

        return $xml."<!-- sparkle-signatures:\nedSignature: {$signature}\nlength: ".strlen($xml)."\n-->\n";
    }

    private function postAppcast(string $body): TestResponse
    {
        return $this->call('POST', route('releaseAppcast.store'), [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
            'HTTP_AUTHORIZATION' => 'Bearer '.str_repeat('t', 64),
        ], $body);
    }
}
