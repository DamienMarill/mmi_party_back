<?php

namespace App\Jobs;

use App\Models\Lootbox;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBoosterNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WebPushService $pushService): void
    {
        $payload = [
            'title' => '🎁 Nouveau Booster Disponible !',
            'body' => 'Un nouveau booster t\'attend dans MMI Party !',
            'icon' => '/assets/icons/icon-192x192.png',
            'badge' => '/assets/icons/badge-72x72.png',
            'data' => [
                'url' => '/loot',
            ],
        ];

        // Récupérer les users actifs (ont ouvert un booster dans les 3 derniers jours)
        $threeDaysAgo = Carbon::now()->subDays(3);

        $activeUserIds = Lootbox::where('created_at', '>=', $threeDaysAgo)
            ->distinct()
            ->pluck('user_id');

        // Envoyer par chunks pour éviter les problèmes de mémoire
        PushSubscription::whereIn('user_id', $activeUserIds)
            ->chunk(100, function ($subscriptions) use ($pushService, $payload) {
                $pushService->sendToSubscriptions($subscriptions, $payload);
            });
    }
}
