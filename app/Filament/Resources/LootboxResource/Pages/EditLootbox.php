<?php

namespace App\Filament\Resources\LootboxResource\Pages;

use App\Filament\Resources\LootboxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLootbox extends EditRecord
{
    protected static string $resource = LootboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
