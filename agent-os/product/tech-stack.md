# Tech Stack

## Frontend

- **Inertia 3** + **React 19** + **TypeScript** (Laravel React starter kit)
- **Tailwind 4**, **shadcn/ui** components
- **Vite 8**, **Wayfinder** for typed routes, **pnpm**
- `sonner` for toasts, `input-otp` for 2FA
- Design: Geist + Geist Mono, single orange accent `#F97316`, no dark mode for now

## Backend

- **PHP 8.5**, **Laravel 13**
- **Laravel Fortify** for authentication — login + 2FA only. No registration, no password
  recovery, no passkeys. Users are provisioned by the Administrador.
- **Redis** for cache, session and queues
- Architecture: **idiomatic Laravel**. No hexagonal layering. Business logic in Models,
  Actions, Policies, FormRequests and value objects under `app/Support`.

## Database

- **PostgreSQL 18**, extensions `pg_trgm` + `unaccent` (they back the ⌘K palette search)
- Single-tenant: no `tenant_id`, no global tenancy scope
- Tests run against real Postgres, never SQLite

## Testing & Quality

- **Pest**, **Larastan** (level 8 globally, max on `app/Support`), **Pint**

## Local environment

Docker Compose via **Colima**. Services: `app` (FrankenPHP, 8080), `postgres` (**5433** — 5432
is taken on this host), `redis`, `vite` (5173), `mailpit` (8025), `pgtest` (5434, tmpfs).
`vendor/` and `node_modules/` live in named volumes, not bind mounts.
