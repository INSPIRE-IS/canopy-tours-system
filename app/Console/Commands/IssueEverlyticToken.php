<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AttendedGuestFeedService;
use Illuminate\Console\Command;

class IssueEverlyticToken extends Command
{
    protected $signature = 'integrations:issue-everlytic-token
        {--email= : Existing Integration API user email address}
        {--name= : Descriptive token name, for example staging-2026-11}
        {--revoke-existing : Revoke existing Everlytic tokens for this user first}';

    protected $description = 'Issue a non-expiring, site-scoped Sanctum token for an Integration API user.';

    public function handle(): int
    {
        if (config('sanctum.expiration') !== null) {
            $this->error('Sanctum has a global token expiration configured. Refusing to issue a token described as non-expiring.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid existing Integration API user --email is required.');

            return self::FAILURE;
        }

        $user = User::query()
            ->with(['roles', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
        if (! $user) {
            $this->error("Active user {$email} was not found.");

            return self::FAILURE;
        }

        if (! $user->team) {
            $this->error('The user is not assigned to an active site.');

            return self::FAILURE;
        }

        $roleNames = $user->roles->pluck('name');
        if ($roleNames->count() !== 1 || $roleNames->first() !== 'Integration API') {
            $this->error('The user must have only the Integration API role.');

            return self::FAILURE;
        }

        $tokenName = trim((string) $this->option('name'));
        $tokenName = $tokenName !== ''
            ? "everlytic-{$tokenName}"
            : 'everlytic-'.now()->format('Ymd-His');

        if ($this->option('revoke-existing')) {
            $user->tokens()->where('name', 'like', 'everlytic-%')->delete();
        }

        $token = $user->createToken($tokenName, [AttendedGuestFeedService::TOKEN_ABILITY]);

        $this->newLine();
        $this->warn('Store this token securely. It will only be displayed once:');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->info("Issued {$tokenName} for {$user->email} and site {$user->team->name}.");
        $this->line('Ability: '.AttendedGuestFeedService::TOKEN_ABILITY);
        $this->line('Expires: never (until explicitly revoked)');

        return self::SUCCESS;
    }
}
