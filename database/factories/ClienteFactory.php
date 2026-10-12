<?php

namespace Database\Factories;

use App\Enums\TipoPersona;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Cliente;
use App\Support\Clientes\NitVerificationDigit;
use App\Support\Clientes\TagGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->unique()->company();
        $documento = (string) fake()->unique()->numberBetween(800_000_000, 999_999_999);

        return [
            'tipo_persona' => TipoPersona::Juridica,
            'tipo_documento_value_id' => fn (): int => $this->catalogValue('tipos-documento', 'nit', 'NIT'),
            'regimen_value_id' => fn (): int => $this->catalogValue('regimenes', 'responsable-de-iva', 'Responsable de IVA'),
            'documento' => $documento,
            'dv' => (string) NitVerificationDigit::for($documento),
            'nombre' => $nombre,
            'tag' => $this->freeTag($nombre),
            'ciudad' => fake()->randomElement(['Medellín', 'Bogotá', 'Cali', 'Barranquilla']),
            'departamento' => fake()->randomElement(['Antioquia', 'Cundinamarca', 'Valle del Cauca', 'Atlántico']),
            'direccion' => fake()->streetAddress(),
            'notas' => null,
            'is_active' => true,
        ];
    }

    public function natural(): static
    {
        return $this->state(fn (): array => [
            'tipo_persona' => TipoPersona::Natural,
            'tipo_documento_value_id' => $this->catalogValue('tipos-documento', 'cedula-de-ciudadania', 'Cédula de ciudadanía'),
            'dv' => null,
            'nombre' => fake()->name(),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * A client the database will accept: it needs exactly one principal
     * contact, and that rule is a deferred constraint trigger.
     */
    public function conContactoPrincipal(): static
    {
        return $this->afterCreating(function (Cliente $cliente): void {
            ClienteContactoFactory::new()
                ->for($cliente, 'cliente')
                ->principal()
                ->create();
        });
    }

    /**
     * Resolves a seeded catalog value, creating a minimal one when the catalog
     * has not been seeded, so a test that only cares about clientes does not
     * have to know about Maestros.
     */
    private function catalogValue(string $listKey, string $systemKey, string $name): int
    {
        $existing = CatalogValue::query()
            ->whereRelation('list', 'key', $listKey)
            ->where('system_key', $systemKey)
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        $list = CatalogList::query()->firstOrCreate(
            ['key' => $listKey],
            ['group' => 'Clientes y fiscal', 'title' => $listKey, 'flags' => [], 'fields' => [], 'used_in' => []],
        );

        return (int) CatalogValue::query()->create([
            'list_id' => $list->id,
            'system_key' => $systemKey,
            'name' => $name,
        ])->id;
    }

    /**
     * The tag is unique across the install, so the factory has to respect that
     * the same way the application does.
     */
    private function freeTag(string $nombre): string
    {
        return TagGenerator::unique(
            $nombre,
            fn (string $candidate): bool => Cliente::query()->whereRaw('upper(tag) = ?', [$candidate])->exists(),
        );
    }
}
