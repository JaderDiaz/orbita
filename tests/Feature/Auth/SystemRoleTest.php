<?php

namespace Tests\Feature\Auth;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_defaults_to_the_member_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(SystemRole::Member, $user->system_role);
        $this->assertFalse($user->is_owner);
        $this->assertTrue($user->isMember());
        $this->assertFalse($user->isOwner());
        $this->assertFalse($user->isAdmin());
    }

    public function test_role_helpers_reflect_the_stored_role(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($owner->isOwner());
        // An Administrador also acts as a Miembro, so the owner does too.
        $this->assertTrue($owner->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOwner());
    }

    /**
     * The invariant lives in PostgreSQL, not only in PHP: a unique partial
     * index on is_owner. Breaking the owner is unrecoverable, so the database
     * is the last line of defence.
     */
    public function test_the_database_rejects_a_second_owner(): void
    {
        User::factory()->owner()->create();

        $this->expectException(QueryException::class);

        User::factory()->owner()->create();
    }

    /**
     * Check constraint: is_owner implies system_role = 'owner'.
     */
    public function test_the_database_rejects_an_owner_flag_without_the_owner_role(): void
    {
        $this->expectException(QueryException::class);

        User::factory()->create([
            'system_role' => SystemRole::Admin,
            'is_owner' => true,
        ]);
    }

    /**
     * Check constraint: system_role is restricted to the enum's values.
     *
     * Inserted raw on purpose. The Eloquent enum cast already rejects an
     * unknown value with a ValueError, so going through the model would never
     * reach PostgreSQL and the constraint would go untested.
     */
    public function test_the_database_rejects_an_unknown_role(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Rol invalido',
            'email' => 'rol-invalido@orbita.co',
            'password' => 'irrelevant',
            'system_role' => 'superadmin',
            'is_owner' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The Eloquent cast is the first guard, before the query is even built.
     */
    public function test_the_model_rejects_an_unknown_role(): void
    {
        $this->expectException(\ValueError::class);

        User::factory()->create(['system_role' => 'superadmin']);
    }
}
