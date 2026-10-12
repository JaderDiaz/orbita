# Standards para el shell fiel al diseño

Aplican los siguientes estándares a este trabajo.

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

## No aplican

- `backend/authorization`, `backend/catalog-master-data`, `backend/document-numbering`,
  `backend/money`, `backend/settings-parameters`, `backend/taxes-colombia` — no se toca PHP.
- `database/postgres` — no hay migraciones ni consultas.
- `testing/testing` — la convención del repo exime a los cambios de estilo y layout de tests
  nuevos. Los existentes deben seguir verdes.
