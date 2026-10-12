<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Keys whose phpunit.xml values must win over the container environment.
     *
     * @var list<string>
     */
    private const OVERRIDABLE_ENV = [
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];

    protected function setUp(): void
    {
        $this->promotePhpUnitEnvironment();

        parent::setUp();
    }

    /**
     * Laravel's env() reads $_SERVER before $_ENV, and Docker Compose injects the
     * development database into $_SERVER. PHPUnit's <env force="true"> only sets
     * putenv() and $_ENV, so without this the suite would silently run against the
     * development database and RefreshDatabase would wipe it.
     *
     * Copying the resolved values into $_SERVER before the application boots keeps
     * phpunit.xml the single source of truth. Guarded by TestDatabaseTargetTest.
     */
    private function promotePhpUnitEnvironment(): void
    {
        foreach (self::OVERRIDABLE_ENV as $key) {
            $value = getenv($key);

            if ($value !== false) {
                $_SERVER[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
