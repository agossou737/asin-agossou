<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Anti-spam et anti-enumeration de NPI.
        RateLimiter::for('depot', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('consultation', fn (Request $r) => Limit::perMinute(30)->by($r->ip()));
        RateLimiter::for('admin-api', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));

        // Anti force brute : 5 essais par minute pour un meme email depuis une meme adresse.
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(mb_strtolower((string) $r->input('email')).'|'.$r->ip()));
    }
}
