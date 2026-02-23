<?php

namespace App\Providers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        if (app()->environment(['local', 'staging', 'development'])) {
            log::info('Mail is set to sandbox');
            Mail::alwaysTo('sandbox@inspireonline.co.za'); // O@BczDAVgrz2{89e
        }

        $this->registerPolicies();
        //
    }
}
