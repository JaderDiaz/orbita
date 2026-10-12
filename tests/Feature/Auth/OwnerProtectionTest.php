<?php

namespace Tests\Feature\Auth;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "El propietario siempre es administrador" and cannot be blocked. Deliberately
 * redundant with the database constraints, because this one is unrecoverable.
 */
class OwnerProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_is_granted_every_ability(): void
    {
        $owner = User::factory()->owner()->create();

        // An ability nobody defined: Gate::before must still grant it.
        $this->assertTrue($owner->can('an-ability-that-does-not-exist'));
    }

    public function test_a_plain_member_is_not_granted_unknown_abilities(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse($member->can('an-ability-that-does-not-exist'));
    }

    public function test_the_owner_cannot_be_deleted_blocked_or_demoted(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', $owner));
        $this->assertFalse($admin->can('block', $owner));
        $this->assertFalse($admin->can('changeRole', $owner));
    }

    public function test_the_owner_cannot_demote_itself(): void
    {
        $owner = User::factory()->owner()->create();

        // Gate::before would otherwise allow the owner everything, so the
        // policy must be consulted for these three abilities specifically.
        $this->assertFalse($owner->can('changeRole', $owner));
        $this->assertFalse($owner->can('block', $owner));
        $this->assertFalse($owner->can('delete', $owner));
    }

    public function test_an_admin_can_manage_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue($admin->can('block', $member));
        $this->assertTrue($admin->can('changeRole', $member));
    }

    public function test_a_member_cannot_manage_other_users(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create();

        $this->assertFalse($member->can('block', $other));
        $this->assertFalse($member->can('changeRole', $other));
        $this->assertFalse($member->can('viewAny', User::class));
    }

    /**
     * Regression: the seeder used firstOrCreate, which leaves an existing row
     * untouched. An account created earlier without a role stayed a member,
     * and the installation ended up with no owner at all.
     */
    public function test_the_seeder_corrects_an_existing_account_with_the_wrong_role(): void
    {
        User::factory()->create([
            'email' => 'owner@orbita.co',
            'system_role' => SystemRole::Member,
            'is_owner' => false,
        ]);

        $this->seed();

        $owner = User::where('email', 'owner@orbita.co')->sole();

        $this->assertSame(SystemRole::Owner, $owner->system_role);
        $this->assertTrue($owner->is_owner);
    }

    public function test_the_seeder_creates_exactly_one_owner(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('is_owner', true)->count());
        $this->assertSame(1, User::where('system_role', SystemRole::Owner)->count());
        $this->assertDatabaseHas('users', ['email' => 'owner@orbita.co', 'is_owner' => true]);
        $this->assertDatabaseHas('users', ['email' => 'camila@orbita.co', 'system_role' => 'member']);
    }
}
