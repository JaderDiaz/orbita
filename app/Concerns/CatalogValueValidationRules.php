<?php

namespace App\Concerns;

use App\Enums\CatalogColor;
use App\Enums\CatalogIcon;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Validation shared by creating and editing a catalog value.
 *
 * A list only accepts the fields it declares, so the rules are built from the
 * list rather than fixed: submitting a colour to a list that renders no colour
 * is not a valid request.
 */
trait CatalogValueValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function catalogValueRules(CatalogList $list, ?CatalogValue $ignoring = null): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:120',
                $this->uniquePerList($list, 'name', $ignoring, 'Ya existe un valor con ese nombre en la lista.'),
            ],
            'is_default' => ['boolean'],
        ];

        if ($list->usesField('code')) {
            $rules['code'] = [
                'nullable',
                'string',
                'max:5',
                $this->uniquePerList($list, 'code', $ignoring, 'Ya existe un valor con ese código en la lista.'),
            ];
        }

        if ($list->usesField('color')) {
            $rules['color'] = ['nullable', Rule::enum(CatalogColor::class)];
        }

        if ($list->usesField('icon')) {
            $rules['icon'] = ['nullable', Rule::enum(CatalogIcon::class)];
        }

        if ($list->usesField('desc')) {
            $rules['description'] = ['nullable', 'string', 'max:500'];
        }

        if ($list->primaryFlag() !== null) {
            $rules['flag'] = ['boolean'];
        }

        return $rules;
    }

    /**
     * Case-insensitive uniqueness within the list.
     *
     * Written as a closure rather than Rule::unique because the database
     * enforces this with an index over `lower(column)`. A case-sensitive check
     * here would accept "APROBADA" next to "Aprobada" and then surface the
     * rejection as a 500 instead of a field error.
     */
    private function uniquePerList(CatalogList $list, string $column, ?CatalogValue $ignoring, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($list, $column, $ignoring, $message): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            // The column is matched to a literal expression rather than
            // interpolated, so no identifier ever reaches the SQL from a
            // variable.
            $comparison = match ($column) {
                'name' => 'lower(name) = lower(?)',
                'code' => 'lower(code) = lower(?)',
                default => throw new InvalidArgumentException("No uniqueness rule for column [{$column}]."),
            };

            $taken = CatalogValue::query()
                ->where('list_id', $list->id)
                ->whereRaw($comparison, [trim($value)])
                ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring?->getKey()))
                ->exists();

            if ($taken) {
                $fail($message);
            }
        };
    }
}
