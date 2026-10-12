# Product Mission

## Problem

A software development agency runs its whole commercial and delivery lifecycle across
disconnected tools: quotes in spreadsheets, task tracking in a generic board, hours nowhere,
invoices by hand, and profitability guessed at after the fact. Nobody can answer "did this
project make money?" without rebuilding the numbers manually.

## Target Users

Internal only. Three roles:

- **Administrador (Owner)** — full access: finanzas, configuración, facturación, equipo,
  creating and closing projects. Also acts as a Miembro and can be assigned to projects.
- **Miembro del equipo** (Dev / Diseñador) — only assigned projects. Moves tasks, reports
  issues, registers time, uploads documents. Must never see montos ni costos.
- **Cliente** — read-only portal: approve quotes, see progress, download cuentas de cobro.
  Deferred to phase 2; the authorization boundary is built from the start.

## Solution

One system covering the chain end to end: **prospección → cotización → ejecución → entrega
documental → facturación → análisis de rentabilidad**.

What makes it different from a generic PM tool:

- **Phase-based billing.** A project splits into fases, each carrying a percentage of the
  budget, an acta de entrega, and at most one invoice. Accepting a fase is gated on uploading
  the signed acta.
- **Real profitability, not estimates.** Facturado − (Σ horas × costo/hora del miembro + Σ
  egresos del proyecto), computed from time entries that snapshot the member's rate at the
  moment they were logged.
- **Colombian fiscal reality.** NIT/RUT, régimen, IVA, Retefuente, ReteICA, ReteIVA, cuentas
  de cobro, per-client document numbering.
- **Enumerations are data, not code.** Maestros y Listas lets the agency manage its own
  estados, disciplinas, categorías and etiquetas without a deploy.
