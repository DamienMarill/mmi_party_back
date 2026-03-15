<?php

namespace App\Filament\Resources\CardVersionResource\Pages;

use App\Filament\Resources\CardVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCardVersion extends EditRecord
{
    protected static string $resource = CardVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
