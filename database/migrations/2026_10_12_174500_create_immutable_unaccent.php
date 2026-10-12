<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * An IMMUTABLE wrapper over unaccent(), so searches can be indexed.
     *
     * `unaccent(text)` is declared STABLE, not IMMUTABLE, because it reads a
     * dictionary that could in principle be reconfigured. Postgres therefore
     * refuses it inside an index expression, and an accent-insensitive search
     * without an index would sequential-scan every row.
     *
     * Naming the dictionary explicitly is what makes the wrapper honest: the
     * two-argument form resolves it at call time rather than depending on the
     * search path, which is the actual reason the one-argument form is only
     * STABLE.
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION orbita_unaccent(text)
            RETURNS text
            LANGUAGE sql
            IMMUTABLE
            STRICT
            PARALLEL SAFE
            AS \$\$ SELECT public.unaccent('public.unaccent', \$1) \$\$
        ");
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS orbita_unaccent(text)');
    }
};
