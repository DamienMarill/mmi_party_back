<?php

namespace App\Observers;

use App\Enums\CardTypes;
use App\Models\CardTemplate;

class CardTemplateObserver
{
    public function saving(CardTemplate $cardTemplate)
    {
        if ($cardTemplate->type !== CardTypes::STUDENT) {
            $cardTemplate->level = null;
            $cardTemplate->stats = null;
            $cardTemplate->shape = null;
        }
    }
}
