<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource;

class ListAds extends ListRecords
{
    protected static string $resource = AdResource::class;

    /** Return the create action shown in the list page header. */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
