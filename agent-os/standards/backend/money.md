# Money

**Store money as `bigint` minor units (centavos) plus a `currency char(3)` column. Never float.**

```php
// migration
$table->bigInteger('total_minor');
$table->char('currency', 3)->default('COP');
```

- Unit prices use `numeric(14,4)` — hourly rates and fractional quantities need the precision.
- Every *total* is `bigint` minor units.
- Arithmetic goes through `brick/money`. Never `+`/`*` on raw floats.
- A stored total is **never recomputed on read**. Persist every intermediate.
- Format server-side with `NumberFormatter('es_CO')` so PDFs and screens cannot diverge.

Column naming: suffix `_minor` on every minor-unit column, so a missing conversion is visible
at the call site.
