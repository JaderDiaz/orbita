# Clientes — Notas de shaping

## Alcance

El primer módulo de negocio del producto: la ficha del cliente con su identidad fiscal, sus
contactos, el listado y el detalle.

**Entra**

- `clientes` y `cliente_contactos` con sus invariantes en Postgres
- Identidad fiscal: tipo de persona, tipo de documento, documento, régimen, razón social, dirección
- Dígito de verificación del NIT, calculado y validado
- Ciudad y departamento como campos propios
- `tag` de 3 caracteres, único, que alimentará `COT-{TAG}-0000` y `FAC-{TAG}-0000`
- Contactos: varios, mínimo uno, exactamente un principal, garantizado por la base
- Listado con búsqueda, filtro, orden y paginación del lado del servidor
- Formulario de alta y edición con el resumen lateral del prototipo
- Detalle mínimo: portada, ficha fiscal, contactos y notas
- Archivar y eliminar con la regla del prototipo
- `ui/pagination`
- Clientes como primer consumidor de `UsageRegistry`

**No entra**

- Documentos del cliente — la otra mitad de Phase 1
- Los 4 KPIs y las 5 pestañas del detalle
- Las columnas Proyectos y Facturado del listado
- Portal del cliente (`users.cliente_id`), que es Phase 6
- Editor Markdown con barra de herramientas

## Decisiones

| Decisión | Razón |
| --- | --- |
| **Contactos entran** | Elección del usuario ante un fork explícito. El roadmap los pone en la misma fase, y meter después la regla "mínimo uno, un principal" obliga a migrar cada cliente ya creado. |
| **Detalle sin KPIs ni pestañas** | Elección del usuario. Las pestañas se completan cuando existan los módulos que las alimentan; una pestaña siempre vacía no informa nada. |
| **La identidad es el `id`, nunca el nombre** | El prototipo usa `key: c.empresa` y une proyectos, cotizaciones, facturas y documentos por el string del nombre. Renombrar un cliente huerfanaría todo su historial. |
| **Se valida el dígito de verificación del NIT** | El prototipo acepta cualquier texto. Un NIT con DV incorrecto es una factura rechazada por la DIAN. Es aritmética módulo 11, barata y testeable sin base de datos. |
| **Ciudad y departamento separados** | El ReteICA es municipal, así que la ubicación deja de ser decorativa en cuanto llegue Facturación. |
| **El `tag` resuelve colisiones** | `genTag()` genera el mismo tag para dos empresas que empiecen igual, y el tag es el prefijo de numeración. Dos `FAC-TRA-0001` es un problema fiscal. |
| **Notas como texto plano** | El prototipo las pasa por `dangerouslySetInnerHTML` con un conversor hecho a mano que emite `href` crudos: XSS y URLs `javascript:`. El render sanitizado del lado del servidor es trabajo propio del editor Markdown. |
| **Eliminar solo sin historial; si no, archivar** | Regla del prototipo que coincide con el estándar. Reusa lo aprendido en Maestros, incluido que `ON DELETE RESTRICT` lanza SQLSTATE 23001. |
| **El estado es un booleano, no una lista** | Activo/Inactivo es binario y el prototipo no lo modela como maestro. Una lista de dos valores invitaría a inventar un tercero que ningún código entiende. |

## Contexto

- **Visuales:** proyecto de Claude Design `4ba0fa0d-992f-4439-a02a-311350030ea9`. Ver
  `visuals/README.md` para los rangos de línea exactos. Verificado que el archivo no ha cambiado
  desde la extracción: mismo `etag` y mismo tamaño.
- **Referencias de código:** el módulo de Maestros y Listas, construido en el spec anterior, es la
  implementación de referencia para casi todo. Ver `references.md`.
- **Alineación con el producto:** es Phase 1 del roadmap, desbloqueada por el cierre de Phase 0.

## Estándares aplicados

- `backend/authorization` — el módulo es solo para administrativos y un miembro no debe alcanzarlo.
- `backend/catalog-master-data` — tipo de documento y régimen son llaves foráneas a
  `catalog_values` con `on delete restrict`, y nunca se comparan por nombre.
- `backend/document-numbering` — el `tag` es el prefijo por cliente de las series de cotización y
  factura, y cambiarlo no debe renumerar documentos existentes.
- `database/postgres` — invariantes en la base, filtrado y paginación del lado del servidor,
  búsqueda con `pg_trgm` y `unaccent`.
- `javascript/react-inertia` — Inertia 3, Wayfinder, corte contenedor/presentación.
- `global/conventions` — `Cliente` en español, inglés en el código, UI en español.
- `testing/testing` — qué se prueba y qué explícitamente no.
