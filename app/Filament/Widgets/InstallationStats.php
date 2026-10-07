<?php

namespace App\Filament\Widgets;

use App\Models\Installation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstallationStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        return [
            Stat::make('Installations seen', number_format(Installation::query()->count()))
                ->description('Distinct installation tokens'),
            Stat::make('First seen in 7 days', number_format(Installation::query()->where('first_seen_at', '>=', now()->subDays(7))->count())),
            Stat::make('First seen in 30 days', number_format(Installation::query()->where('first_seen_at', '>=', now()->subDays(30))->count())),
            Stat::make('Active in 7 days', number_format(Installation::query()->where('last_seen_at', '>=', now()->subDays(7))->count())),
            Stat::make('Active in 30 days', number_format(Installation::query()->where('last_seen_at', '>=', now()->subDays(30))->count())),
            Stat::make('Installations upgraded', number_format(Installation::query()->where('upgrade_count', '>', 0)->count()))
                ->description(number_format(Installation::query()->sum('upgrade_count')).' version changes reported'),
        ];
    }
}
