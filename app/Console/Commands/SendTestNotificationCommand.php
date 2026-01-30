<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendTestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {user_id? : The ID of the user to send the notification to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to a user';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\WebPushService $webPushService): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        } else {
            // Pick the first user with subscriptions
            $user = \App\Models\User::whereHas('pushSubscriptions')->first();
            if (!$user) {
                $this->error("No user with push subscriptions found.");
                return 1;
            }
            $this->info("No user specified, sending to user ID: {$user->id} ({$user->name})");
        }

        $payload = [
            'title' => 'Test Artisan Notification 🚀',
            'body' => 'Success! La commande artisan fonctionne correctement.',
            'icon' => '/assets/icons/icon-192x192.png',
            'data' => [
                'url' => config('app.front_url')
            ]
        ];

        $this->info("Sending notification to user {$user->id}...");

        $webPushService->sendToUser($user, $payload);

        $this->info("Notification sent (queued)!");

        return 0;
    }
}
