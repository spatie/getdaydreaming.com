<?php

namespace App\Filament\Widgets;

use App\Models\Installation;
use Filament\Widgets\ChartWidget;

class VersionDistribution extends ChartWidget
{
    protected ?string $heading = 'Active installations by app version';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $versions = Installation::query()
            ->selectRaw('app_version, count(*) as total')
            ->where('last_seen_at', '>=', now()->subDays(30))
            ->groupBy('app_version')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Installations',
                'data' => $versions->pluck('total')->all(),
            ]],
            'labels' => $versions->pluck('app_version')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
