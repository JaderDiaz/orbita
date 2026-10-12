# Tech Stack Standard

PHP 8.5 · Laravel 13 · PostgreSQL 18 · Redis 8
Inertia 3 · React 19 · TypeScript · Tailwind 4 · shadcn/ui · Vite 8 · pnpm
Pest · Larastan · Pint
Docker Compose (FrankenPHP app container) on Colima

Authentication: **Laravel Fortify, login + 2FA only.** No registration, no password recovery,
no passkeys — users are provisioned by an Administrador.

Architecture: **idiomatic Laravel.** No hexagonal layering, no `app/Modules/`.
