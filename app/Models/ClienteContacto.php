<?php

namespace App\Models;

use Database\Factories\ClienteContactoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Someone to talk to at a client. Part of the client, not a record of its own:
 * a cotización freezes these details when it is issued rather than pointing
 * here, which is why contacts are deleted with their client.
 *
 * @property int $id
 * @property int $cliente_id
 * @property string $nombre
 * @property string|null $cargo
 * @property string $email
 * @property string|null $telefono
 * @property bool $is_principal
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cliente $cliente
 */
#[Fillable(['cliente_id', 'nombre', 'cargo', 'email', 'telefono', 'is_principal', 'position'])]
class ClienteContacto extends Model
{
    /** @use HasFactory<ClienteContactoFactory> */
    use HasFactory;

    protected $table = 'cliente_contactos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_principal' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
