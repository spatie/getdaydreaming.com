<?php

namespace App\Http\Controllers\Api;

use App\ApprovedReleaseUrl;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReleaseArtifactsController extends Controller
{
    public function __invoke(Request $request, ApprovedReleaseUrl $approvedReleaseUrl): JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => 'Expected application/json.'], 415);
        }

        $allowedFields = ['filename', 'url', 'sha256', 'size', 'version', 'build'];

        if (array_diff(array_keys($request->json()->all()), $allowedFields) !== []) {
            return response()->json(['message' => 'The artifact contains unknown fields.'], 422);
        }

        $data = $request->validate([
            'filename' => ['required', 'string', 'max:128', 'regex:/\ADaydreaming-\d+\.\d+\.\d+-[1-9]\d*\.(?:dmg|zip)\z/'],
            'url' => ['required', 'url', 'max:2048'],
            'sha256' => ['required', 'string', 'regex:/\A[a-fA-F0-9]{64}\z/'],
            'size' => ['required', 'integer', 'min:1', 'max:2147483648'],
            'version' => ['required', 'string', 'max:32', 'regex:/\A\d+\.\d+\.\d+\z/'],
            'build' => ['required', 'integer', 'min:1'],
        ]);

        $expectedPrefix = "Daydreaming-{$data['version']}-{$data['build']}";

        if (! in_array($data['filename'], ["{$expectedPrefix}.dmg", "{$expectedPrefix}.zip"], true)) {
            return response()->json(['message' => 'The filename does not match the version and build.'], 422);
        }

        if (! is_string(config('services.release.object_base_url')) || config('services.release.object_base_url') === '') {
            return response()->json(['message' => 'Release storage is not configured.'], 503);
        }

        if (! $approvedReleaseUrl->matches($data['filename'], $data['url'])) {
            return response()->json(['message' => 'The artifact URL is outside the approved release location.'], 422);
        }

        $data['size'] = (int) $data['size'];
        $data['build'] = (int) $data['build'];
        $data['sha256'] = strtolower($data['sha256']);
        $existing = DB::table('release_artifacts')->where('filename', $data['filename'])->first();

        if ($existing !== null) {
            return $this->existingResponse($existing, $data);
        }

        try {
            $head = Http::withoutRedirecting()->timeout(10)->head($data['url']);
        } catch (ConnectionException) {
            return response()->json(['message' => 'The artifact is not reachable.'], 422);
        }

        if (! $head->successful() || $head->header('Content-Length') !== (string) $data['size']) {
            return response()->json(['message' => 'The artifact is unavailable or has the wrong size.'], 422);
        }

        $inserted = DB::table('release_artifacts')->insertOrIgnore([
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 1) {
            return response()->json(['filename' => $data['filename'], 'sha256' => $data['sha256']], 201);
        }

        $existing = DB::table('release_artifacts')->where('filename', $data['filename'])->first();

        return $this->existingResponse($existing, $data);
    }

    /** @param array{filename: string, url: string, sha256: string, size: int, version: string, build: int} $data */
    private function existingResponse(?object $existing, array $data): JsonResponse
    {
        if ($existing !== null && $existing->url === $data['url']
            && hash_equals($existing->sha256, $data['sha256'])
            && (int) $existing->size === $data['size']
            && $existing->version === $data['version']
            && (int) $existing->build === $data['build']) {
            return response()->json(['filename' => $data['filename'], 'sha256' => $data['sha256']]);
        }

        return response()->json(['message' => 'This release filename is already registered with different contents.'], 409);
    }
}
