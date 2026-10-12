<?php

use App\Enums\CatalogColor;
use App\Enums\CatalogIcon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_lists', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('group', 60);
            $table->string('title', 120);
            $table->text('description')->nullable();

            // Declared flags: [{"key": "is_final", "label": "Estado final"}].
            // A list may declare several, so this is a list and not one column.
            $table->jsonb('flags')->default('[]');

            // Which per-value fields this list uses: code, color, icon, desc.
            $table->jsonb('fields')->default('[]');

            // Screens that consume the list. Declarative, written by hand.
            $table->jsonb('used_in')->default('[]');

            $table->boolean('allows_user_values')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['group', 'position']);
        });

        Schema::create('catalog_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('catalog_lists')->restrictOnDelete();

            // The immutable key the code owns. Null for user-created values.
            $table->string('system_key', 80)->nullable();

            $table->string('name', 120);
            $table->string('code', 5)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('icon', 20)->nullable();
            $table->text('description')->nullable();

            // Resolved flag values keyed by flag key: {"is_final": true}.
            $table->jsonb('flags')->default('{}');

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['list_id', 'position']);
        });

        // Case-insensitive keys. The catalog is addressed by these from code,
        // so `Responsable de IVA` and `responsable de iva` must not coexist.
        DB::statement('ALTER TABLE catalog_lists ALTER COLUMN key TYPE citext');
        DB::statement('ALTER TABLE catalog_values ALTER COLUMN system_key TYPE citext');

        $this->addValueInvariants();
    }

    /**
     * The invariants live in the database, not only in PHP.
     *
     * These rows are referenced by every transactional table in the product, so
     * a duplicate name, two defaults in one list, or a deactivated system value
     * would corrupt data no application-level guard could recover.
     */
    private function addValueInvariants(): void
    {
        // Names and codes are unique per list, case-insensitively. Done as
        // expression indexes rather than citext columns because the user is
        // free to rename and recase these; only the keys are addressed by code.
        DB::statement('
            CREATE UNIQUE INDEX catalog_values_list_name_index
            ON catalog_values (list_id, lower(name))
        ');

        DB::statement('
            CREATE UNIQUE INDEX catalog_values_list_code_index
            ON catalog_values (list_id, lower(code))
            WHERE code IS NOT NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX catalog_values_list_system_key_index
            ON catalog_values (list_id, system_key)
            WHERE system_key IS NOT NULL
        ');

        // At most one default per list. Not "exactly one": Disciplinas ships
        // with none on purpose, because no discipline is the obvious choice.
        DB::statement('
            CREATE UNIQUE INDEX catalog_values_single_default_index
            ON catalog_values (list_id)
            WHERE is_default
        ');

        $colors = $this->quoted(CatalogColor::values());
        $icons = $this->quoted(CatalogIcon::values());

        DB::statement("
            ALTER TABLE catalog_values
            ADD CONSTRAINT catalog_values_color_check
            CHECK (color IS NULL OR color IN ({$colors}))
        ");

        DB::statement("
            ALTER TABLE catalog_values
            ADD CONSTRAINT catalog_values_icon_check
            CHECK (icon IS NULL OR icon IN ({$icons}))
        ");

        // A value the code addresses must carry the key it is addressed by.
        DB::statement('
            ALTER TABLE catalog_values
            ADD CONSTRAINT catalog_values_system_implies_key_check
            CHECK (NOT is_system OR system_key IS NOT NULL)
        ');

        // A system value can be renamed and recoloured but never retired: code
        // branches on it, so deactivating it would break a financial path.
        DB::statement('
            ALTER TABLE catalog_values
            ADD CONSTRAINT catalog_values_system_stays_active_check
            CHECK (NOT is_system OR is_active)
        ');

        // A hidden default would silently preselect nothing in every form.
        DB::statement('
            ALTER TABLE catalog_values
            ADD CONSTRAINT catalog_values_default_is_active_check
            CHECK (NOT is_default OR is_active)
        ');
    }

    /**
     * @param  list<string>  $values
     */
    private function quoted(array $values): string
    {
        return implode(', ', array_map(
            fn (string $value): string => "'{$value}'",
            $values,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_values');
        Schema::dropIfExists('catalog_lists');
    }
};
