<?php

namespace App\Filament\Resources\LootboxResource\Pages;

use App\Filament\Resources\LootboxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLootboxes extends ListRecords
{
    protected static string $resource = LootboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
