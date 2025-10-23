<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        //dd($exception);
        if ($exception instanceof InvalidSignatureException) {
            // Redirect to a friendly page or Inertia route
            // return Inertia::view('/front/welcome', [
            //     'message' => 'Your check-in link has expired. Please restart the process.'
            // ]);
            return redirect()->route('booking-questionnaire.index');
            // Or redirect to another route
            // return redirect()->route('front.booking-questionnaire.start')
            //     ->with('error', 'Your check-in link has expired. Please restart the process.');
        }

        return parent::render($request, $exception);
    }
}
