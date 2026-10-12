<?php

use App\Enums\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('system_role', 20)->default(SystemRole::Member->value);
            $table->boolean('is_owner')->default(false);

            $table->index('system_role');
        });

        // The invariants live in the database, not only in PHP. Breaking the
        // owner is unrecoverable, so this is the last line of defence.
        $roles = implode(', ', array_map(
            fn (string $role): string => "'{$role}'",
            SystemRole::values(),
        ));

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_system_role_check
            CHECK (system_role IN ({$roles}))
        ");

        DB::statement('
            ALTER TABLE users
            ADD CONSTRAINT users_owner_implies_owner_role_check
            CHECK (NOT is_owner OR system_role = \'owner\')
        ');

        // Partial unique index: at most one row may carry is_owner = true.
        DB::statement('
            CREATE UNIQUE INDEX users_single_owner_index
            ON users (is_owner)
            WHERE is_owner
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_single_owner_index');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_owner_implies_owner_role_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_system_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['system_role']);
            $table->dropColumn(['system_role', 'is_owner']);
        });
    }
};
