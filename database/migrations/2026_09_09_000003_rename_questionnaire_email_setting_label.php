<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lookup_items')
            ->where('slug', 'auto_send_questionnaire_confirmation')
            ->update([
                'name' => 'Show Guest Indemnity Email Option',
                'info' => 'Show the guest an optional checkbox to receive a signed indemnity PDF by email. The checkbox remains off by default.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('lookup_items')
            ->where('slug', 'auto_send_questionnaire_confirmation')
            ->update([
                'name' => 'Auto Send Questionnaire Confirmation',
                'info' => 'Automatically send indemnity or feedback confirmation emails after guests complete questionnaire flows.',
                'updated_at' => now(),
            ]);
    }
};
