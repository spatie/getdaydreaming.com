<?php

namespace App;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;

class AppcastFeed
{
    public function body(): string
    {
        $published = DB::table('published_appcasts')->where('id', 1)->first(['body', 'sha256']);

        if (is_string($published?->body) && $published->body !== ''
            && is_string($published->sha256)
            && hash_equals($published->sha256, hash('sha256', $published->body))) {
            return $published->body;
        }

        $disk = Storage::disk(config('services.appcast.disk'));
        $path = config('services.appcast.path');
        $body = $disk->exists($path)
            ? $disk->get($path)
            : file_get_contents(resource_path('appcast.xml'));

        if (! is_string($body) || $body === '') {
            throw new RuntimeException('The signed appcast is unavailable.');
        }

        return $body;
    }

    /** @return array<int, array{
     *     version: string,
     *     build: int,
     *     title: string,
     *     notes: string,
     *     publishedAt: ?CarbonImmutable,
     *     downloadUrl: string
     * }> */
    public function releases(?string $body = null): array
    {
        $body ??= $this->body();

        if (stripos($body, '<!DOCTYPE') !== false || stripos($body, '<!ENTITY') !== false) {
            return [];
        }

        $xml = @simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET);

        if ($xml === false || $xml->getName() !== 'rss' || ! isset($xml->channel)) {
            return [];
        }

        $releases = [];

        foreach ($xml->channel->item as $item) {
            $sparkle = $item->children('http://www.andymatuschak.org/xml-namespaces/sparkle');
            $version = trim((string) $sparkle->shortVersionString);
            $build = trim((string) $sparkle->version);
            $downloadUrl = trim((string) $item->enclosure['url']);
            $url = parse_url($downloadUrl);

            if (! is_array($url)) {
                continue;
            }

            $path = $url['path'] ?? '';
            $filename = str_starts_with($path, '/releases/') ? substr($path, strlen('/releases/')) : '';

            if (! preg_match('/^\d+\.\d+\.\d+$/', $version) || ! preg_match('/^[1-9]\d*$/', $build)) {
                continue;
            }

            if (($url['scheme'] ?? null) !== 'https' || ($url['host'] ?? null) !== 'getdaydreaming.com'
                || isset($url['user']) || isset($url['pass']) || isset($url['port']) || isset($url['query']) || isset($url['fragment'])) {
                continue;
            }

            if ($filename === '' || str_contains($filename, '/') || ! str_ends_with($filename, '.dmg')) {
                continue;
            }

            $rawNotes = preg_replace('/<\/(?:p|li|h[1-6])>/i', "\n", (string) $item->description);
            $notes = trim(html_entity_decode(strip_tags($rawNotes ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $publishedAt = strtotime((string) $item->pubDate);

            $releases[] = [
                'version' => $version,
                'build' => (int) $build,
                'title' => trim((string) $item->title) ?: "Daydreaming {$version}",
                'notes' => $notes,
                'publishedAt' => $publishedAt === false ? null : CarbonImmutable::createFromTimestamp($publishedAt),
                'downloadUrl' => $downloadUrl,
            ];
        }

        usort($releases, fn (array $first, array $second): int => $second['build'] <=> $first['build']);

        return $releases;
    }

    /** @return array{
     *     version: string,
     *     build: int,
     *     title: string,
     *     notes: string,
     *     publishedAt: ?CarbonImmutable,
     *     downloadUrl: string
     * }|null */
    public function latestRelease(): ?array
    {
        return $this->releases()[0] ?? null;
    }
}
