<?php

namespace App\Support\Clientes;

use App\Enums\TipoPersona;
use App\Models\CatalogValue;
use App\Models\Cliente;
use App\Models\ClienteContacto;

/**
 * Shapes clientes for the screens.
 *
 * The initials, the formatted document and the tag preview are resolved here so
 * the list, the form and the detail cannot drift from each other.
 */
class ClientePayload
{
    /**
     * One row of the list.
     *
     * @return array<string, mixed>
     */
    public static function row(Cliente $cliente): array
    {
        $principal = $cliente->contactoPrincipal;

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'iniciales' => self::initials($cliente->nombre),
            'documento' => $cliente->documentoCompleto(),
            'tag' => $cliente->tag,
            'is_active' => $cliente->is_active,
            'contacto' => $principal === null ? null : [
                'nombre' => $principal->nombre,
                'email' => $principal->email,
            ],
        ];
    }

    /**
     * Everything the detail screen shows.
     *
     * @return array<string, mixed>
     */
    public static function detail(Cliente $cliente): array
    {
        return [
            ...self::row($cliente),
            'tipo_persona' => $cliente->tipo_persona->value,
            'tipo_persona_label' => $cliente->tipo_persona->label(),
            'tipo_documento' => $cliente->tipoDocumento->name,
            'regimen' => $cliente->regimen->name,
            'ciudad' => $cliente->ciudad,
            'departamento' => $cliente->departamento,
            'direccion' => $cliente->direccion,
            'notas' => $cliente->notas,
            'numeracion' => self::numbering($cliente->tag),
            'desde' => $cliente->created_at?->translatedFormat('M Y'),
            'usos' => ClienteUsage::countFor($cliente->id),
            'contactos' => $cliente->contactos
                ->map(fn (ClienteContacto $contacto): array => self::contacto($contacto))
                ->all(),
        ];
    }

    /**
     * What the form needs to be filled in and re-filled on edit.
     *
     * @return array<string, mixed>
     */
    public static function formValues(?Cliente $cliente): array
    {
        if ($cliente === null) {
            return [
                'id' => null,
                'tipo_persona' => TipoPersona::Juridica->value,
                'tipo_documento_value_id' => null,
                'regimen_value_id' => null,
                'documento' => '',
                'nombre' => '',
                'tag' => '',
                'ciudad' => '',
                'departamento' => '',
                'direccion' => '',
                'notas' => '',
                'is_active' => true,
                'contactos' => [self::emptyContacto(true)],
            ];
        }

        return [
            'id' => $cliente->id,
            'tipo_persona' => $cliente->tipo_persona->value,
            'tipo_documento_value_id' => $cliente->tipo_documento_value_id,
            'regimen_value_id' => $cliente->regimen_value_id,
            'documento' => $cliente->documentoCompleto(),
            'nombre' => $cliente->nombre,
            'tag' => $cliente->tag,
            'ciudad' => $cliente->ciudad,
            'departamento' => $cliente->departamento,
            'direccion' => $cliente->direccion ?? '',
            'notas' => $cliente->notas ?? '',
            'is_active' => $cliente->is_active,
            'contactos' => $cliente->contactos
                ->map(fn (ClienteContacto $contacto): array => self::contacto($contacto))
                ->all(),
        ];
    }

    /**
     * The catalog options the form offers, already filtered to the active ones.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function options(): array
    {
        return [
            'tipos_documento' => self::valuesOf('tipos-documento'),
            'regimenes' => self::valuesOf('regimenes'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function valuesOf(string $listKey): array
    {
        return array_values(CatalogValue::query()
            ->whereRelation('list', 'key', $listKey)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (CatalogValue $value): array => [
                'id' => $value->id,
                'name' => $value->name,
                'code' => $value->code,
                'description' => $value->description,
                'is_default' => $value->is_default,
            ])
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private static function contacto(ClienteContacto $contacto): array
    {
        return [
            'id' => $contacto->id,
            'nombre' => $contacto->nombre,
            'cargo' => $contacto->cargo ?? '',
            'email' => $contacto->email,
            'telefono' => $contacto->telefono ?? '',
            'is_principal' => $contacto->is_principal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyContacto(bool $principal): array
    {
        return [
            'id' => null,
            'nombre' => '',
            'cargo' => '',
            'email' => '',
            'telefono' => '',
            'is_principal' => $principal,
        ];
    }

    /**
     * What this client's documents will be numbered as. Shown in the form so
     * the consequence of the tag is visible while it is being chosen.
     *
     * @return array{factura: string, cotizacion: string}
     */
    public static function numbering(string $tag): array
    {
        return [
            'factura' => "FAC-{$tag}-0001",
            'cotizacion' => "COT-{$tag}-0001",
        ];
    }

    /**
     * Two characters, as the prototype's avatar shows them.
     */
    public static function initials(string $nombre): string
    {
        $words = preg_split('/\s+/', trim($nombre)) ?: [];
        $words = array_values(array_filter($words, fn (string $word): bool => $word !== ''));

        if ($words === []) {
            return '??';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
    }
}
