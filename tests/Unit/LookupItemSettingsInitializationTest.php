<?php

use App\Models\LookupItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->originalDatabaseConnection = config('database.default');
    config([
        'database.default' => 'settings_test',
        'database.connections.settings_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ]);
    DB::purge('settings_test');

    Schema::connection('settings_test')->create('lookups', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::connection('settings_test')->create('lookup_items', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->text('info')->nullable();
        $table->string('slug')->nullable();
        $table->float('rate')->nullable();
        $table->float('multiplier')->nullable();
        $table->boolean('value')->nullable();
        $table->date('effective_from');
        $table->date('effective_to')->nullable();
        $table->boolean('is_default')->default(false);
        $table->boolean('is_active')->default(true);
        $table->unsignedBigInteger('team_id')->nullable();
        $table->unsignedBigInteger('lookup_id');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::connection('settings_test')->create('audit_logs', function (Blueprint $table) {
        $table->id();
        $table->text('description');
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->text('properties')->nullable();
        $table->string('host', 46)->nullable();
        $table->timestamps();
    });

    LookupItem::creating(function (LookupItem $item) {
        if ($item->team_id === null) {
            $item->team_id = 3;
        }
    });
});

afterEach(function () {
    LookupItem::flushEventListeners();
    LookupItem::clearBootedModels();
    DB::purge('settings_test');
    config(['database.default' => $this->originalDatabaseConnection]);
});

it('creates global defaults once even when a tenant creation hook is active', function () {
    LookupItem::ensureSettingsForTeam(3);

    $firstGlobalCount = DB::table('lookup_items')->whereNull('team_id')->count();
    $firstTeamCount = DB::table('lookup_items')->where('team_id', 3)->count();

    LookupItem::ensureSettingsForTeam(3);

    expect($firstGlobalCount)->toBeGreaterThan(0)
        ->and($firstTeamCount)->toBe($firstGlobalCount)
        ->and(DB::table('lookups')->where('slug', 'settings')->count())->toBe(1)
        ->and(DB::table('lookup_items')->whereNull('team_id')->count())->toBe($firstGlobalCount)
        ->and(DB::table('lookup_items')->where('team_id', 3)->count())->toBe($firstTeamCount)
        ->and(DB::table('lookup_items')->whereNull('team_id')->distinct()->count('slug'))->toBe($firstGlobalCount)
        ->and(DB::table('lookup_items')->where('team_id', 3)->distinct()->count('slug'))->toBe($firstTeamCount);
});
