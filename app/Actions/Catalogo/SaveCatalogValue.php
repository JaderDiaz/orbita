<?php

namespace App\Actions\Catalogo;

use App\Models\CatalogList;
use App\Models\CatalogValue;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates one catalog value.
 *
 * Touches two rows whenever the value becomes the default — itself and whatever
 * used to be — so it runs in a transaction. The database enforces one default
 * per list, which is exactly why clearing and setting cannot be two statements
 * outside one.
 */
class SaveCatalogValue
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(CatalogList $list, array $attributes): CatalogValue
    {
        return DB::transaction(function () use ($list, $attributes): CatalogValue {
            $position = (int) $list->values()->max('position');

            $value = new CatalogValue;
            $value->list_id = $list->id;
            $value->position = $position + 1;
            $value->is_active = true;

            return $this->fill($value, $list, $attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(CatalogValue $value, array $attributes): CatalogValue
    {
        return DB::transaction(fn (): CatalogValue => $this->fill($value, $value->list, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fill(CatalogValue $value, CatalogList $list, array $attributes): CatalogValue
    {
        $value->name = trim((string) $attributes['name']);

        // A field the list does not declare is never stored: the screen would
        // not render it, and a hidden colour is a value nobody can correct.
        $value->code = $list->usesField('code') ? $this->trimmed($attributes['code'] ?? null) : null;
        $value->color = $list->usesField('color') ? ($attributes['color'] ?? null) : null;
        $value->icon = $list->usesField('icon') ? ($attributes['icon'] ?? null) : null;
        $value->description = $list->usesField('desc') ? $this->trimmed($attributes['description'] ?? null) : null;

        $flag = $list->primaryFlag();

        if ($flag !== null) {
            $value->flags = [$flag['key'] => (bool) ($attributes['flag'] ?? false)];
        }

        $shouldBeDefault = (bool) ($attributes['is_default'] ?? false);

        if ($shouldBeDefault) {
            $this->clearDefaultOf($list, $value);
        }

        $value->is_default = $shouldBeDefault;
        $value->save();

        return $value;
    }

    private function clearDefaultOf(CatalogList $list, CatalogValue $keeping): void
    {
        $list->values()
            ->where('is_default', true)
            ->when($keeping->exists, fn ($query) => $query->whereKeyNot($keeping->getKey()))
            ->update(['is_default' => false]);
    }

    private function trimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
