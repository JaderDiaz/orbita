# Document Numbering

```sql
document_series(id, key citext unique,   -- 'COT:ACM', 'FAC:ACM', 'PRY:2026', 'EG'
                prefix, scope_type, scope_id, period int null,
                next_value int default 1, padding int default 4,
                resolution_number, range_from, range_to, valid_until)  -- DIAN, empty in v1
```

Allocate atomically in a single statement — no explicit locks:

```sql
INSERT INTO document_series (key, ...) VALUES (?, ...)
ON CONFLICT (key) DO UPDATE SET next_value = document_series.next_value + 1
RETURNING next_value;
```

## Rules

- **Assign the number at issue, never at draft.** Drafts carry `number null`. This is what keeps
  a series gap-free and makes an abandoned draft cost nothing.
- Allocation runs inside the issuing transaction.
- `unique(series_id, number)` plus `unique(document_type, number)` as a backstop.
- The number is **stored text**, never derived. Changing a client's Tag must not renumber
  existing documents.
- Formats: `COT-{TAG}-{0000}` and `FAC-{TAG}-{0000}` per client, `PRY-{year}-{000}`, `EG-{0000}`.
- A per-client series is valid for a **cuenta de cobro**. A DIAN factura electrónica needs one
  consecutive series inside an authorized resolution range — its numbers come from the provider.
