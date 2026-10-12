<?php

namespace App\Models;

use App\Support\Catalogo\Catalogo;
use Database\Factories\CatalogListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An editable enumeration the agency manages without a deploy.
 *
 * @property int $id
 * @property string $key
 * @property string $group
 * @property string $title
 * @property string|null $description
 * @property list<array{key: string, label: string}> $flags
 * @property list<string> $fields
 * @property list<string> $used_in
 * @property bool $allows_user_values
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'group', 'title', 'description', 'flags', 'fields', 'used_in', 'allows_user_values', 'position'])]
class CatalogList extends Model
{
    /** @use HasFactory<CatalogListFactory> */
    use HasFactory;

    /**
     * Per-value fields a list may declare.
     *
     * @var list<string>
     */
    public const FIELDS = ['code', 'color', 'icon', 'desc'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'fields' => 'array',
            'used_in' => 'array',
            'allows_user_values' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<CatalogValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CatalogValue::class, 'list_id')->orderBy('position');
    }

    /**
     * Whether this list uses a given per-value field.
     */
    public function usesField(string $field): bool
    {
        return in_array($field, $this->fields, true);
    }

    /**
     * The single flag this list declares, if any.
     *
     * The schema allows several because a user-added "Anulada por cliente"
     * marked final must behave correctly, but every seeded list declares at
     * most one and the editor renders one toggle per declared flag.
     *
     * @return array{key: string, label: string}|null
     */
    public function primaryFlag(): ?array
    {
        return $this->flags[0] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Catalogo::flush());
        static::deleted(fn () => Catalogo::flush());
    }
}
