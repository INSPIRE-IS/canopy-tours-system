<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customer_manifests', 'guardian_name')) {
            Schema::table('customer_manifests', function (Blueprint $table) {
                $table->string('guardian_name')->nullable()->after('type');
            });
        }

        $this->backfillFromLinkedParentCustomer();
        $this->backfillFromTextQuestionAnswers();
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_manifests', 'guardian_name')) {
            Schema::table('customer_manifests', function (Blueprint $table) {
                $table->dropColumn('guardian_name');
            });
        }
    }

    private function backfillFromLinkedParentCustomer(): void
    {
        DB::table('customer_manifests as cm')
            ->join('customers as c', 'c.id', '=', 'cm.customer_id')
            ->join('customers as parent', 'parent.id', '=', 'c.parent_id')
            ->where(function ($query) {
                $query->whereNull('cm.guardian_name')
                    ->orWhere('cm.guardian_name', '');
            })
            ->update([
                'cm.guardian_name' => DB::raw('parent.full_name'),
            ]);
    }

    private function backfillFromTextQuestionAnswers(): void
    {
        if (!Schema::hasTable('question_answers') || !Schema::hasTable('questions')) {
            return;
        }

        $guardianQuestionId = DB::table('questions')
            ->where('slug', 'parent_id')
            ->value('id');

        if (!$guardianQuestionId) {
            return;
        }

        DB::table('question_answers')
            ->select(['id', 'booking_item_id', 'customer_id', 'answer'])
            ->where('question_id', $guardianQuestionId)
            ->whereNotNull('answer')
            ->where('answer', '<>', '')
            ->orderBy('id')
            ->chunkById(200, function ($answers) {
                foreach ($answers as $answer) {
                    $guardianName = trim((string) $answer->answer);
                    if ($guardianName === '' || ctype_digit($guardianName)) {
                        continue;
                    }

                    DB::table('customer_manifests')
                        ->where('booking_item_id', $answer->booking_item_id)
                        ->where('customer_id', $answer->customer_id)
                        ->update(['guardian_name' => $guardianName]);
                }
            });
    }
};
