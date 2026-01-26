<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendBoosterNotificationJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote'); // Removed ->hourly() from Artisan::command as it's for scheduling

Schedule::command('inspire')->hourly();

// Notifier les utilisateurs pour les boosters (12:35, 18:35 par défaut)
$lootboxTimes = config('app.lootbox_times', ['12:35', '18:35']);

foreach ($lootboxTimes as $time) {
    Schedule::job(new SendBoosterNotificationJob())
        ->dailyAt($time)
        ->timezone('Europe/Paris')
        ->name("booster-notification-{$time}");
}
