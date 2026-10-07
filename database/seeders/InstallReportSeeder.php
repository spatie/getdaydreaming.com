<?php

namespace Database\Seeders;

use App\Models\Installation;
use App\Models\InstallReport;
use Illuminate\Database\Seeder;

class InstallReportSeeder extends Seeder
{
    public function run(): void
    {
        Installation::factory()->count(10)->create()->each(function (Installation $installation): void {
            $installation->reports()->create(
                InstallReport::factory()->make(['token_hash' => $installation->token_hash])->getAttributes(),
            );
        });
    }
}
