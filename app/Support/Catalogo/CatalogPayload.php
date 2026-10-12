<?php

namespace App\Support\Catalogo;

use App\Enums\CatalogColor;
use App\Enums\CatalogIcon;
use App\Models\CatalogList;
use App\Models\CatalogValue;

/**
 * Shapes the catalog for the Maestros screen.
 *
 * Resolving the badge pair here rather than in the frontend keeps the palette
 * defined exactly once, in the CatalogColor enum. The screen applies whatever
 * colours arrive and owns no copy of them.
 *
 * The whole catalog is sent in one payload. It is a bounded, administrative set
 * — 16 enumerations whose largest list holds seven values — so the search and
 * the show-inactive toggle filter it in the browser. The standard's
 * server-side-filtering rule is about unbounded transactional tables, where
 * shipping the rows would be the problem; here a round trip per keystroke would
 * cost more than the data.
 */
class CatalogPayload
{
    /**
     * A value whose list declares no colour: the prototype's flat badge.
     */
    private const FLAT_BADGE = ['background' => '#f2f2f7', 'foreground' => '#48484a'];

    /**
     * @return array<string, string>
     */
    private const FIELD_LABELS = [
        'code' => 'Código corto',
        'color' => 'Color del badge',
        'icon' => 'Icono',
        'desc' => 'Descripción',
    ];

    /**
     * @return array{lists: list<array<string, mixed>>, palette: list<array<string, string>>, icons: list<array<string, string>>, summary: string}
     */
    public static function build(): array
    {
        $lists = CatalogList::query()
            ->with(['values' => fn ($query) => $query->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->get();

        $valueCount = $lists->sum(fn (CatalogList $list): int => $list->values->count());

        return [
            'lists' => array_values($lists->map(fn (CatalogList $list): array => self::list($list))->all()),
            'palette' => self::palette(),
            'icons' => self::icons(),
            'summary' => sprintf(
                '%d listas · %d valores. Cambiarlos afecta registros nuevos; los existentes conservan su valor.',
                $lists->count(),
                $valueCount,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function list(CatalogList $list): array
    {
        $flag = $list->primaryFlag();

        return [
            'id' => $list->id,
            'key' => $list->key,
            'group' => $list->group,
            'title' => $list->title,
            'description' => $list->description,
            'fields' => $list->fields,
            'used_in' => $list->used_in,
            'allows_user_values' => $list->allows_user_values,
            'flag' => $flag,
            'field_labels' => self::fieldLabels($list),
            'active_count' => $list->values->where('is_active', true)->count(),
            'inactive_count' => $list->values->where('is_active', false)->count(),
            'values' => array_values($list->values
                ->map(fn (CatalogValue $value): array => self::value($value, $list))
                ->all()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function value(CatalogValue $value, CatalogList $list): array
    {
        $flag = $list->primaryFlag();

        return [
            'id' => $value->id,
            'name' => $value->name,
            'code' => $value->code,
            'color' => $value->color?->value,
            'icon' => $value->icon?->value,
            'description' => $value->description,
            'flag' => $flag !== null && $value->flag($flag['key']),
            'is_default' => $value->is_default,
            'is_active' => $value->is_active,
            'is_system' => $value->is_system,
            'position' => $value->position,
            'usage' => UsageRegistry::countFor($value->id),
            'badge' => self::badge($value, $list),
        ];
    }

    /**
     * The badge the screen renders, resolved exactly as the prototype does it:
     * a list with codes but no colours shows the code, everything else shows
     * the name.
     *
     * @return array{background: string, foreground: string, text: string}
     */
    private static function badge(CatalogValue $value, CatalogList $list): array
    {
        $showsCode = $list->usesField('code') && ! $list->usesField('color');

        $pair = $value->color !== null
            ? ['background' => $value->color->background(), 'foreground' => $value->color->foreground()]
            : self::FLAT_BADGE;

        return [
            ...$pair,
            'text' => $showsCode && $value->code !== null ? $value->code : $value->name,
        ];
    }

    /**
     * The pills under "Campos de cada valor".
     *
     * @return list<string>
     */
    private static function fieldLabels(CatalogList $list): array
    {
        $labels = ['Nombre'];

        foreach (CatalogList::FIELDS as $field) {
            if ($list->usesField($field)) {
                $labels[] = self::FIELD_LABELS[$field];
            }
        }

        $flag = $list->primaryFlag();

        if ($flag !== null) {
            $labels[] = $flag['label'];
        }

        return [...$labels, 'Predeterminado', 'Activo'];
    }

    /**
     * @return list<array<string, string>>
     */
    private static function palette(): array
    {
        return array_map(fn (CatalogColor $color): array => [
            'key' => $color->value,
            'label' => $color->label(),
            'background' => $color->background(),
            'foreground' => $color->foreground(),
        ], CatalogColor::cases());
    }

    /**
     * @return list<array<string, string>>
     */
    private static function icons(): array
    {
        return array_map(fn (CatalogIcon $icon): array => [
            'key' => $icon->value,
            'label' => $icon->label(),
        ], CatalogIcon::cases());
    }
}
