<?php

namespace App\Actions\Clientes;

use App\Enums\TipoPersona;
use App\Models\Cliente;
use App\Support\Clientes\NitVerificationDigit;
use App\Support\Clientes\TagGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a client together with its contacts.
 *
 * One transaction on purpose: the database requires every client to have
 * exactly one principal contact, and that rule is a deferred trigger evaluated
 * at commit. Writing the client and its contacts separately would mean a window
 * where the client is invalid, and on failure a client with no way to be quoted.
 */
class SaveCliente
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Cliente
    {
        return DB::transaction(function () use ($attributes): Cliente {
            $cliente = new Cliente;

            return $this->fill($cliente, $attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Cliente $cliente, array $attributes): Cliente
    {
        return DB::transaction(fn (): Cliente => $this->fill($cliente, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fill(Cliente $cliente, array $attributes): Cliente
    {
        $tipoPersona = $attributes['tipo_persona'] instanceof TipoPersona
            ? $attributes['tipo_persona']
            : TipoPersona::from((string) $attributes['tipo_persona']);

        $documento = self::normaliseDocument((string) $attributes['documento']);
        $nombre = trim((string) $attributes['nombre']);

        $cliente->fill([
            'tipo_persona' => $tipoPersona,
            'tipo_documento_value_id' => $attributes['tipo_documento_value_id'],
            'regimen_value_id' => $attributes['regimen_value_id'],
            'documento' => $documento,

            // Derived, never taken from the form: it is part of the identity
            // frozen onto an issued invoice, so it must agree with the NIT.
            'dv' => $tipoPersona->expectsVerificationDigit()
                ? (string) NitVerificationDigit::for($documento)
                : null,

            'nombre' => $nombre,

            // Derived when the form leaves it empty. The generator resolves
            // collisions, which the prototype never does, and the tag is the
            // prefix of this client's invoice series.
            'tag' => $this->resolveTag($cliente, $nombre, $attributes['tag'] ?? null),
            'ciudad' => trim((string) $attributes['ciudad']),
            'departamento' => trim((string) $attributes['departamento']),
            'direccion' => self::trimmed($attributes['direccion'] ?? null),
            'notas' => self::trimmed($attributes['notas'] ?? null),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);

        $cliente->save();

        /** @var list<array<string, mixed>> $contactos */
        $contactos = $attributes['contactos'] ?? [];

        $this->syncContactos($cliente, $contactos);

        return $cliente;
    }

    /**
     * Replaces the contact list wholesale.
     *
     * A contact carries no history of its own — an issued quote freezes its
     * details rather than pointing here — so rewriting the set is safe and
     * avoids trying to match rows the form never identified.
     *
     * @param  list<array<string, mixed>>  $contactos
     */
    private function syncContactos(Cliente $cliente, array $contactos): void
    {
        if ($contactos === []) {
            return;
        }

        $cliente->contactos()->delete();

        foreach ($contactos as $position => $contacto) {
            $cliente->contactos()->create([
                'nombre' => trim((string) $contacto['nombre']),
                'cargo' => self::trimmed($contacto['cargo'] ?? null),
                'email' => mb_strtolower(trim((string) $contacto['email'])),
                'telefono' => self::trimmed($contacto['telefono'] ?? null),
                'is_principal' => (bool) ($contacto['is_principal'] ?? false),
                'position' => $position,
            ]);
        }
    }

    /**
     * The tag the client keeps, honouring one the user typed.
     *
     * Changing a client's tag must not renumber its existing documents, which
     * is why the number is stored text on each document rather than derived
     * from here.
     */
    private function resolveTag(Cliente $cliente, string $nombre, mixed $submitted): string
    {
        if (is_string($submitted) && trim($submitted) !== '') {
            return strtoupper(trim($submitted));
        }

        if ($cliente->exists && $cliente->tag !== '') {
            return $cliente->tag;
        }

        return TagGenerator::unique(
            $nombre,
            fn (string $candidate): bool => Cliente::query()
                ->whereRaw('upper(tag) = ?', [$candidate])
                ->when($cliente->exists, fn ($query) => $query->whereKeyNot($cliente->getKey()))
                ->exists(),
        );
    }

    /**
     * Dots, spaces and the digit written after the dash are presentation. What
     * is stored is the document itself.
     */
    public static function normaliseDocument(string $documento): string
    {
        $withoutClaimed = explode('-', trim($documento))[0];

        return preg_replace('/[^A-Za-z0-9]/', '', $withoutClaimed) ?? '';
    }

    private static function trimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
