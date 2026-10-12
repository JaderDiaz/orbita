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
