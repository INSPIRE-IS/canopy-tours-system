<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesaler_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('wholesaler_rates', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('wholesaler_rates', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('is_prepaid_voucher');
            }
            if (!Schema::hasColumn('wholesaler_rates', 'effective_to')) {
                $table->date('effective_to')->nullable()->after('effective_from');
            }
            if (!Schema::hasColumn('wholesaler_rates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('effective_to');
            }
            if (!Schema::hasColumn('wholesaler_rates', 'note')) {
                $table->text('note')->nullable()->after('is_active');
            }
        });

        DB::table('wholesaler_rates')
            ->whereNull('name')
            ->update(['name' => 'Default rate']);

        // MySQL can use the old composite unique index to support FK checks. Add explicit FK indexes first.
        $this->addIndexIfMissing('wholesaler_rates', ['wholesaler_id'], 'wholesaler_rates_wholesaler_id_idx');
        $this->addIndexIfMissing('wholesaler_rates', ['service_id'], 'wholesaler_rates_service_id_idx');
        $this->addIndexIfMissing('wholesaler_rates', ['season_id'], 'wholesaler_rates_season_id_idx');

        if ($this->hasIndex('wholesaler_rates', 'wholesaler_rates_wholesaler_id_service_id_season_id_unique')) {
            Schema::table('wholesaler_rates', function (Blueprint $table) {
                $table->dropUnique('wholesaler_rates_wholesaler_id_service_id_season_id_unique');
            });
        }

        if (!$this->hasIndex('wholesaler_rates', 'wholesaler_rates_lookup_idx')) {
            Schema::table('wholesaler_rates', function (Blueprint $table) {
                $table->index(
                    ['team_id', 'wholesaler_id', 'service_id', 'season_id', 'is_active', 'effective_from', 'effective_to'],
                    'wholesaler_rates_lookup_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('wholesaler_rates', 'wholesaler_rates_lookup_idx')) {
            Schema::table('wholesaler_rates', function (Blueprint $table) {
                $table->dropIndex('wholesaler_rates_lookup_idx');
            });
        }

        if (!$this->hasIndex('wholesaler_rates', 'wholesaler_rates_wholesaler_id_service_id_season_id_unique')) {
            Schema::table('wholesaler_rates', function (Blueprint $table) {
                $table->unique(['wholesaler_id', 'service_id', 'season_id']);
            });
        }

        Schema::table('wholesaler_rates', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['name', 'effective_from', 'effective_to', 'is_active', 'note'],
                fn (string $column) => Schema::hasColumn('wholesaler_rates', $column)
            ));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'select count(1) as aggregate from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
