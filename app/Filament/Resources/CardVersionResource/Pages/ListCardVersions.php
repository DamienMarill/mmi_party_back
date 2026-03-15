<?php

namespace App\Filament\Resources\CardVersionResource\Pages;

use App\Filament\Resources\CardVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCardVersions extends ListRecords
{
    protected static string $resource = CardVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
