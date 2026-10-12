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
     * Credentials mirror the prototype's demo accounts so the seeded data
     * matches the design that is being implemented.
     */
    private const DEMO_PASSWORD = 'orbita2026';

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@orbita.co'],
            [
                'name' => 'Jader Díaz',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'system_role' => SystemRole::Owner,
                'is_owner' => true,
            ],
        );

        User::firstOrCreate(
            ['email' => 'camila@orbita.co'],
            [
                'name' => 'Camila Rojas',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'system_role' => SystemRole::Member,
                'is_owner' => false,
            ],
        );
    }
}
