<?php

namespace App\Http\Controllers;

use App\Services\PromoService;

class PromoController extends Controller
{
    private PromoService $promoService;

    public function __construct(PromoService $promoService)
    {
        $this->promoService = $promoService;
    }

    /**
     * Check and unlock eligible promo cards for the authenticated user.
     */
    public function check()
    {
        $user = auth()->user();

        $unlocked = $this->promoService->checkAndUnlock($user);

        return response()->json([
            'unlocked' => $unlocked->values(),
            'count' => $unlocked->count(),
        ]);
    }
}
