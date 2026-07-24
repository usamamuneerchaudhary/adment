<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use Usamamuneerchaudhary\Adment\Contracts\ManagesAds;
use Usamamuneerchaudhary\Adment\Enums\AdDevice;
use Usamamuneerchaudhary\Adment\Enums\AdMediaType;
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

                    TextInput::make('order')
                        ->label(__('Weight'))
                        ->helperText(__('Higher weight means the ad is more likely to be shown in A/B rotation.'))
                        ->numeric()
                        ->default(1)
                        ->minValue(config('adment.validation.order_min', 0))
                        ->maxValue(config('adment.validation.order_max', 127))
                        ->required(),
                ]),

            Section::make(__('Schedule'))
                ->visible(fn (Get $get): bool => ! self::isAdType($get('ads_type'), AdType::GoogleAdsense))
                ->columns(2)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label(__('Starts at'))
                        ->helperText(__('Leave empty to start immediately.'))
                        ->native(false),

                    DateTimePicker::make('expired_at')
                        ->label(__('Ends at'))
                        ->default(now()->addMonth())
                        ->required(fn (Get $get): bool => ! self::isAdType($get('ads_type'), AdType::GoogleAdsense))
                        ->native(false),
                ]),

            Section::make(__('Targeting'))
                ->visible(fn (Get $get): bool => ! self::isAdType($get('ads_type'), AdType::GoogleAdsense))
                ->columns(2)
                ->schema([
                    TagsInput::make('target_countries')
                        ->label(__('Countries'))
                        ->helperText(__('ISO-3166 alpha-2 codes (e.g. US, GB). Empty = all countries. Resolved from CDN headers such as CF-IPCountry.'))
                        ->placeholder(__('US'))
                        ->nestedRecursiveRules(['string', 'size:2']),

                    CheckboxList::make('target_devices')
                        ->label(__('Devices'))
                        ->options(AdDevice::class)
                        ->helperText(__('Leave empty to target all devices.')),
                ]),

            Section::make(__('Google AdSense'))
                ->visible(fn (Get $get): bool => self::isAdType($get('ads_type'), AdType::GoogleAdsense))
                ->schema([
                    TextInput::make('google_adsense_slot_id')
                        ->label(__('Slot ID'))
                        ->helperText(__('The data-ad-slot value from your AdSense ad unit.'))
                        ->required(fn (Get $get): bool => self::isAdType($get('ads_type'), AdType::GoogleAdsense))
                        ->maxLength(255),
                ]),

            Section::make(__('Creative'))
                ->visible(fn (Get $get): bool => ! self::isAdType($get('ads_type'), AdType::GoogleAdsense))
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

                    Select::make('media_type')
                        ->label(__('Media type'))
                        ->options(AdMediaType::class)
                        ->default(AdMediaType::Image)
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpanFull(),

                    FileUpload::make('image')
                        ->label(fn (Get $get): string => match (true) {
                            self::isMediaType($get('media_type'), AdMediaType::Video) => __('Video (desktop / default)'),
                            self::isMediaType($get('media_type'), AdMediaType::Gif) => __('GIF (desktop / default)'),
                            default => __('Image (desktop / default)'),
                        })
                        ->acceptedFileTypes(fn (Get $get): array => self::acceptedFileTypes($get('media_type')))
                        ->disk(config('adment.media.disk', 'public'))
                        ->directory(config('adment.media.directory', 'ads'))
                        ->imageEditor(fn (Get $get): bool => self::isMediaType($get('media_type'), AdMediaType::Image)),

                    FileUpload::make('tablet_image')
                        ->label(fn (Get $get): string => match (true) {
                            self::isMediaType($get('media_type'), AdMediaType::Video) => __('Tablet video'),
                            self::isMediaType($get('media_type'), AdMediaType::Gif) => __('Tablet GIF'),
                            default => __('Tablet image'),
                        })
                        ->helperText(__('Falls back to the default creative when empty.'))
                        ->acceptedFileTypes(fn (Get $get): array => self::acceptedFileTypes($get('media_type')))
                        ->disk(config('adment.media.disk', 'public'))
                        ->directory(config('adment.media.directory', 'ads')),

                    FileUpload::make('mobile_image')
                        ->label(fn (Get $get): string => match (true) {
                            self::isMediaType($get('media_type'), AdMediaType::Video) => __('Mobile video'),
                            self::isMediaType($get('media_type'), AdMediaType::Gif) => __('Mobile GIF'),
                            default => __('Mobile image'),
                        })
                        ->helperText(__('Falls back to tablet, then default creative.'))
                        ->acceptedFileTypes(fn (Get $get): array => self::acceptedFileTypes($get('media_type')))
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
                    ->label(__('Creative'))
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

                Tables\Columns\TextColumn::make('media_type')
                    ->label(__('Media'))
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('impressions')
                    ->label(__('Impressions'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicked')
                    ->label(__('Clicks'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ctr')
                    ->label(__('CTR'))
                    ->state(fn (Ad $record): string => $record->ctr().'%')
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw(
                            'CASE WHEN impressions = 0 THEN 0 ELSE (clicked * 100.0 / impressions) END '.$direction
                        );
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('Starts'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Immediately'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expired_at')
                    ->label(__('Ends'))
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
                Tables\Filters\SelectFilter::make('media_type')
                    ->label(__('Media'))
                    ->options(AdMediaType::class),
                Tables\Filters\Filter::make('expired')
                    ->label(__('Expired'))
                    ->query(fn ($query) => $query
                        ->where('ads_type', '!=', AdType::GoogleAdsense)
                        ->where('expired_at', '<', now())),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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

    /** Determine whether a form ads_type value matches the given ad type. */
    protected static function isAdType(mixed $value, AdType $type): bool
    {
        return $value === $type || $value === $type->value;
    }

    /** Determine whether a form media_type value matches the given media type. */
    protected static function isMediaType(mixed $value, AdMediaType $type): bool
    {
        return $value === $type || $value === $type->value;
    }

    /**
     * Resolve accepted upload MIME types for the selected media type.
     *
     * @return list<string>
     */
    protected static function acceptedFileTypes(mixed $value): array
    {
        $type = $value instanceof AdMediaType
            ? $value
            : AdMediaType::tryFrom((string) $value) ?? AdMediaType::Image;

        return $type->acceptedFileTypes();
    }
}
