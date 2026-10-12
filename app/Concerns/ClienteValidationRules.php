<?php

namespace App\Concerns;

use App\Actions\Clientes\SaveCliente;
use App\Enums\TipoPersona;
use App\Models\CatalogValue;
use App\Models\Cliente;
use App\Support\Clientes\NitVerificationDigit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validation shared by creating and editing a client.
 *
 * Uniqueness is checked case-insensitively because the database enforces it
 * over `lower()` and `upper()` expressions. A case-sensitive check here would
 * accept the duplicate and surface the rejection as a 500 rather than a field
 * error.
 */
trait ClienteValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function clienteRules(?Cliente $ignoring = null): array
    {
        return [
            'tipo_persona' => ['required', Rule::enum(TipoPersona::class)],
            'tipo_documento_value_id' => ['required', $this->belongsToList('tipos-documento')],
            'regimen_value_id' => ['required', $this->belongsToList('regimenes')],
            'documento' => ['required', 'string', 'max:30', $this->documentRules($ignoring)],
            'nombre' => ['required', 'string', 'max:180'],
            'tag' => ['nullable', 'string', 'size:3', 'regex:/^[A-Za-z0-9]{3}$/', $this->uniqueTag($ignoring)],
            'ciudad' => ['required', 'string', 'max:120'],
            'departamento' => ['required', 'string', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],

            'contactos' => ['required', 'array', 'min:1', $this->exactlyOnePrincipal()],
            'contactos.*.nombre' => ['required', 'string', 'max:150'],
            'contactos.*.cargo' => ['nullable', 'string', 'max:120'],
            'contactos.*.email' => ['required', 'email', 'max:180'],
            'contactos.*.telefono' => ['nullable', 'string', 'max:40'],
            'contactos.*.is_principal' => ['boolean'],
        ];
    }

    /**
     * The value has to belong to the list the field is for. Without this a
     * régimen could be submitted as a tipo de documento.
     */
    private function belongsToList(string $listKey): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($listKey): void {
            $belongs = CatalogValue::query()
                ->whereKey($value)
                ->whereRelation('list', 'key', $listKey)
                ->where('is_active', true)
                ->exists();

            if (! $belongs) {
                $fail('Selecciona una opción válida de la lista.');
            }
        };
    }

    /**
     * One person or company is one client, and a NIT must carry a verification
     * digit that actually corresponds to it.
     */
    private function documentRules(?Cliente $ignoring): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoring): void {
            if (! is_string($value)) {
                return;
            }

            $documento = SaveCliente::normaliseDocument($value);

            if ($documento === '') {
                $fail('El documento no puede quedar vacío.');

                return;
            }

            $taken = Cliente::query()
                ->whereRaw('lower(documento) = lower(?)', [$documento])
                ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring?->getKey()))
                ->exists();

            if ($taken) {
                $fail('Ya existe un cliente con ese documento.');

                return;
            }

            $tipoPersona = $this->input('tipo_persona');

            if ($tipoPersona !== TipoPersona::Juridica->value) {
                return;
            }

            if (NitVerificationDigit::for($documento) === null) {
                $fail('El NIT no es válido.');

                return;
            }

            $claimed = self::claimedVerificationDigit($value);

            if ($claimed !== null && ! NitVerificationDigit::matches($documento, $claimed)) {
                $fail('El dígito de verificación no corresponde al NIT.');
            }
        };
    }

    private function uniqueTag(?Cliente $ignoring): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoring): void {
            if (! is_string($value)) {
                return;
            }

            $taken = Cliente::query()
                ->whereRaw('upper(tag) = upper(?)', [trim($value)])
                ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring?->getKey()))
                ->exists();

            if ($taken) {
                $fail('Ese tag ya lo usa otro cliente. La numeración debe ser única.');
            }
        };
    }

    /**
     * The database enforces this too, but a trigger error is not a field error:
     * caught here, the form can point at the contacts.
     */
    private function exactlyOnePrincipal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            $principals = count(array_filter(
                $value,
                fn (mixed $contacto): bool => is_array($contacto) && (bool) ($contacto['is_principal'] ?? false),
            ));

            if ($principals !== 1) {
                $fail('Marca exactamente un contacto principal.');
            }
        };
    }

    /**
     * The digit the user wrote after the dash, if any.
     */
    private static function claimedVerificationDigit(string $documento): ?string
    {
        $parts = explode('-', trim($documento));

        if (count($parts) < 2) {
            return null;
        }

        $claimed = preg_replace('/\D/', '', $parts[1]) ?? '';

        return $claimed === '' ? null : $claimed;
    }
}
