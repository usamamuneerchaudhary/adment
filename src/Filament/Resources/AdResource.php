<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Enums\AdStatus;
use Usamamuneerchaudhary\Adment\Enums\AdType;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages;
use Usamamuneerchaudhary\Adment\Models\Ad;

class AdResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    /** Resolve the Eloquent model class used by this resource. */
    public static function getModel(): string
    {
        return config('adment.models.ad', Ad::class);
    }

    /** Return the navigation group for the ads resource. */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('adment.panel.navigation_group');
    }

    /** Return the navigation sort order for the ads resource. */
    public static function getNavigationSort(): ?int
    {
        return config('adment.panel.navigation_sort');
    }

    /** Return the singular model label. */
    public static function getModelLabel(): string
    {
        return __('Ad');
    }

    /** Return the plural model label. */
    public static function getPluralModelLabel(): string
    {
        return __('Ads');
    }

    /** Build the create/edit form schema for an ad. */
    public static function form(Schema $schema): Schema
    {
        $locations = app(ManagesAds::class)->getLocations();

        return $schema->components([
            Section::make(__('General'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('key')
                        ->label(__('Key'))
                        ->helperText(__('Public identifier used in Blade components and the API.'))
                        ->default(fn (): string => Ad::generateUniqueKey())
                        ->required()
                        ->maxLength(120)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),

                    Select::make('ads_type')
                        ->label(__('Type'))
                        ->options(AdType::class)
                        ->default(AdType::Custom)
                        ->required()
                        ->live()
                        ->native(false),

                    Select::make('status')
                        ->label(__('Status'))
                        ->options(AdStatus::class)
                        ->default(AdStatus::Published)
                        ->required()
                        ->native(false),

                    Select::make('location')
                        ->label(__('Location'))
                        ->options($locations)
                        ->default('not_set')
                        ->visible(count($locations) > 1)
                        ->native(false),

                    Grid::make(2)->schema([
                        TextInput::make('order')
                            ->label(__('Order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(config('adment.validation.order_min', 0))
                            ->maxValue(config('adment.validation.order_max', 127))
                            ->required(),

                        DateTimePicker::make('expired_at')
                            ->label(__('Expires at'))
                            ->default(now()->addMonth())
                            ->required(fn (Get $get): bool => $get('ads_type') !== AdType::GoogleAdsense->value)
                            ->visible(fn (Get $get): bool => $get('ads_type') !== AdType::GoogleAdsense->value)
                            ->native(false),
                    ]),
                ]),

            Section::make(__('Google AdSense'))
                ->visible(fn (Get $get): bool => $get('ads_type') === AdType::GoogleAdsense->value)
                ->schema([
                    TextInput::make('google_adsense_slot_id')
                        ->label(__('Slot ID'))
                        ->helperText(__('The data-ad-slot value from your AdSense ad unit.'))
                        ->required(fn (Get $get): bool => $get('ads_type') === AdType::GoogleAdsense->value)
                        ->maxLength(255),
                ]),

            Section::make(__('Creative'))
                ->visible(fn (Get $get): bool => $get('ads_type') !== AdType::GoogleAdsense->value)
                ->columns(2)
                ->schema([
                    TextInput::make('url')
                        ->label(__('Destination URL'))
                        ->url()
                        ->maxLength(255)
                        ->columnSpan(1),

                    Toggle::make('open_in_new_tab')
                        ->label(__('Open in new tab'))
                        ->default(true)
                        ->inline(false),

                    FileUpload::make('image')
                        ->label(__('Image (desktop / default)'))
                        ->image()
                        ->disk(config('adment.media.disk', 'public'))
                        ->directory(config('adment.media.directory', 'ads'))
                        ->imageEditor(),

                    FileUpload::make('tablet_image')
                        ->label(__('Tablet image'))
                        ->helperText(__('Falls back to the default image when empty.'))
                        ->image()
                        ->disk(config('adment.media.disk', 'public'))
                        ->directory(config('adment.media.directory', 'ads')),

                    FileUpload::make('mobile_image')
                        ->label(__('Mobile image'))
                        ->helperText(__('Falls back to tablet, then default image.'))
                        ->image()
                        ->disk(config('adment.media.disk', 'public'))
                        ->directory(config('adment.media.directory', 'ads')),
                ]),
        ]);
    }

    /** Build the ads index table columns, filters, and actions. */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Image'))
                    ->disk(config('adment.media.disk', 'public'))
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label(__('Key'))
                    ->badge()
                    ->copyable()
                    ->copyMessage(__('Key copied'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('location')
                    ->label(__('Location'))
                    ->formatStateUsing(
                        fn (?string $state): string => app(ManagesAds::class)->getLocations()[$state] ?? (string) $state,
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('clicked')
                    ->label(__('Clicks'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label(__('Expires'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never'))
                    ->color(fn (Ad $record): ?string => $record->isExpired() ? 'danger' : null),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(AdStatus::class),
                Tables\Filters\SelectFilter::make('ads_type')
                    ->label(__('Type'))
                    ->options(AdType::class),
                Tables\Filters\Filter::make('expired')
                    ->label(__('Expired'))
                    ->query(fn ($query) => $query
                        ->where('ads_type', '!=', AdType::GoogleAdsense)
                        ->where('expired_at', '<', now())),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Register the list, create, and edit pages for this resource. */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
        ];
    }
}
