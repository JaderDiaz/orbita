<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guard test for the environment the suite actually boots with.
 *
 * The suite shares the postgres service with development but must use its own
 * database: RefreshDatabase runs migrate:fresh, so pointing at `orbita` would
 * wipe the development data on every run.
 *
 * The drivers are guarded for the same reason the database is. Compose injects
 * CACHE_STORE, SESSION_DRIVER, QUEUE_CONNECTION and MAIL_MAILER into $_SERVER,
 * which Laravel's env() reads before $_ENV, so a phpunit.xml entry without
 * force="true" is silently ignored and the suite runs on the database cache and
 * queue instead of the array and sync drivers. See TestCase for the mechanism.
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

    public function test_suite_runs_on_the_drivers_phpunit_declares(): void
    {
        $this->assertSame('array', config('cache.default'), 'El cache de pruebas debe ser array, no la base de datos.');
        $this->assertSame('array', config('session.driver'), 'La sesion de pruebas debe ser array, no la base de datos.');
        $this->assertSame('sync', config('queue.default'), 'La cola de pruebas debe ser sync, no la base de datos.');
        $this->assertSame('array', config('mail.default'), 'El mailer de pruebas debe ser array, no log.');
    }
}
