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
