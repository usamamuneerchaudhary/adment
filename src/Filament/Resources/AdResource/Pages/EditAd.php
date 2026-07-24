<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Filament\Resources\AdResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Usamamuneerchaudhary\Adment\Filament\Resources\AdResource;

class EditAd extends EditRecord
{
    protected static string $resource = AdResource::class;

    /** Return the delete action shown in the edit page header. */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
