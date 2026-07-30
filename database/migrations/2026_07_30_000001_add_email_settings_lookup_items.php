<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $settings = [
        [
            'slug' => 'enable_pickup_dropoff',
            'name' => 'Enable Pickup/Dropoff',
            'info' => 'Show pickup/dropoff fields on booking forms.',
            'value' => 0,
        ],
        [
            'slug' => 'auto_send_invoice_on_booking',
            'name' => 'Auto Send Invoice on Booking',
            'info' => 'Automatically send invoice and boarding pass emails when a booking is created or updated.',
            'value' => 0,
        ],
        [
            'slug' => 'auto_send_quote_on_booking',
            'name' => 'Auto Send Quote on Booking',
            'info' => 'Automatically send quote emails when a booking is created or updated.',
            'value' => 0,
        ],
        [
            'slug' => 'enable_assign_staff',
            'name' => 'Enable Assign Staff',
            'info' => 'Show assigned staff section on booking add view.',
            'value' => 0,
        ],
        [
            'slug' => 'show_send_invoice_checkbox',
            'name' => 'Show Send Invoice Checkbox',
            'info' => 'If enabled, staff can choose to send the invoice on save. Manual email sending must also be enabled.',
            'value' => 1,
        ],
        [
            'slug' => 'show_send_quote_checkbox',
            'name' => 'Show Send Quote Checkbox',
            'info' => 'If enabled, staff can choose to send the quote on save. Manual email sending must also be enabled.',
            'value' => 1,
        ],
        [
            'slug' => 'show_booking_information',
            'name' => 'Show Booking Information',
            'info' => 'Show booking information fields on add booking.',
            'value' => 1,
        ],
        [
            'slug' => 'email_sending_enabled',
            'name' => 'Enable Guest Email Sending',
            'info' => 'Master site switch for sending guest emails. If disabled, automatic and manual guest emails are blocked.',
            'value' => 1,
        ],
        [
            'slug' => 'manual_email_sending_enabled',
            'name' => 'Enable Manual Email Sending',
            'info' => 'Allow staff to deliberately send guest emails such as payment links, invoices, check-in links, and guest messages.',
            'value' => 1,
        ],
        [
            'slug' => 'auto_send_payment_confirmation',
            'name' => 'Auto Send Payment Confirmation',
            'info' => 'Automatically send confirmation emails after frontend PayFast or simulated payment flows complete.',
            'value' => 0,
        ],
        [
            'slug' => 'auto_send_questionnaire_confirmation',
            'name' => 'Auto Send Questionnaire Confirmation',
            'info' => 'Automatically send indemnity or feedback confirmation emails after guests complete questionnaire flows.',
            'value' => 0,
        ],
    ];

    public function up(): void
    {
        $now = now();

        $lookupId = DB::table('lookups')->where('slug', 'settings')->value('id');
        if (!$lookupId) {
            $lookupId = DB::table('lookups')->insertGetId([
                'name' => 'Settings',
                'slug' => 'settings',
                'team_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $teamIds = DB::table('teams')->pluck('id')->all();

        foreach ($this->settings as $setting) {
            $this->upsertSetting($lookupId, null, $setting, $now);

            foreach ($teamIds as $teamId) {
                $this->upsertSetting($lookupId, (int) $teamId, $setting, $now);
            }
        }
    }

    public function down(): void
    {
        DB::table('lookup_items')
            ->whereIn('slug', [
                'email_sending_enabled',
                'manual_email_sending_enabled',
                'auto_send_payment_confirmation',
                'auto_send_questionnaire_confirmation',
            ])
            ->delete();
    }

    private function upsertSetting(int $lookupId, ?int $teamId, array $setting, $now): void
    {
        $query = DB::table('lookup_items')
            ->where('lookup_id', $lookupId)
            ->where('slug', $setting['slug']);

        $teamId === null
            ? $query->whereNull('team_id')
            : $query->where('team_id', $teamId);

        $existingId = $query->value('id');

        if ($existingId) {
            DB::table('lookup_items')
                ->where('id', $existingId)
                ->update([
                    'name' => $setting['name'],
                    'info' => $setting['info'],
                    'multiplier' => null,
                    'value' => (int) ($setting['value'] ?? 0),
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('lookup_items')->insert([
            'name' => $setting['name'],
            'info' => $setting['info'],
            'slug' => $setting['slug'],
            'rate' => null,
            'multiplier' => null,
            'value' => (int) ($setting['value'] ?? 0),
            'effective_from' => $now->toDateString(),
            'effective_to' => null,
            'is_default' => 1,
            'is_active' => 1,
            'team_id' => $teamId,
            'lookup_id' => $lookupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
