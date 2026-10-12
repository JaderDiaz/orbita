<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guard test. The suite shares the postgres service with development but must
 * use its own database: RefreshDatabase runs migrate:fresh, so pointing at
 * `orbita` would wipe the development data on every run.
 */
class TestDatabaseTargetTest extends TestCase
{
    public function test_suite_runs_against_the_dedicated_postgres_test_database(): void
    {
        $connection = config('database.default');

        $this->assertSame('pgsql', $connection, 'Tests must run on PostgreSQL, not SQLite.');

        $config = config("database.connections.{$connection}");

        $this->assertSame('orbita_test', $config['database'], 'Tests must target the orbita_test database.');
        $this->assertNotSame('orbita', $config['database'], 'Tests must never touch the development database.');
    }
}
