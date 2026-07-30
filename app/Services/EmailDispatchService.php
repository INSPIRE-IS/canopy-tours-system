<?php

namespace App\Services;

use App\Models\LookupItem;

class EmailDispatchService
{
    public const SETTING_MASTER = 'email_sending_enabled';
    public const SETTING_MANUAL = 'manual_email_sending_enabled';
    public const SETTING_AUTO_INVOICE = 'auto_send_invoice_on_booking';
    public const SETTING_AUTO_QUOTE = 'auto_send_quote_on_booking';
    public const SETTING_AUTO_PAYMENT = 'auto_send_payment_confirmation';
    public const SETTING_AUTO_QUESTIONNAIRE = 'auto_send_questionnaire_confirmation';

    public function canSendAutomatic(?int $teamId, string $settingSlug): bool
    {
        $settings = $this->settings($teamId);

        return $this->isEnabled($settings, self::SETTING_MASTER)
            && $this->isEnabled($settings, $settingSlug);
    }

    public function canSendManual(?int $teamId): bool
    {
        $settings = $this->settings($teamId);

        return $this->isEnabled($settings, self::SETTING_MASTER)
            && $this->isEnabled($settings, self::SETTING_MANUAL);
    }

    public function shouldSendBookingDocument(?int $teamId, bool $autoSetting, bool $explicitRequest): bool
    {
        $settings = $this->settings($teamId);

        if (!$this->isEnabled($settings, self::SETTING_MASTER)) {
            return false;
        }

        if ($explicitRequest) {
            return $this->isEnabled($settings, self::SETTING_MANUAL);
        }

        return $autoSetting;
    }

    public function manualDisabledMessage(): string
    {
        return 'Manual guest email sending is disabled for this site.';
    }

    private function settings(?int $teamId): array
    {
        return LookupItem::settingsForTeam($teamId);
    }

    private function isEnabled(array $settings, string $slug): bool
    {
        return (bool) ($settings[$slug] ?? false);
    }
}
