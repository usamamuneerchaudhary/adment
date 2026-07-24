<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Usamamuneerchaudhary\Adment\Models\AdDailyStat;

class AdPerformanceChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function getHeading(): ?string
    {
        return __('Impressions & clicks');
    }

    protected function getType(): string
    {
        return 'line';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->subDays(29)->startOfDay();

        $rows = AdDailyStat::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, SUM(impressions) as impressions, SUM(clicks) as clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy(fn (AdDailyStat $stat): string => $stat->date->toDateString());

        $labels = [];
        $impressions = [];
        $clicks = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $impressions[] = (int) ($rows->get($key)->impressions ?? 0);
            $clicks[] = (int) ($rows->get($key)->clicks ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('Impressions'),
                    'data' => $impressions,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => __('Clicks'),
                    'data' => $clicks,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
