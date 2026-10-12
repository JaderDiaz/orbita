<?php

namespace App\Models;

use App\Enums\CatalogColor;
use App\Enums\CatalogIcon;
use App\Support\Catalogo\Catalogo;
use App\Support\Catalogo\SystemKey;
use Database\Factories\CatalogValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One option inside an editable enumeration.
 *
 * @property int $id
 * @property int $list_id
 * @property string|null $system_key
 * @property string $name
 * @property string|null $code
 * @property CatalogColor|null $color
 * @property CatalogIcon|null $icon
 * @property string|null $description
 * @property array<string, bool> $flags
 * @property bool $is_default
 * @property bool $is_active
 * @property bool $is_system
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CatalogList $list
 */
#[Fillable(['list_id', 'system_key', 'name', 'code', 'color', 'icon', 'description', 'flags', 'is_default', 'is_active', 'is_system', 'position'])]
class CatalogValue extends Model
{
    /** @use HasFactory<CatalogValueFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => CatalogColor::class,
            'icon' => CatalogIcon::class,
            'flags' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CatalogList, $this>
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(CatalogList::class, 'list_id');
    }

    /**
     * Code-owned behaviour. Never compare against `name` with a literal: the
     * user is free to rename every value, and a rename must not change logic.
     *
     * Named `matches` rather than `is` because Eloquent already defines
     * `Model::is($model)` for comparing two models, and PHP will not accept an
     * incompatible override.
     */
    public function matches(SystemKey $key): bool
    {
        return $this->id === Catalogo::idFor($key);
    }

    /**
     * User-extensible semantics. A flag the agency sets on a value it added
     * behaves correctly with no deploy, which is why behaviour keys off this
     * rather than off an exhaustive list of system keys wherever it can.
     */
    public function flag(string $key): bool
    {
        return (bool) ($this->flags[$key] ?? false);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Catalogo::flush());
        static::deleted(fn () => Catalogo::flush());
    }
}
