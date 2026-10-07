<?php

namespace Database\Seeders;

use App\Models\Installation;
use Illuminate\Database\Seeder;

class InstallationSeeder extends Seeder
{
    public function run(): void
    {
        Installation::factory()->count(10)->create();
    }
}
