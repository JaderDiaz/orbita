# Clientes — bitácora

Objetivo: el primer módulo de negocio — ficha del cliente, contactos, listado y detalle mínimo.
Rama: `feat/clientes` (desde `main`, que ya incluye Maestros y Listas).
Runner: `make test` (PHPUnit sobre Postgres real), `make check` para todas las compuertas.

## Tareas

| ID | Tarea | Estado | Commit |
| --- | --- | --- | --- |
| T1 | Guardar la documentación del spec | hecho | fbf33c7 |
| T2 | Esquema: `clientes` y `cliente_contactos` | hecho | 8d4e451 |
| T3 | Lógica pura: tag, dígito de verificación, uso | hecho | e009377 |
| T4 | Modelos, enum y factories | hecho | 193bb3d |
| T5 | `ui/pagination` y el listado | hecho | fdc57f7 |
| T6 | Formulario con contactos | hecho | fdc57f7 |
| T7 | Detalle mínimo | hecho | fdc57f7 |
| T8 | Escrituras, autorización y rutas | hecho | fdc57f7 |
| T9 | Tests | hecho | — |

T5 a T8 quedaron en un commit: la página no compila sin las rutas de Wayfinder y el test de la
ruta no pasa sin el componente de la página.

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

**L4 — tres defectos encontrados contra Postgres real**

1. PL/pgSQL compila un `CASE` como una sola expresión SQL, así que resolvía `NEW.cliente_id`
   también sobre el registro de `clientes`, que no tiene ese campo. Las ramas pasaron a ser `IF`,
   con `DELETE` aparte porque ahí no hay `NEW`, y un `UPDATE` que mueve un contacto entre clientes
   verifica ambos.
2. `RefreshDatabase` revierte su transacción, así que un trigger diferido nunca se dispara solo.
   Los tests piden `SET CONSTRAINTS ALL IMMEDIATE`, que además deja los constraints inmediatos
   para el resto de la transacción — por eso el rechazo aparece en el `UPDATE` y no después.
3. `unaccent()` es `STABLE`, no `IMMUTABLE`, y Postgres la rechaza dentro de un índice. De ahí el
   envoltorio `orbita_unaccent()`.

**L5 — un bug del navegador y un artefacto de herramienta**

- El efecto de búsqueda disparaba una petición al montar: la guarda de "primer render" no
  sobrevive a `strictMode`, que invoca los efectos dos veces. Ahora compara contra lo que el
  servidor ya tiene.
- El detalle se veía con la columna izquierda aplastada a 42 px y la portada en claro. **No era
  el código**: el build de producción sí contenía `.lg\:w-\[300px\]`. Era el escaneo de Tailwind
  del dev server, que no detectó los archivos nuevos sobre virtiofs. Se resuelve con
  `docker compose restart vite`.

**L6 — el DV del prototipo no verifica**

El NIT de ejemplo `830.114.921-7` verifica a **1**, no a 7. Los datos de muestra del prototipo no
pasarían la validación de la DIAN. Hay un test de regresión que lo fija.
