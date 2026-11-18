<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rappels d'échéance d'abonnement aux patrons, chaque matin (heure d'Abidjan = UTC)
Schedule::command('subscriptions:remind')->dailyAt('09:00')->withoutOverlapping();
