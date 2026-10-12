<?php

namespace Tests\Feature\Catalogo;

use App\Models\CatalogValue;
use App\Support\Catalogo\UsageRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A registry that reports a stale count is what let the prototype offer to
 * delete a value used in fifty records, so the count is derived from the real
 * references and the delete is guarded by the foreign key underneath it.
 */
class UsageRegistryTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_a_value_nothing_points_at_is_not_in_use(): void
    {
        $value = CatalogValue::factory()->create();

        $this->assertSame(0, UsageRegistry::countFor($value->id));
        $this->assertFalse(UsageRegistry::isInUse($value->id));
    }

    public function test_the_whole_catalog_is_counted_in_one_query(): void
    {
        CatalogValue::factory()->create();

        UsageRegistry::flush();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        UsageRegistry::counts();
        UsageRegistry::counts();

        $this->assertSame(1, $queries, 'El conteo se memoriza por request: una consulta, no una por valor.');
    }

    public function test_it_counts_real_references_in_one_query(): void
    {
        $this->createConsumerTables();

        $used = CatalogValue::factory()->create();
        $unused = CatalogValue::factory()->create();

        DB::table('test_proyectos')->insert([
            ['estado_value_id' => $used->id],
            ['estado_value_id' => $used->id],
        ]);
        DB::table('test_tareas')->insert([
            ['prioridad_value_id' => $used->id],
            ['prioridad_value_id' => null],
        ]);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $counts = ConsumerUsageRegistry::counts();

        $this->assertSame(1, $queries, 'El conteo debe resolverse en una sola consulta.');
        $this->assertSame([$used->id => 3], $counts, 'Un valor sin uso no debe aparecer en el conteo.');
        $this->assertSame(3, ConsumerUsageRegistry::countFor($used->id));
        $this->assertSame(0, ConsumerUsageRegistry::countFor($unused->id));
        $this->assertTrue(ConsumerUsageRegistry::isInUse($used->id));
        $this->assertFalse(ConsumerUsageRegistry::isInUse($unused->id));
    }

    public function test_a_value_in_use_cannot_be_deleted_even_if_the_count_is_wrong(): void
    {
        $this->createConsumerTables();

        $value = CatalogValue::factory()->create();
        DB::table('test_proyectos')->insert(['estado_value_id' => $value->id]);

        $this->expectException(QueryException::class);

        $value->delete();
    }

    private function createConsumerTables(): void
    {
        Schema::create('test_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_value_id')->nullable()->constrained('catalog_values')->restrictOnDelete();
        });

        Schema::create('test_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prioridad_value_id')->nullable()->constrained('catalog_values')->restrictOnDelete();
        });
    }
}

/**
 * Stands in for the registry once business modules declare their references.
 * The production list is empty because nothing references the catalog yet.
 */
class ConsumerUsageRegistry extends UsageRegistry
{
    /**
     * @return list<array{table: string, column: string}>
     */
    public static function references(): array
    {
        return [
            ['table' => 'test_proyectos', 'column' => 'estado_value_id'],
            ['table' => 'test_tareas', 'column' => 'prioridad_value_id'],
        ];
    }
}
