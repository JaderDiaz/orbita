<?php

namespace Tests\Unit\Catalogo;

use App\Support\Catalogo\UsageRegistry;
use PHPUnit\Framework\TestCase;

/**
 * With no module referencing the catalog, the registry must answer without
 * touching the database at all. This runs with no application booted, which is
 * the proof: a query would fatal here.
 */
class UsageRegistryWithoutDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        UsageRegistry::flush();
    }

    protected function tearDown(): void
    {
        UsageRegistry::flush();

        parent::tearDown();
    }

    public function test_an_empty_registry_reports_no_usage_without_querying(): void
    {
        $this->assertSame([], UsageRegistry::references());
        $this->assertSame([], UsageRegistry::counts());
        $this->assertSame(0, UsageRegistry::countFor(1));
        $this->assertFalse(UsageRegistry::isInUse(1));
    }
}
