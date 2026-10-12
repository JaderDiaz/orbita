<?php

namespace Database\Factories;

use App\Models\CatalogList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CatalogList>
 */
class CatalogListFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $title */
        $title = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($title),
            'group' => 'Pruebas',
            'title' => Str::ucfirst($title),
            'description' => fake()->sentence(),
            'flags' => [],
            'fields' => [],
            'used_in' => [],
            'allows_user_values' => true,
            'position' => 0,
        ];
    }

    /**
     * A list whose values carry one boolean with the given label.
     */
    public function withFlag(string $key, string $label): static
    {
        return $this->state(fn (): array => [
            'flags' => [['key' => $key, 'label' => $label]],
        ]);
    }

    /**
     * @param  list<string>  $fields
     */
    public function withFields(array $fields): static
    {
        return $this->state(fn (): array => ['fields' => $fields]);
    }

    public function key(string $key): static
    {
        return $this->state(fn (): array => ['key' => $key]);
    }
}
