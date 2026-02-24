<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('help_questions', 'is_internal')) {
            Schema::table('help_questions', function (Blueprint $table) {
                $table->boolean('is_internal')->default(false)->after('question');
            });
        }

        $this->makeTeamNullable();

        if (Schema::hasTable('type_help_questions')) {
            DB::table('help_questions as h')
                ->join('type_help_questions as t', 'h.type_help_question_id', '=', 't.id')
                ->where('t.name', 'Internal')
                ->update(['h.is_internal' => true]);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $this->rebuildHelpQuestionsTable(false, false);
            return;
        }

        Schema::table('help_questions', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE help_questions MODIFY team_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE help_questions ALTER COLUMN team_id SET NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE help_questions ALTER COLUMN team_id BIGINT NOT NULL');
        }

        Schema::table('help_questions', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        if (Schema::hasColumn('help_questions', 'is_internal')) {
            Schema::table('help_questions', function (Blueprint $table) {
                $table->dropColumn('is_internal');
            });
        }
    }

    private function makeTeamNullable(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $this->rebuildHelpQuestionsTable(true, true);
            return;
        }

        Schema::table('help_questions', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE help_questions MODIFY team_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE help_questions ALTER COLUMN team_id DROP NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE help_questions ALTER COLUMN team_id BIGINT NULL');
        }

        Schema::table('help_questions', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    private function rebuildHelpQuestionsTable(bool $teamNullable, bool $includeInternal): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');
        Schema::rename('help_questions', 'help_questions_old');

        Schema::create('help_questions', function (Blueprint $table) use ($teamNullable, $includeInternal) {
            $table->id();
            $table->string('question');
            if ($includeInternal) {
                $table->boolean('is_internal')->default(false);
            }
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('type_help_question_id')
                ->constrained('type_help_questions')
                ->onDelete('cascade');
            $teamColumn = $table->foreignId('team_id')
                ->constrained('teams')
                ->onDelete('cascade');
            if ($teamNullable) {
                $teamColumn->nullable();
            }
        });

        if ($includeInternal) {
            DB::statement(
                'INSERT INTO help_questions (id, question, is_internal, created_at, updated_at, deleted_at, type_help_question_id, team_id)
                 SELECT id, question, is_internal, created_at, updated_at, deleted_at, type_help_question_id, team_id
                 FROM help_questions_old'
            );
        } else {
            DB::statement(
                'INSERT INTO help_questions (id, question, created_at, updated_at, deleted_at, type_help_question_id, team_id)
                 SELECT id, question, created_at, updated_at, deleted_at, type_help_question_id, team_id
                 FROM help_questions_old'
            );
        }

        Schema::drop('help_questions_old');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
