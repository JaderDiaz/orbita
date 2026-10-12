<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extensions the application depends on.
     *
     * `docker/postgres/init/01-init.sql` also creates pg_trgm and unaccent, but
     * that script only runs when Postgres initialises an EMPTY data volume. A
     * CI or production database created any other way would not have them, and
     * citext was never there at all even though the catalog schema needs it for
     * case-insensitive keys. Declaring them here makes the schema portable.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    /**
     * Left in place on purpose: dropping an extension would cascade into every
     * column typed with it, and other databases on the cluster may rely on it.
     */
    public function down(): void
    {
        //
    }
};
