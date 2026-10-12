<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\ClienteContacto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClienteContacto>
 */
class ClienteContactoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'nombre' => fake()->name(),
            'cargo' => fake()->randomElement(['Gerente de tecnología', 'Director de proyectos', 'Tesorería y pagos']),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '+57 300 000 0000',
            'is_principal' => false,
            'position' => 0,
        ];
    }

    /**
     * The contact that receives cotizaciones and cuentas de cobro. Exactly one
     * per client, enforced by the database.
     */
    public function principal(): static
    {
        return $this->state(fn (): array => ['is_principal' => true]);
    }
}
