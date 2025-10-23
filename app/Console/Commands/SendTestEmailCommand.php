<?php

namespace App\Console\Commands;

use App\Jobs\SendNewBookingMail;
use App\Models\StudentsInfo;
use App\Models\User;
use App\Models\Lookups;
use Illuminate\Console\Command;
use App\Traits\EmailTrait;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Support\Facades\Log;

class SendTestEmailCommand extends Command
{
    use EmailTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-test {email}';
    // php artisan email:send-test longjsjs@gmail.com

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email using EmailTrait';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $result = $this->sendTestEmail($email);
        $this->info($result);

        $email = $this->argument('email');
        $result = $this->sendTestEmailAttachement($email);
        $this->info($result);
    }
}
