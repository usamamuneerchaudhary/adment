<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Usamamuneerchaudhary\Adment\Models\Ad;

class TopAdsByCtrWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function table(Table $table): Table
    {
        $minImpressions = (int) config('adment.analytics.min_impressions_for_ctr_ranking', 100);
        [$start, $end] = $this->dateBounds();

        return $table
            ->heading(__('Top ads by CTR'))
            ->query(
                Ad::query()
                    ->where('impressions', '>=', $minImpressions)
                    ->orderByRaw('CASE WHEN impressions = 0 THEN 0 ELSE (clicked * 100.0 / impressions) END DESC')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Ad'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->label(__('Location'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('period_impressions')
                    ->label(__('Impressions'))
                    ->state(function (Ad $record) use ($start, $end): int {
                        return (int) $record->dailyStats()
                            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                            ->sum('impressions');
                    }),

                Tables\Columns\TextColumn::make('period_clicks')
                    ->label(__('Clicks'))
                    ->state(function (Ad $record) use ($start, $end): int {
                        return (int) $record->dailyStats()
                            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                            ->sum('clicks');
                    }),

                Tables\Columns\TextColumn::make('ctr')
                    ->label(__('CTR'))
                    ->state(fn (Ad $record): string => $record->ctr().'%'),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function dateBounds(): array
    {
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->subDays(29)->startOfDay();

        return [$start, $end];
    }
}
