<?php

namespace Tests\Feature\Clientes;

use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Clientes is the first business table and everything else will hold foreign
 * keys to it, so its invariants live in Postgres.
 *
 * Inserts are raw rather than through Eloquent: an enum cast would throw while
 * building the query and the constraint would never be exercised.
 *
 * Note the `SET CONSTRAINTS ALL IMMEDIATE` calls. The principal-contact rule is
 * a DEFERRED constraint trigger, and RefreshDatabase wraps each test in a
 * transaction it rolls back, so the trigger would never fire on its own. Asking
 * for immediate evaluation is what makes a deferred rule testable at all.
 */
class ClienteSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_a_client_with_one_principal_contact_is_accepted(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true]);

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->assertSame(1, DB::table('clientes')->count());
    }

    public function test_a_client_without_contacts_is_rejected(): void
    {
        $this->makeCliente();

        $this->expectException(QueryException::class);

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    public function test_a_client_whose_only_contact_is_not_principal_is_rejected(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => false]);

        $this->expectException(QueryException::class);

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    public function test_a_client_cannot_hold_two_principal_contacts(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true]);

        $this->expectException(QueryException::class);

        $this->makeContacto($cliente, ['is_principal' => true, 'email' => 'otro@andina.co']);
    }

    public function test_demoting_the_only_principal_is_rejected(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true]);

        // Once constraints are set immediate they stay that way for the rest of
        // the transaction, so the update itself is what gets rejected.
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->expectException(QueryException::class);

        DB::table('cliente_contactos')->where('cliente_id', $cliente)->update(['is_principal' => false]);
    }

    public function test_deleting_a_client_takes_its_contacts_without_tripping_the_rule(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        DB::table('clientes')->where('id', $cliente)->delete();

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->assertSame(0, DB::table('cliente_contactos')->count(), 'La cascada no debe dejar contactos huerfanos.');
    }

    public function test_the_document_is_unique_regardless_of_case(): void
    {
        $this->makeCliente(['documento' => 'ce-12345']);

        $this->expectException(QueryException::class);

        $this->makeCliente(['documento' => 'CE-12345', 'tag' => 'OTR']);
    }

    public function test_the_tag_is_unique_regardless_of_case(): void
    {
        $this->makeCliente(['tag' => 'ASE']);

        $this->expectException(QueryException::class);

        $this->makeCliente(['tag' => 'ase', 'documento' => '900999888']);
    }

    public function test_a_malformed_tag_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->makeCliente(['tag' => 'a-1']);
    }

    public function test_an_unknown_tipo_persona_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->makeCliente(['tipo_persona' => 'mixta']);
    }

    public function test_a_contact_email_is_unique_within_the_client(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true, 'email' => 'pagos@andina.co']);

        $this->expectException(QueryException::class);

        $this->makeContacto($cliente, ['email' => 'PAGOS@ANDINA.CO']);
    }

    public function test_the_same_contact_email_is_allowed_for_another_client(): void
    {
        $first = $this->makeCliente();
        $second = $this->makeCliente(['documento' => '900999888', 'tag' => 'OTR']);

        $this->makeContacto($first, ['is_principal' => true, 'email' => 'pagos@grupo.co']);
        $this->makeContacto($second, ['is_principal' => true, 'email' => 'pagos@grupo.co']);

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->assertSame(2, DB::table('cliente_contactos')->count());
    }

    public function test_a_catalog_value_in_use_by_a_client_cannot_be_deleted(): void
    {
        $cliente = $this->makeCliente();
        $this->makeContacto($cliente, ['is_principal' => true]);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->expectException(QueryException::class);

        DB::table('catalog_values')->where('id', $this->catalogValue('nit'))->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCliente(array $attributes = []): int
    {
        return (int) DB::table('clientes')->insertGetId([
            'tipo_persona' => 'juridica',
            'tipo_documento_value_id' => $this->catalogValue('nit'),
            'regimen_value_id' => $this->catalogValue('responsable-de-iva'),
            'documento' => '900111222',
            'dv' => '7',
            'nombre' => 'Andina Seguros',
            'tag' => 'ASE',
            'ciudad' => 'Bogotá',
            'departamento' => 'Cundinamarca',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeContacto(int $clienteId, array $attributes = []): void
    {
        DB::table('cliente_contactos')->insert([
            'cliente_id' => $clienteId,
            'nombre' => 'Marcela Peña',
            'email' => 'mpena@andina.co',
            'is_principal' => false,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);
    }

    private function catalogValue(string $systemKey): int
    {
        return (int) DB::table('catalog_values')->where('system_key', $systemKey)->value('id');
    }
}
