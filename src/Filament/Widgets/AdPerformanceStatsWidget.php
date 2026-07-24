<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Usamamuneerchaudhary\Adment\Models\Ad;
use Usamamuneerchaudhary\Adment\Models\AdDailyStat;

class AdPerformanceStatsWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        [$start, $end] = $this->dateBounds();

        $stats = AdDailyStat::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(impressions), 0) as impressions, COALESCE(SUM(clicks), 0) as clicks')
            ->first();

        $impressions = (int) ($stats->impressions ?? 0);
        $clicks = (int) ($stats->clicks ?? 0);
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        $activeAds = Ad::query()->displayable()->count();

        return [
            Stat::make(__('Impressions'), number_format($impressions))
                ->description(__('In selected range'))
                ->descriptionIcon('heroicon-m-eye')
                ->color('primary'),

            Stat::make(__('Clicks'), number_format($clicks))
                ->description(__('In selected range'))
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('success'),

            Stat::make(__('CTR'), $ctr.'%')
                ->description(__('Click-through rate'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($ctr >= 2 ? 'success' : ($ctr >= 1 ? 'warning' : 'gray')),

            Stat::make(__('Active ads'), number_format($activeAds))
                ->description(__('Currently displayable'))
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function dateBounds(): array
    {
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->subDays(29)->startOfDay();

        return [$start, $end];
    }
}
