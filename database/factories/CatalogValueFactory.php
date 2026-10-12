<?php

namespace Database\Factories;

use App\Models\CatalogList;
use App\Models\CatalogValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CatalogValue>
 */
class CatalogValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $name */
        $name = fake()->unique()->words(2, true);

        return [
            'list_id' => CatalogList::factory(),
            'system_key' => null,
            'name' => Str::ucfirst($name),
            'code' => null,
            'color' => null,
            'icon' => null,
            'description' => null,
            'flags' => [],
            'is_default' => false,
            'is_active' => true,
            'is_system' => false,
            'position' => 0,
        ];
    }

    /**
     * The value forms preselect. At most one per list.
     */
    public function isDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    /**
     * Retired: it keeps its history but stops appearing in pickers.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'is_default' => false,
        ]);
    }

    /**
     * A value the code addresses. It can be renamed but never retired.
     */
    public function system(string $systemKey): static
    {
        return $this->state(fn (): array => [
            'system_key' => $systemKey,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, bool>  $flags
     */
    public function flags(array $flags): static
    {
        return $this->state(fn (): array => ['flags' => $flags]);
    }
}
