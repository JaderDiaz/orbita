# Estándares para Login y Layout

Contenido completo de los estándares que aplican a este trabajo, copiados de
`agent-os/standards/` al momento de crear el spec.

---

## backend/authorization

# Authorization

## System roles are a PHP enum, not master data

```php
users.system_role enum('owner','admin','member','client')
```

**"Roles del equipo"** (Frontend, Backend, Full stack, UI/UX, QA, Project manager) is a catalog
list of **job titles with zero authorization meaning**. Never conflate the two — a roles table
invites someone to invent a fourth role that bypasses the money boundary.

## A Miembro must never receive montos or costos

Defense in depth, in this order. UI hiding is not enforcement.

1. **Routes** — `routes/web.php` internal, `routes/portal.php` client, separate middleware
   groups. `#[Authorize]` attributes on controllers.
2. **Queries** — a global scope for members limiting projects to those they are assigned to,
   propagated to tareas, fases, tiempos and documentos. A member cannot reach another project's
   row even by ID.
3. **Attributes, fail closed** — a `Monetary` cast that returns `null` _and logs_ when
   `Gate::denies('finance.view')`. Money never reaches the serializer, even through a careless
   `->toArray()`.
4. **Separate read models** — members query an explicit column list with no money columns at
   all. Conditional field hiding in one shared Resource is how leaks happen.
5. **Tests** — a route × role matrix asserting status codes, plus an assertion that no member
   response payload contains a key matching `/(monto|costo|presupuesto|margen|tarifa|utilidad)/i`.

## Owner invariant

`users.is_owner` + a unique partial index + a DB check
`(not is_owner) or (system_role = 'owner' and access_state = 'active')` + `Gate::before` +
policies denying block/demote/delete on the owner. Deliberately redundant: breaking this is
unrecoverable.

## Users and state

- A user is usable only with an **Equipo y costos** record, or tied to a **Cliente** (portal).
- An Administrador **also acts as a Miembro** and can be assigned to projects.
- Blocking preserves history: `access_state` + `blocked_at`. Never delete users or team members.
  All FKs `on delete restrict`.
- Documents carry `visible` (default `false`) — it is the entire client data boundary. Audit
  every toggle.

---

## javascript/react-inertia

# React + Inertia

Inertia 3, React 19, TypeScript, Tailwind 4, shadcn/ui.

## Wayfinder

Routes are **generated at build time**. Importing a route that does not exist breaks
`pnpm run build`.

- Always import from `@/routes/*` or `@/actions/*`. Never hardcode a URL.
- After disabling any Fortify feature, audit with
  `grep -rhoE "from '@/routes[^']*'" resources/js/{pages,components,layouts}` and then run
  `pnpm run build` — the build is the only authoritative check.
- Note the trap: the forgot-password link imports `{ request } from '@/routes/password'`.
  Grepping for "forgot-password" does not find it.

## Structure

```
resources/js/pages/       one per screen, matches the Inertia render name
resources/js/features/    kanban/, command-palette/, markdown-editor/, filters/
resources/js/components/  shared; components/ui/ is shadcn, edit freely
resources/js/layouts/
resources/js/types/
```

Container/presentational: a `pages/*` component wires data and actions; the pieces it composes
take props and render. Keep data fetching out of presentational components.

## Rules

- Shared Inertia props stay minimal — user, role, nav counts, flash. Nothing else.
- Use Inertia deferred props and partial reloads for heavy tables instead of a custom API.
- Markdown is user input: render server-side with raw HTML disabled and sanitized. The client
  preview is a convenience, never the trust boundary.
- UI copy in Spanish (neutral). Code and identifiers in English.

---

## global/conventions

# Conventions

## Language

- **Code, identifiers, comments, commits, tests, docs: English.**
- **Domain vocabulary stays Spanish**, exactly as the business uses it: `Cliente`, `Cotizacion`,
  `Proyecto`, `Fase`, `Disciplina`, `Tarea`, `Factura`, `Pago`, `Egreso`, `Documento`.
  Do not translate these to Client/Quote/Project — the team speaks Spanish about the domain.
- **UI copy: Spanish (neutral, no regional slang).**

## Structure

Idiomatic Laravel. No `app/Modules/`, no `Domain/Application/Infrastructure` layers.

```
app/Models/            Eloquent models
app/Actions/<Domain>/  single-purpose writes that touch more than one table
app/Support/           value objects and pure logic (Money, Impuestos, Catalogo, Parametros)
app/Casts/             custom Eloquent casts
app/Policies/          one per model
app/Http/Requests/     validation
```

Pure logic that must be unit-testable without a database belongs in `app/Support`, never in a
controller or model method.

## Commits

Conventional Commits. No AI attribution, no `Co-Authored-By`.

---

## database/postgres

# PostgreSQL

- PostgreSQL 18. Extensions `pg_trgm` and `unaccent` (they back the ⌘K palette search).
- **Tests run against real Postgres, never SQLite.** Behavior this app depends on — partial
  unique indexes, check constraints, `citext`, jsonb operators, recursive CTEs — does not exist
  or differs in SQLite.
- Put invariants in the database, not only in PHP:
    - `on delete restrict` on every FK that protects history
    - partial unique indexes (`unique (list_id) where is_default`)
    - check constraints for enum-like text columns and the owner invariant
    - a statement-level trigger validating `sum(fases.pct) = 100` per project
    - a trigger rejecting updates to an issued financial document
- **Derive, don't store, what is a function of other columns.** `Vencida` is
  `vence < today AND saldo > 0`, not a stored state. Store lifecycle states only.
- Compute rollups with views (`project_profitability`, `client_balances`,
  `member_utilization`). Materialize only when measured slow, with a refresh job — never
  hand-maintained counter columns.
- Filtering, sorting and pagination are **always server-side**, with supporting indexes.
  Cursor pagination for `time_entries`.
- Single-tenant: no `tenant_id`, no global tenancy scope. If tenancy ever arrives, choose
  database-per-tenant, not row-level.

---

## testing/testing

# Testing

Pest. Larastan level 8 globally, max on `app/Support`. Pint with the Laravel preset.

## What to test, in this order

This application touches money. Priority is not negotiable:

1. Tax computation — including ReteICA as **per mil** and ReteIVA based on **the IVA amount**
2. Rounding of totals and payment/saldo arithmetic, including partial payments
3. Numbering under concurrency — issue 50 in parallel, assert no duplicates
4. Issued-document immutability
5. The route × role authorization matrix, and that no member payload leaks monetary keys
6. The rentabilidad formula
7. `sum(fases.pct) = 100` and rejection of task dependency cycles

## How

- Unit tests with **no database** for `app/Support` (Money, Impuestos, numbering, dependency
  graph). They run in microseconds and catch the bugs that matter most.
- Feature tests against **real Postgres**.
- Browser tests for exactly three flows: kanban drag with the horas-reales prompt, the timer
  start/pause/commit cycle, and the quote-send checklist.
- Architecture tests: no `catalog_values.name` string literals, no `float` in money paths, no
  monetary keys in member responses.

## What not to test

Catalog and settings CRUD beyond one representative list. Inertia prop snapshots. Markdown
toolbar buttons. Chart rendering. Formatting helpers beyond one locale smoke test. Coverage
percentage as a goal.

---

## No aplican

`backend/money`, `backend/taxes-colombia`, `backend/document-numbering`,
`backend/catalog-master-data`, `backend/settings-parameters` — este spec no toca dinero,
impuestos, numeración de documentos, datos maestros ni parámetros.
