<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ],
        ]);

        $this->webPush->setAutomaticPadding(false);
    }

    public function sendToUser(User $user, array $payload): void
    {
        foreach ($user->pushSubscriptions as $sub) {
            $this->queueNotification($sub, $payload);
        }

        $this->flush();
    }

    public function sendToSubscriptions($subscriptions, array $payload): void
    {
        foreach ($subscriptions as $sub) {
            $this->queueNotification($sub, $payload);
        }

        $this->flush();
    }

    private function queueNotification(PushSubscription $sub, array $payload): void
    {
        $subscription = Subscription::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->p256dh_key,
            'authToken' => $sub->auth_token,
            'contentEncoding' => $sub->content_encoding,
        ]);

        $this->webPush->queueNotification($subscription, json_encode($payload));
    }

    private function flush(): void
    {
        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                \Illuminate\Support\Facades\Log::info('Push sent successfully', [
                    'endpoint' => $report->getEndpoint()
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('Push failed', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                    'response_text' => $report->getResponse()->getBody()->getContents(),
                ]);

                // Supprimer les subscriptions expirées/invalides
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }
    }
}
