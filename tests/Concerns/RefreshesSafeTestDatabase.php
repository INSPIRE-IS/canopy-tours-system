<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

trait RefreshesSafeTestDatabase
{
    use RefreshDatabase {
        refreshDatabase as private refreshDatabaseWithoutSafetyCheck;
    }

    public function refreshDatabase(): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}", []);
        $driver = $connection['driver'] ?? null;
        $database = $connection['database'] ?? null;
        $databaseName = is_string($database) ? basename($database) : '';

        $safeNamedDatabase = is_string($database) && str_ends_with($database, '_testing');
        $safeSqliteDatabase = $driver === 'sqlite'
            && ($database === ':memory:' || str_contains($databaseName, 'testing'));

        if (!app()->environment('testing') || (!$safeNamedDatabase && !$safeSqliteDatabase)) {
            throw new RuntimeException(sprintf(
                'Refusing to refresh database [%s]. Tests may only refresh a *_testing database or a dedicated testing SQLite database.',
                $database ?: 'unknown'
            ));
        }

        $this->refreshDatabaseWithoutSafetyCheck();
    }
}
