<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeds the catalog every other table references, then the demo accounts
     * declared in config/orbita.php, which is the same list the login screen
     * offers as quick-fill while in debug mode.
     *
     * updateOrCreate, not firstOrCreate: the seeder must also CORRECT an
     * account that already exists with the wrong role. firstOrCreate leaves an
     * existing row untouched, which silently left the owner as a member.
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        $password = Hash::make((string) config('orbita.demo_password'));

        /** @var list<array{name: string, email: string, role: SystemRole}> $accounts */
        $accounts = config('orbita.demo_accounts', []);

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'system_role' => $account['role'],
                    'is_owner' => $account['role'] === SystemRole::Owner,
                ],
            );
        }
    }
}
