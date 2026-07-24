<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;
use Usamamuneerchaudhary\Adment\Filament\Widgets\AdPerformanceChartWidget;
use Usamamuneerchaudhary\Adment\Filament\Widgets\AdPerformanceStatsWidget;
use Usamamuneerchaudhary\Adment\Filament\Widgets\TopAdsByCtrWidget;

/**
 * @property-read Schema $form
 */
class AdAnalyticsDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** Return the navigation group for this analytics page. */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('adment.panel.navigation_group');
    }

    /** Return the sidebar sort order for this analytics page. */
    public static function getNavigationSort(): ?int
    {
        return ((int) config('adment.panel.navigation_sort', 50)) + 1;
    }

    /** Return the sidebar label for this analytics page. */
    public static function getNavigationLabel(): string
    {
        return __('Ad analytics');
    }

    /** Return the page title shown in the header. */
    public function getTitle(): string
    {
        return __('Ad analytics');
    }

    /** Prefill the date range filter to the last 30 days. */
    public function mount(): void
    {
        $this->startDate = now()->subDays(29)->toDateString();
        $this->endDate = now()->toDateString();

        $this->form->fill([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    /** Build the date range filter form. */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Date range'))
                    ->columns(2)
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('From'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state): void {
                                $this->startDate = $state;
                            })
                            ->native(false),

                        DatePicker::make('endDate')
                            ->label(__('To'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state): void {
                                $this->endDate = $state;
                            })
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFiltersFormContentComponent(),
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getFiltersFormContentComponent(): Component
    {
        return EmbeddedSchema::make('form');
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getWidgetsColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets()));
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getWidgets(): array
    {
        return [
            AdPerformanceStatsWidget::class,
            AdPerformanceChartWidget::class,
            TopAdsByCtrWidget::class,
        ];
    }
}
