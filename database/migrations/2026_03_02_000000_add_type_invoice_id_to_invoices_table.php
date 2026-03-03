<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('type_invoice_id')
                ->nullable()
                ->after('invoice_template_id')
                ->constrained('type_invoices')
                ->nullOnDelete();
        });

        $defaultTypeId = DB::table('type_invoices')->where('slug', 'std')->value('id');
        if (!$defaultTypeId) {
            return;
        }

        $templateTypeMap = DB::table('invoice_templates')->pluck('type_invoice_id', 'id');
        $invoices = DB::table('invoices')->select('id', 'invoice_template_id')->get();

        foreach ($invoices as $invoice) {
            $typeId = $defaultTypeId;
            if (!empty($invoice->invoice_template_id) && isset($templateTypeMap[$invoice->invoice_template_id])) {
                $typeId = $templateTypeMap[$invoice->invoice_template_id];
            }

            DB::table('invoices')->where('id', $invoice->id)->update([
                'type_invoice_id' => $typeId,
            ]);
        }

        DB::table('invoices')
            ->whereNull('type_invoice_id')
            ->update(['type_invoice_id' => $defaultTypeId]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('type_invoice_id');
        });
    }
};
