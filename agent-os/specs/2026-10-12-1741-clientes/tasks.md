# Clientes — bitácora

Objetivo: el primer módulo de negocio — ficha del cliente, contactos, listado y detalle mínimo.
Rama: `feat/clientes` (desde `main`, que ya incluye Maestros y Listas).
Runner: `make test` (PHPUnit sobre Postgres real), `make check` para todas las compuertas.

## Tareas

| ID | Tarea | Estado | Commit |
| --- | --- | --- | --- |
| T1 | Guardar la documentación del spec | hecho | |
| T2 | Esquema: `clientes` y `cliente_contactos` | pendiente | |
| T3 | Lógica pura: tag, dígito de verificación, uso | pendiente | |
| T4 | Modelos, enum y factories | pendiente | |
| T5 | `ui/pagination` y el listado | pendiente | |
| T6 | Formulario con contactos | pendiente | |
| T7 | Detalle mínimo | pendiente | |
| T8 | Escrituras, autorización y rutas | pendiente | |
| T9 | Tests | pendiente | |

## Bitácora

**L1 — pedido del usuario, verbatim**

> /agent-os:shape-spec módulo de clientes, listar, crear, editar, eliminar, consulta el diseño y
> actualiza la información que tengas de este módulo para tener todo lo ultimo

**L2 — sobre "actualiza la información del diseño"**

Verificado en vez de asumido: `Prototipo Admin.dc.html` conserva el mismo `etag`
(`1791839678113816`) y el mismo tamaño (775 640 bytes) que cuando se extrajo el módulo. El diseño
no ha cambiado y la extracción guardada es la última. No hubo que releer 775 KB.

**L3 — fork de alcance resuelto por el usuario**

Ante tres opciones (ficha + contactos + detalle mínimo / ficha + contactos sin detalle / solo la
ficha), el usuario eligió **ficha + contactos + detalle mínimo**.
