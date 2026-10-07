<?php

namespace App\Console\Commands;

use App\Models\Installation;
use App\Models\InstallReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('daydreaming:prune-install-metrics')]
#[Description('Delete expired installation reports and inactive installations')]
class PruneInstallMetrics extends Command
{
    public function handle(): int
    {
        $deletedReports = InstallReport::query()
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        $deletedInstallations = Installation::query()
            ->where('last_seen_at', '<', now()->subYear())
            ->delete();

        $this->info("Deleted {$deletedReports} reports and {$deletedInstallations} inactive installations.");

        return self::SUCCESS;
    }
}
