<?php

namespace App\Actions;

use App\Models\Installation;
use App\Models\InstallReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RecordInstallReport
{
    /** @param array{token: string, app_version: string, app_build: string, macos_version: string, architecture: string, reported_at: string, schema_version: int} $payload */
    public function execute(array $payload): void
    {
        $tokenHash = hash_hmac('sha256', strtolower($payload['token']), config('app.key'));
        $receivedAt = now();
        $reportedAt = CarbonImmutable::parse($payload['reported_at']);

        DB::transaction(function () use ($payload, $tokenHash, $receivedAt, $reportedAt): void {
            $report = InstallReport::query()->createOrFirst(
                [
                    'token_hash' => $tokenHash,
                    'report_date' => $reportedAt->toDateString(),
                    'app_version' => $payload['app_version'],
                    'app_build' => $payload['app_build'],
                ],
                [
                    'macos_version' => $payload['macos_version'],
                    'architecture' => $payload['architecture'],
                    'reported_at' => $reportedAt,
                ],
            );

            if (! $report->wasRecentlyCreated) {
                return;
            }

            $installation = Installation::query()->firstOrCreate(
                ['token_hash' => $tokenHash],
                [
                    'app_version' => $payload['app_version'],
                    'app_build' => $payload['app_build'],
                    'macos_version' => $payload['macos_version'],
                    'architecture' => $payload['architecture'],
                    'first_seen_at' => $receivedAt,
                    'last_seen_at' => $receivedAt,
                    'last_reported_at' => $reportedAt,
                ],
            );

            if ($installation->wasRecentlyCreated) {
                return;
            }

            $installation->forceFill([
                'report_count' => $installation->report_count + 1,
                'last_seen_at' => $receivedAt,
            ]);

            if ($reportedAt->greaterThanOrEqualTo($installation->last_reported_at)) {
                $isUpgrade = $installation->app_version !== $payload['app_version'] || $installation->app_build !== $payload['app_build'];

                $installation->forceFill([
                    'app_version' => $payload['app_version'],
                    'app_build' => $payload['app_build'],
                    'macos_version' => $payload['macos_version'],
                    'architecture' => $payload['architecture'],
                    'upgrade_count' => $installation->upgrade_count + ($isUpgrade ? 1 : 0),
                    'last_reported_at' => $reportedAt,
                ]);
            }

            $installation->save();
        });
    }
}
