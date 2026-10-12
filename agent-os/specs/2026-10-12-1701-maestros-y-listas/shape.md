# Maestros y Listas — Notas de shaping

## Alcance

Construir el catálogo de enumeraciones editables del que depende todo el resto del producto:
tablas, semilla completa, resolver de `system_key`, conteo de uso real y la pantalla de
administración.

**Entra**

- Extensiones `citext`, `pg_trgm`, `unaccent` por migración
- `catalog_lists` y `catalog_values` con los invariantes del estándar en Postgres
- Semilla completa: 16 listas, 7 grupos, 73 valores extraídos del prototipo
- `system_key` + resolver cacheado + enums de PHP para el comportamiento que el código posee
- `flags` jsonb para la semántica extensible por el usuario
- `UsageRegistry` — un `UNION ALL` cacheado, sin triggers ni columnas contador
- Pantallas: maestro/detalle, editor en línea, desactivación y borrado bloqueado
- Primitivos faltantes: `ui/table`, `ui/pagination`, y los 8 pares de badge semánticos
- Sub-nav: abrir Configuración cuando la URL es de un hijo, y marcar el sub-ítem activo
- Policies, rutas, y encender `maestros` en el nav
- Test de arquitectura que prohíbe comparar `catalog_values.name` con un literal

**No entra**

- Clientes — es el spec siguiente
- Resto de Phase 0: Parámetros, audit trail, `Money`, esqueleto de ⌘K
- Cablear las listas a los módulos que aún no existen

## Decisiones

| Decisión | Razón |
| --- | --- |
| **Maestros completo antes de Clientes** | Elección del usuario ante un fork explícito. Es el orden del roadmap: retrofitear llaves foráneas a `catalog_values` es, en sus palabras, "the most expensive mistake available". |
| Extensiones por **migración**, no por el script de init de Docker | `docker/postgres/init/01-init.sql` crea `pg_trgm` y `unaccent`, pero corre solo al inicializar un volumen vacío. Una base de CI o de producción no creada por ese script no las tendría. |
| **`citext` se agrega** | Verificado contra la base real: solo estaban `pg_trgm`, `plpgsql` y `unaccent`. `citext` está disponible en la imagen (1.8) pero no instalada, y el estándar lo usa en `key citext unique` y `system_key citext`. Sin esto, cualquier columna `citext` falla. |
| **Se agrega lo que el prototipo no modela**: `system_key`, `is_system`, `position` | El prototipo une todo por el label string. Renombrar un valor huerfaniza cada registro existente, y deja borrables valores que sostienen lógica. El estándar ya exige estas columnas. |
| **El color se guarda como clave de paleta**, no como hex libre | El prototipo ofrece dos `input type="color"` libres, con lo que blanco sobre blanco es alcanzable. La semilla solo usa las 8 entradas de la paleta. |
| **El conteo de uso es un `COUNT` real** | En el prototipo `uso` es dato semilla que nunca se actualiza: un valor usado en 50 registros sigue diciendo `Sin uso` y es borrable. |
| **Reordenar por columna `position`** | El prototipo intercambia vecinos del arreglo completo mientras el usuario ve una vista filtrada, así que con filtro activo nada se mueve visualmente. |
| Editor y confirmación de borrado **en línea**, no modales | Es lo que hace el prototipo y mantiene el contexto de la fila a la vista. |
| Borrado **bloqueado**, desactivación siempre permitida | Estándar y prototipo coinciden. La FK `on delete restrict` lo garantiza en la base. |

## Contexto

- **Visuales:** proyecto de Claude Design `4ba0fa0d-992f-4439-a02a-311350030ea9`. Ver
  `visuals/README.md` para los rangos de línea exactos.
- **Referencias de código:** el shell y los tokens construidos en el spec de Login y Layout.
  Ver `references.md`.
- **Alineación con el producto:** cierra la pieza de Phase 0 de la que depende Phase 1.

## Pedido original del usuario

Verbatim del transcript del diseño, 2026-10-10:

> "Del mismo modo optimina la vista de "Maestros y Listas" agrupandolos y optmizandolos para que
> podamos tener control de las mismas, las listas pueden tener metadatos relacionados segun donde
> se use pueden tener parametros adicionales por ejempli las etiquetas pueden tener color primario
> y secunfario para hacer bages, y otros pueden tener iconos para los timpos de tareas por ejempli,
> o los tipos de documento pueden tener codigos para usar en vistas mas cortas, evalua las listas
> que usamos a lo largo del desarrollo del panel de adminsitrador y optimiza esta vista a criterio,
> sobra, funcional, y con el CRUD includo por listas."

El usuario nunca pidió `system_key`, `is_system`, `allows_user_values`, reordenar arrastrando ni
bitácora de auditoría. Las tres primeras se agregan porque el estándar las exige; las dos últimas
quedan fuera.

## Estándares aplicados

- `backend/catalog-master-data` — es el estándar central de este spec: define el esquema, el uso de
  `system_key` y `flags`, y la prohibición de comparar por `name`.
- `backend/authorization` — la pantalla es solo para administrativos, y estos datos cambian el
  tratamiento fiscal, así que la frontera importa.
- `database/postgres` — invariantes en la base, filtrado y paginación server-side.
- `javascript/react-inertia` — estructura de Inertia 3, Wayfinder y el corte contenedor/presentación.
- `global/conventions` — inglés en el código, español en el dominio y en la UI.
- `testing/testing` — qué se prueba y qué explícitamente no.
