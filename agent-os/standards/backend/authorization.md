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
3. **Attributes, fail closed** — a `Monetary` cast that returns `null` *and logs* when
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
