<?php

namespace Tests\Unit\Catalogo;

use App\Support\Catalogo\UsageRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The registry's declared references are the contract between the catalog and
 * the modules that consume it. Asserting them here, with no application booted,
 * keeps a module from quietly dropping itself off the list and making its
 * catalog values deletable again.
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

    public function test_every_consumer_of_the_catalog_is_declared(): void
    {
        $this->assertSame([
            ['table' => 'clientes', 'column' => 'tipo_documento_value_id'],
            ['table' => 'clientes', 'column' => 'regimen_value_id'],
        ], UsageRegistry::references());
    }
}
