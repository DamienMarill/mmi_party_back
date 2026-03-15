<?php

namespace App\Filament\Resources\MmiiResource\Pages;

use App\Filament\Resources\MmiiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMmiis extends ListRecords
{
    protected static string $resource = MmiiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
