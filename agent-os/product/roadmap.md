# Product Roadmap

Each phase is independently deployable and useful standing alone.

## Phase 0 — Foundations (current)

Scaffolding, Docker stack, auth with 3 roles and the owner invariant, **Maestros y Listas**,
**Parámetros**, audit trail, app shell, ⌘K skeleton, `Money` support.

Everything else holds foreign keys to catalog values and reads parameters. Retrofitting these
is the most expensive mistake available.

## Phase 1 — Clientes y Documentos

Clientes + contactos (multiple, min one, one principal) + Documentos with storage, v1/v2/v3
versioning and the client-visibility flag. Useful immediately as CRM-lite; documents are
required by fases and egresos.

## Phase 2 — Cotizaciones

Cotizaciones + per-client numbering (`COT-{TAG}-{0000}`) + PDF + sending over Enlace/Correo +
the completeness checklist. Closes the first revenue-facing loop.

## Phase 3 — Proyectos y ejecución

Proyectos + fases (percentages must sum to 100) + tareas + dependencies + kanban +
server-authoritative timer + time entries. The daily-use core; the team can be onboarded.

## Phase 4 — Facturación

Facturas + impuestos + pagos + egresos + issued-document immutability + fase→factura
generation. Money, built on primitives already frozen.

## Phase 5 — Analítica

Dashboard KPIs, rentabilidad views, "Requiere atención" alert feed, full ⌘K index. Needs
phases 1–4 populated to mean anything.

## Phase 6 — Portal del cliente

Views over the authorization boundary already built in phase 0.

## Later, independently schedulable

- **DIAN factura electrónica** via an authorized provider (Factus / Alegra / Siigo / Dataico).
  v1 issues cuentas de cobro only; the `FiscalDocumentProvider` port is defined but unimplemented.
- **WhatsApp** delivery channel (WABA template approval is a weeks-long business process).
- **Mobile API** — every write is an Action class so `routes/api.php` can reuse the logic.
