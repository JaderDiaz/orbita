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
