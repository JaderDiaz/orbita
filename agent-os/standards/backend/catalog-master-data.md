# Maestros y Listas (master data)

Enumerations are **user-editable rows, not PHP enums**. The agency manages its own estados,
disciplinas, categorías and etiquetas without a deploy.

```sql
catalog_lists(id, key citext unique, "group", title, description,
              flags jsonb,   -- [{key:'is_final', label:'Estado final'}]
              fields jsonb, used_in jsonb, allows_user_values bool)

catalog_values(id, list_id references catalog_lists on delete restrict,
               system_key citext null,    -- immutable key the code owns
               name, code, color, icon, description,
               flags jsonb,               -- {"is_final": true}
               is_default bool, is_active bool, is_system bool, position int)

unique (list_id, lower(name));
unique (list_id, lower(code)) where code is not null;
unique (list_id, system_key) where system_key is not null;
unique (list_id) where is_default;
```

## Rules

- Transactional tables reference `*_value_id references catalog_values on delete restrict`.
  Postgres then guarantees a value in use cannot be deleted.
- **Deletion is blocked; deactivation is always allowed.** Nobody needs to delete "Pausado" —
  they need it to stop appearing in pickers. History keeps its value.
- **Never compare against `name` with a string literal.** Gate behavior on `system_key`
  (via a generated PHP enum + cached resolver) or on `flags`:

```php
// wrong
if ($cotizacion->estado->name === 'Aprobada') { ... }

// right — code-owned behavior
if ($cotizacion->estado->is(EstadoCotizacion::Aprobada)) { ... }

// right — user-extensible semantics
if ($cotizacion->estado->flag('is_final')) { ... }
```

- `flags` is jsonb keyed by flag key, so a user-added "Anulada por cliente" marked final
  behaves correctly with no deploy. Design for N flags per list, not one.
- `match` over `system_key` must be exhaustive with `default => throw`, so a user-added value
  never silently falls through a financial branch.
- Usage counts come from a code-side `UsageRegistry` (one cached `UNION ALL`). No triggers, no
  counter columns.
- Values are referenced by FK, **not stamped** onto records. Snapshot only on issued financial
  documents, where legal immutability requires it.
