<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('telephone')->nullable()->after('vat_no');
            $table->string('email')->nullable()->after('telephone');
            $table->string('website_url')->nullable()->after('email');
            $table->string('address_line_1')->nullable()->after('address');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('postal_code')->nullable()->after('city');
            $table->string('bank_branch_name')->nullable()->after('bank_name');
        });

        DB::table('type_files')->updateOrInsert(
            ['id' => 8],
            [
                'name' => 'invoice_quote_icon',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'telephone',
                'email',
                'website_url',
                'address_line_1',
                'address_line_2',
                'city',
                'postal_code',
                'bank_branch_name',
            ]);
        });

        DB::table('files')->where('type_file_id', 8)->update(['type_file_id' => null]);
        DB::table('type_files')->where('id', 8)->where('name', 'invoice_quote_icon')->delete();
    }
};
