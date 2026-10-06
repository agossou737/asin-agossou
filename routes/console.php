<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rappel aux administrateurs des demandes en attente / en cours : chaque jour a 8h00 et 16h00
// (fuseau APP_TIMEZONE). Necessite la tache cron `php artisan schedule:run` (voir README).
Schedule::command('demandes:rappel-admin')
    ->dailyAt('08:00')
    ->timezone(config('app.timezone'))
    ->name('rappel-admin-08h')
    ->withoutOverlapping();

Schedule::command('demandes:rappel-admin')
    ->dailyAt('16:00')
    ->timezone(config('app.timezone'))
    ->name('rappel-admin-16h')
    ->withoutOverlapping();
