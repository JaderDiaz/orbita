<?php

use App\Enums\SystemRole;

return [

    /*
    |--------------------------------------------------------------------------
    | Demo accounts
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the seeded development logins, shared by
    | DatabaseSeeder and the login screen's quick-fill block. They mirror the
    | prototype's demo accounts. The login screen only ever exposes them while
    | the application is in debug mode.
    |
    */

    'demo_password' => env('ORBITA_DEMO_PASSWORD', 'orbita2026'),

    'demo_accounts' => [
        [
            'name' => 'Jader Díaz',
            'email' => 'owner@orbita.co',
            'role' => SystemRole::Owner,
        ],
        [
            'name' => 'Camila Rojas',
            'email' => 'camila@orbita.co',
            'role' => SystemRole::Member,
        ],
    ],

];
