<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Tighten\Ziggy\Ziggy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        if (app()->environment(['local', 'staging', 'development'])) {
            Log::info('Mail is set to sandbox');
            Mail::alwaysTo('sandbox@inspireonline.co.za'); // O@BczDAVgrz2{89e
        }

        Inertia::share([
            'ziggy' => function () {
                return (new Ziggy())->toArray();
            },
        ]);
    }
}
