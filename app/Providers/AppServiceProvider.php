<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
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
        Gate::define('viewPulse', function ($user) {
            return (bool) ($user?->is_admin ?? false);
        });

        $isSandboxMailEnvironment = app()->environment(['local', 'staging', 'development']);

        if ($isSandboxMailEnvironment) {
            Log::info('Mail is set to sandbox');
            Mail::alwaysTo('sandbox@inspireonline.co.za');

            $subjectPrefix = config('mail.audit_subject_prefix') ?: '[' . app()->environment() . ']';
            Event::listen(MessageSending::class, function (MessageSending $event) use ($subjectPrefix) {
                $subject = $event->message->getSubject() ?: '';
                if ($subjectPrefix && !str_starts_with($subject, $subjectPrefix)) {
                    $event->message->subject(trim($subjectPrefix . ' ' . $subject));
                }
            });
        } else {
            $auditBcc = config('mail.audit_bcc');

            if ($auditBcc) {
                Event::listen(MessageSending::class, function (MessageSending $event) use ($auditBcc) {
                    $event->message->addBcc($auditBcc);
                });
            }
        }

        Inertia::share([
            'ziggy' => function () {
                return (new Ziggy())->toArray();
            },
        ]);
    }
}
