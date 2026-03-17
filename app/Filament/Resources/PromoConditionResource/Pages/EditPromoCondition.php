<?php

namespace App\Filament\Resources\PromoConditionResource\Pages;

use App\Filament\Resources\PromoConditionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromoCondition extends EditRecord
{
    protected static string $resource = PromoConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
