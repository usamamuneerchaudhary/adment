<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use UnitEnum;
use Usamamuneerchaudhary\Adment\Enums\AdsenseMode;
use Usamamuneerchaudhary\Adment\Rules\ValidAdsenseAutoSnippet;
use Usamamuneerchaudhary\Adment\Settings\AdsSettings;

/**
 * @property-read Schema $form
 */
class ManageAdsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Return the navigation group for this settings page. */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('adment.panel.navigation_group');
    }

    /** Return the sidebar label for this settings page. */
    public static function getNavigationLabel(): string
    {
        return __('Ads settings');
    }

    /** Return the page title shown in the header. */
    public function getTitle(): string
    {
        return __('Ads settings');
    }

    /** Prefill the form with the current AdSense and ads.txt settings. */
    public function mount(AdsSettings $settings): void
    {
        $this->form->fill([
            'mode' => $settings->mode()->value,
            'auto_ads_snippet' => $settings->autoAdsSnippet(),
            'unit_client_id' => $settings->unitClientId(),
            'ads_txt' => $this->currentAdsTxt(),
        ]);
    }

    /** Build the AdSense and ads.txt settings form schema. */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Google AdSense'))
                    ->description(__('Configure how AdSense scripts are injected into your public site.'))
                    ->schema([
                        Radio::make('mode')
                            ->label(__('Mode'))
                            ->options(AdsenseMode::class)
                            ->default(AdsenseMode::None->value)
                            ->required()
                            ->live(),

                        Textarea::make('auto_ads_snippet')
                            ->label(__('Auto Ads snippet'))
                            ->helperText(__('Paste the exact snippet from your AdSense account.'))
                            ->rows(4)
                            ->visible(fn (Get $get): bool => self::isAdsenseMode($get('mode'), AdsenseMode::Auto))
                            ->requiredIf('mode', AdsenseMode::Auto->value)
                            ->rules([new ValidAdsenseAutoSnippet]),

                        TextInput::make('unit_client_id')
                            ->label(__('Publisher client ID'))
                            ->placeholder('ca-pub-0000000000000000')
                            ->visible(fn (Get $get): bool => self::isAdsenseMode($get('mode'), AdsenseMode::Unit))
                            ->requiredIf('mode', AdsenseMode::Unit->value)
                            ->regex('/^ca-pub-\d{16}$/'),
                    ]),

                Section::make(__('ads.txt'))
                    ->description(__('Content is written to public/ads.txt so ad networks can verify your inventory.'))
                    ->collapsed()
                    ->schema([
                        Textarea::make('ads_txt')
                            ->label(__('ads.txt content'))
                            ->rows(6)
                            ->maxLength(20000),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->key('form-actions'),
            ]);
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save changes'))
                ->submit('save'),
        ];
    }

    /** Persist AdSense settings and write the public ads.txt file. */
    public function save(AdsSettings $settings): void
    {
        $state = $this->form->getState();

        $mode = $state['mode'] ?? AdsenseMode::None;
        if (! $mode instanceof AdsenseMode) {
            $mode = AdsenseMode::tryFrom((string) $mode) ?? AdsenseMode::None;
        }

        $settings->updateAdsense(
            mode: $mode,
            autoSnippet: $state['auto_ads_snippet'] ?? null,
            unitClientId: $state['unit_client_id'] ?? null,
        );

        $this->writeAdsTxt($state['ads_txt'] ?? null);

        Notification::make()
            ->title(__('Ads settings saved'))
            ->success()
            ->send();
    }

    /** Read the current public/ads.txt contents when the file exists. */
    protected function currentAdsTxt(): ?string
    {
        $path = public_path('ads.txt');

        return File::exists($path) ? File::get($path) : null;
    }

    /** Write or delete public/ads.txt based on the submitted content. */
    protected function writeAdsTxt(?string $content): void
    {
        $path = public_path('ads.txt');

        if ($content === null || trim($content) === '') {
            File::exists($path) && File::delete($path);

            return;
        }

        File::put($path, $content);
    }

    /** Determine whether a form mode value matches the given AdSense mode. */
    protected static function isAdsenseMode(mixed $value, AdsenseMode $mode): bool
    {
        return $value === $mode || $value === $mode->value;
    }
}
