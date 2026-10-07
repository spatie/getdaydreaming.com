<?php

namespace App\Http\Controllers\Api;

use App\AppcastFeed;
use App\ApprovedReleaseUrl;
use App\Http\Controllers\Controller;
use App\SignedAppcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

class ReleaseAppcastController extends Controller
{
    public function __invoke(
        Request $request,
        SignedAppcast $signedAppcast,
        AppcastFeed $appcastFeed,
        ApprovedReleaseUrl $approvedReleaseUrl,
    ): JsonResponse {
        if ($request->getContentTypeFormat() !== 'xml' || $request->header('Content-Type') === null
            || ! str_starts_with(strtolower($request->header('Content-Type')), 'application/xml')) {
            return response()->json(['message' => 'Expected application/xml.'], 415);
        }

        $body = $request->getContent();

        if (! $signedAppcast->isValid($body)) {
            return response()->json(['message' => 'The appcast signature is invalid.'], 422);
        }

        if (stripos($body, '<!DOCTYPE') !== false || stripos($body, '<!ENTITY') !== false) {
            return response()->json(['message' => 'The appcast contains unsupported XML.'], 422);
        }

        $xml = @simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET);

        if ($xml === false || $xml->getName() !== 'rss' || ! isset($xml->channel)) {
            return response()->json(['message' => 'The appcast is not valid RSS.'], 422);
        }

        $items = $xml->channel->item;
        $releases = $appcastFeed->releases($body);

        if (count($items) < 1 || count($items) > 50 || count($items) !== count($releases)) {
            return response()->json(['message' => 'Every appcast item must be a valid release.'], 422);
        }

        $builds = [];
        $latestVersion = '';
        $latestBuild = 0;

        foreach ($items as $item) {
            $sparkle = $item->children('http://www.andymatuschak.org/xml-namespaces/sparkle');
            $version = trim((string) $sparkle->shortVersionString);
            $build = (int) $sparkle->version;
            $filename = "Daydreaming-{$version}-{$build}.dmg";
            $canonicalUrl = "https://getdaydreaming.com/releases/{$filename}";
            $enclosureUrl = trim((string) $item->enclosure['url']);
            $length = trim((string) $item->enclosure['length']);
            $signature = trim((string) $item->enclosure->attributes('http://www.andymatuschak.org/xml-namespaces/sparkle')['edSignature']);
            $signatureBytes = base64_decode($signature, true);

            if ($enclosureUrl !== $canonicalUrl || isset($builds[$build])
                || ! preg_match('/\A[1-9]\d*\z/', $length)
                || ! is_string($signatureBytes) || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES
                || trim(strip_tags((string) $item->description)) === '') {
                return response()->json(['message' => 'An appcast item has invalid release metadata.'], 422);
            }

            $artifact = DB::table('release_artifacts')->where('filename', $filename)->first();

            if ($artifact === null || $artifact->version !== $version || (int) $artifact->build !== $build
                || (int) $artifact->size !== (int) $length
                || ! $approvedReleaseUrl->matches($filename, $artifact->url)) {
                return response()->json(['message' => 'An appcast item has no matching staged artifact.'], 422);
            }

            $builds[$build] = true;

            if ($build > $latestBuild) {
                $latestBuild = $build;
                $latestVersion = $version;
            }
        }

        $digest = hash('sha256', $body);

        return DB::transaction(function () use ($body, $digest, $latestBuild, $latestVersion): JsonResponse {
            $published = DB::table('published_appcasts')->where('id', 1)->lockForUpdate()->first();

            if ($published === null || $latestBuild < (int) $published->latest_build) {
                return response()->json(['message' => 'The appcast is older than the published release.'], 409);
            }

            if ($latestBuild === (int) $published->latest_build) {
                if (is_string($published->sha256) && hash_equals($published->sha256, $digest)) {
                    return response()->json(['sha256' => $digest, 'version' => $latestVersion, 'build' => $latestBuild]);
                }

                return response()->json(['message' => 'This build already has a different published appcast.'], 409);
            }

            DB::table('published_appcasts')->where('id', 1)->update([
                'body' => $body,
                'sha256' => $digest,
                'latest_build' => $latestBuild,
                'updated_at' => now(),
            ]);

            return response()->json(['sha256' => $digest, 'version' => $latestVersion, 'build' => $latestBuild], 202);
        });
    }
}
