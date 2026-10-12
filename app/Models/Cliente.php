<?php

namespace App\Models;

use App\Enums\TipoPersona;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A client of the agency.
 *
 * Identity is this row's id, never the name. The prototype keys every relation
 * on the company name string, so renaming a client there would orphan its
 * projects, quotes, invoices and documents.
 *
 * @property int $id
 * @property TipoPersona $tipo_persona
 * @property int $tipo_documento_value_id
 * @property int $regimen_value_id
 * @property string $documento
 * @property string|null $dv
 * @property string $nombre
 * @property string $tag
 * @property string $ciudad
 * @property string $departamento
 * @property string|null $direccion
 * @property string|null $notas
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CatalogValue $tipoDocumento
 * @property-read CatalogValue $regimen
 */
#[Fillable([
    'tipo_persona', 'tipo_documento_value_id', 'regimen_value_id', 'documento', 'dv',
    'nombre', 'tag', 'ciudad', 'departamento', 'direccion', 'notas', 'is_active',
])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'clientes';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_persona' => TipoPersona::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ClienteContacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(ClienteContacto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The one contact that receives cotizaciones and cuentas de cobro. The
     * database guarantees there is exactly one.
     *
     * @return HasOne<ClienteContacto, $this>
     */
    public function contactoPrincipal(): HasOne
    {
        return $this->hasOne(ClienteContacto::class)->where('is_principal', true);
    }

    /**
     * @return BelongsTo<CatalogValue, $this>
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(CatalogValue::class, 'tipo_documento_value_id');
    }

    /**
     * @return BelongsTo<CatalogValue, $this>
     */
    public function regimen(): BelongsTo
    {
        return $this->belongsTo(CatalogValue::class, 'regimen_value_id');
    }

    /**
     * The document as it is written on an invoice: NIT with its verification
     * digit, anything else on its own.
     */
    public function documentoCompleto(): string
    {
        return $this->dv !== null ? "{$this->documento}-{$this->dv}" : $this->documento;
    }

    /**
     * The list's search box, resolved on the server as the standard requires.
     *
     * Accent-insensitive through the immutable wrapper the trigram indexes are
     * built on, so "Medellin" finds "Medellín" and the index is still used.
     *
     * @param  Builder<Cliente>  $query
     * @return Builder<Cliente>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->whereRaw('orbita_unaccent(nombre) ILIKE orbita_unaccent(?)', [$like])
                ->orWhere('documento', 'ILIKE', $like)
                ->orWhereHas('contactos', function (Builder $contactos) use ($like): void {
                    $contactos
                        ->whereRaw('orbita_unaccent(nombre) ILIKE orbita_unaccent(?)', [$like])
                        ->orWhere('email', 'ILIKE', $like);
                });
        });
    }
}
