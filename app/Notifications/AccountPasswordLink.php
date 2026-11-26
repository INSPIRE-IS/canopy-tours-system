<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

class AccountPasswordLink extends ResetPassword
{
    public const NO_AUDIT_COPY_HEADER = 'X-Canopy-No-Audit-Copy';

    public function __construct($token, private ?string $invitedBy = null)
    {
        parent::__construct($token);
    }

    protected function buildMailMessage($url): MailMessage
    {
        $hours = config('auth.passwords.account_setup.expire') / 60;
        $mail = $this->invitedBy
            ? (new MailMessage)
                ->subject('Set up your Canopy Tours account')
                ->line('Use the link below to set a password for your Canopy Tours booking account.')
                ->action('Set your password', $url)
                ->line("This link expires in {$hours} hours and can only be used once.")
                ->line('If it expires, please reply to this email to request a new link.')
                ->replyTo($this->invitedBy)
            : parent::buildMailMessage($url);

        return $mail
            ->withSymfonyMessage(fn (Email $message) => $message->getHeaders()->addTextHeader(self::NO_AUDIT_COPY_HEADER, '1'));
    }

    protected function resetUrl($notifiable)
    {
        if (! $this->invitedBy) {
            return parent::resetUrl($notifiable);
        }

        return route('password.setup', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
