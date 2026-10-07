<?php

namespace App;

class ApprovedReleaseUrl
{
    public function matches(string $filename, string $url): bool
    {
        $baseUrl = config('services.release.object_base_url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            return false;
        }

        $baseParts = parse_url($baseUrl);

        if (! is_array($baseParts) || ($baseParts['scheme'] ?? null) !== 'https'
            || ! isset($baseParts['host']) || isset($baseParts['user']) || isset($baseParts['pass'])
            || isset($baseParts['port'])
            || isset($baseParts['query']) || isset($baseParts['fragment'])) {
            return false;
        }

        return hash_equals(rtrim($baseUrl, '/').'/'.$filename, $url);
    }
}
