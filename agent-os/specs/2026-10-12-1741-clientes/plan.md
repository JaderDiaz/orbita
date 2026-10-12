# Orbita — Clientes

## Context

Este es el módulo que pediste hace dos ciclos y que quedó diferido. La exploración de entonces
encontró que Clientes necesitaba llaves foráneas a `catalog_values`, tablas que no existían, y
elegiste cerrar **Maestros y Listas** primero. Eso ya está hecho y mergeado en `main`.

Hoy el bloqueo está levantado: `tipos-documento` y `regimenes` están sembradas y listas para
referenciar, hay cinco Actions de referencia, el resolver del catálogo y `UsageRegistry` existen, y
`ui/table` está construido.

**Sobre "consulta el diseño y actualiza la información"**: verifiqué el prototipo contra lo que ya
había extraído. El `etag` y el tamaño de `Prototipo Admin.dc.html` son idénticos
(`1791839678113816`, 775 640 bytes), así que **el diseño no ha cambiado** y la extracción guardada
es la última. No hubo que releer 775 KB.

Resultado buscado: un administrador da de alta un cliente con su identidad fiscal y sus contactos,
lo busca en un listado paginado del lado del servidor, lo edita, y lo archiva o lo elimina según
tenga historial. Es el primer módulo de negocio del producto.

## Alcance

Decidido por ti: **ficha + contactos + detalle mínimo**.

**Entra**

- Tablas `clientes` y `cliente_contactos` con sus invariantes en Postgres
- Identidad fiscal: tipo de persona, tipo de documento, documento, régimen, razón social, dirección
- **Dígito de verificación del NIT** calculado y validado (el prototipo no lo valida)
- **Ciudad y departamento** como campos propios (el prototipo solo tiene ciudad, texto libre)
- `tag` de 3 caracteres, único, que alimentará `COT-{TAG}-0000` y `FAC-{TAG}-0000`
- Contactos: varios, mínimo uno, exactamente un principal — garantizado por la base
- Listado con búsqueda, orden y paginación **del lado del servidor**
- Formulario de alta y edición en página completa, con el resumen lateral del prototipo
- Detalle mínimo: portada, ficha fiscal, contactos y notas
- Archivar e eliminar con la misma regla del prototipo
- `ui/pagination`, que quedó pendiente del spec anterior
- Clientes como **primer consumidor de `UsageRegistry`**

**No entra**

- Documentos del cliente — es la otra mitad de Phase 1, con versionado y visibilidad propios
- Los 4 KPIs y las 5 pestañas del detalle: Proyectos, Cotizaciones, Facturas y Documentos no
  existen, y una pestaña que siempre está vacía no informa nada
- Las columnas **Proyectos** y **Facturado** del listado, por lo mismo
- Portal del cliente (`users.cliente_id`), que es Phase 6
- Editor Markdown con barra de herramientas

## Decisiones tomadas

| Decisión | Razón |
|---|---|
| **Contactos entran** | Tu elección. El roadmap los pone en la misma fase, y meter después la regla "mínimo uno, un principal" obliga a migrar cada cliente ya creado. |
| **Detalle sin KPIs ni pestañas** | Tu elección. Se completa cuando existan los módulos que alimentan esas pestañas. |
| **El identificador es el `id`, nunca el nombre** | El prototipo usa `key: c.empresa` y une proyectos, cotizaciones, facturas y documentos por el string del nombre. Renombrar un cliente huerfanaría todo su historial. |
| **Se valida el dígito de verificación del NIT** | El prototipo acepta cualquier texto con el marcador `900.000.000-0`. Un NIT con DV incorrecto es una factura rechazada por la DIAN. Es aritmética módulo 11, barata y unit-testeable sin base de datos. |
| **Ciudad y departamento separados** | El prototipo solo tiene `ciudad` como texto libre. El **ReteICA es municipal**, así que la ubicación deja de ser decorativa en cuanto llegue Facturación. |
| **El `tag` resuelve colisiones** | `genTag()` del prototipo genera el mismo tag para dos empresas que empiecen igual, y el tag es el prefijo de numeración. Dos `FAC-TRA-0001` es un problema fiscal. Se desambigua al generar y lo garantiza un índice único. |
| **Las notas se guardan y muestran como texto plano** | El prototipo las pasa por `dangerouslySetInnerHTML` con un conversor hecho a mano que emite `href` crudos: agujero de XSS y de URLs `javascript:`. El estándar exige render del lado del servidor y sanitizado, que es trabajo propio del editor Markdown. Texto plano con saltos de línea elimina la superficie hoy. |
| **Eliminar solo sin historial; si no, archivar** | Es la regla del prototipo y coincide con el estándar. Se reusa exactamente lo aprendido en Maestros, incluido que un `ON DELETE RESTRICT` lanza **SQLSTATE 23001**, no 23503. |
| **El estado es un booleano, no una lista** | Activo/Inactivo es binario y el prototipo no lo modela como maestro. Una lista de dos valores invitaría a inventar un tercero que ningún código entiende. |

## El modelo

```sql
clientes(
  id,
  tipo_persona            text check (tipo_persona in ('juridica','natural')),
  tipo_documento_value_id references catalog_values on delete restrict,
  documento               text,
  dv                      char(1) null,          -- solo NIT
  regimen_value_id        references catalog_values on delete restrict,
  nombre                  text,                  -- razón social o nombre completo
  tag                     char(3),
  ciudad                  text,
  departamento            text,
  direccion               text null,
  notas                   text null,
  is_active               boolean default true,
  timestamps
)

unique (lower(documento));
unique (upper(tag));
check  (tag ~ '^[A-Z0-9]{3}$');

cliente_contactos(
  id,
  cliente_id   references clientes on delete cascade,
  nombre, cargo null, email, telefono null,
  is_principal boolean default false,
  position int,
  timestamps
)

unique (cliente_id) where is_principal;
unique (cliente_id, lower(email));
```

Los contactos van en **cascade** y no en restrict porque son parte del cliente, no historial que
otros consulten: una cotización congela los datos del contacto al emitirse, no lo referencia.

**"Mínimo uno, exactamente un principal"** no se puede expresar con un índice. Se hace con un
*constraint trigger* diferido que se evalúa al cerrar la transacción, el mismo mecanismo que el
estándar ya prescribe para `sum(fases.pct) = 100`. Así la regla sobrevive a cualquier camino de
escritura, no solo al formulario.

Índices de búsqueda: `pg_trgm` sobre `nombre` y `documento`, con `unaccent`, que es lo que el
estándar asume y lo que la migración de extensiones ya dejó disponible.

## Lo que el prototipo define

Extraído de `Prototipo Admin.dc.html` y guardado en memoria. Copy literal, no parafraseado.

**Listado** (líneas 1166-1235) — H1 `Clientes`, subtítulo `6 empresas · 5 activas`. Botón
`+ Nuevo cliente`. Chips `Todos` / `Activos` / `Inactivos`. Buscador
`Buscar por empresa, NIT o contacto`. Columnas: `Empresa` (cuadro de 28px con iniciales) · `NIT`
(mono) · `Contacto` (dos líneas, nombre y correo) · `Estado` (píldora verde `Activo` o gris
`Inactivo`). Fila completa navegable. Paginación `1–5 de 6 clientes`, etiqueta `Filas`, 5/10/25.
Vacío: `Sin resultados` / `Ningún cliente coincide con la búsqueda.`

**Formulario** (1798-1937) — página completa, dos columnas. Volver `← Clientes`. Título
`Nuevo cliente` / `Editar cliente`, subtítulo
`Los datos fiscales se usan tal cual en cuentas de cobro y facturas.`

Grupo `Datos del cliente`: `Tipo de persona` (segmentado `Jurídica`/`Natural`, que al cambiar
ajusta tipo de documento y régimen) · `Tipo de documento` · `N.º documento` (mono, marcador
`900.000.000-0` o `1.000.000.000`) · `Régimen` · `Razón social` o `Nombre completo` (marcador
`Ej. Transportes Aurora SAS`) · `Tag` (3 caracteres, prefijo `#`, insignia `Auto`/`Editado`, botón
`↺` con título `Volver a generar desde el nombre`, ayuda `FAC-ABC-0001 · COT-ABC-0001`) ·
`Ciudad` · `Estado` (segmentado) · `Dirección de facturación` · `Notas internas`
(`Solo visible para el equipo`).

Grupo `Contactos`: `Mínimo uno. El principal recibe cotizaciones y cuentas de cobro.` Tarjetas
`Contacto 1`…, píldora `Principal` / `Marcar principal`, quitar con título `Quitar contacto` o
`Se requiere al menos un contacto`. Campos `Nombre`, `Cargo`, `Email`, `Teléfono`. Pie
`+ Agregar contacto`.

Resumen lateral con `Campos obligatorios` y la lista `Razón social`, `Número de documento`,
`Tag de 3 letras`, `Contacto principal con email`. Botones `Guardar cliente` / `Guardar cambios` y
`Cancelar`.

**Eliminar** (1585-1609) — desde el menú del detalle, nunca desde el listado. Sin historial:
`¿Eliminar {empresa}?` / `Se eliminarán su ficha, contactos y notas. Esta acción no se puede
deshacer.` Con historial: `{empresa} no se puede eliminar` /
`Tiene historial asociado (…). Para conservar la trazabilidad contable, archívalo como inactivo:
saldrá de los listados activos y su historial queda intacto.` con `Archivar como inactivo`.

**Generador de tag** (3707-3715) — 3 caracteres A-Z0-9. Quita tildes, descarta
`SAS, S.A.S, SA, S.A., LTDA, DE, DEL, LA, LAS, LOS, EL, Y, E, &`; con 3+ palabras toma la inicial
de cada una, con 2 las dos primeras letras de la primera más la inicial de la segunda, con 1 las
tres primeras letras.

## Hechos verificados del repo

| Qué | Estado |
|---|---|
| `catalog_lists` / `catalog_values` | sembradas; `tipos-documento` (campos `code`, `desc`) y `regimenes` (campo `code`) listas |
| `app/Actions/Catalogo/` | 5 Actions de referencia, incluido el patrón de borrado con traducción del error de FK |
| `app/Support/Catalogo/` | `Catalogo`, `CatalogPayload`, `UsageRegistry`, excepciones |
| `UsageRegistry::references()` | **vacío** — Clientes será su primer consumidor |
| `ui/table.tsx` | existe |
| `ui/pagination.tsx` | **no existe** — se construye aquí |
| `resources/js/features/` | existe, con `maestros/` como referencia de estructura |
| Nav | `SidebarNavigation.php:49` tiene `clientes` deshabilitado; `navigation.ts:34` ya trae icono y hue `#007aff` |
| Rama | `main`, limpia, sincronizada con `origin/main` |

Convenciones a seguir, todas verificadas en el código: modelo `Cliente` en español con PHPDoc
`@property` y `#[Fillable]`; controller con métodos nombrados, siempre Form Request,
`Inertia::flash` + `to_route`; migración con Schema builder y `DB::statement` para checks e índices
parciales; `<Form {...Controller.method.form()}>` con render prop, nunca `useForm`; tests como
clases PHPUnit con `test_snake_case()`, **no Pest funcional**; policies con `#[Authorize]`.

## Tareas

### Task 1 — Guardar la documentación del spec

`agent-os/specs/2026-10-12-1741-clientes/` con `plan.md`, `shape.md`, `standards.md` (copia
verbatim, cerrando con `## No aplican`), `references.md` y `visuals/README.md` con los rangos de
línea del prototipo.

Aplican: `backend/authorization`, `backend/catalog-master-data`, `database/postgres`,
`javascript/react-inertia`, `global/conventions`, `testing/testing`, y `backend/document-numbering`
por el `tag`. No aplican: `money`, `taxes-colombia` (el cliente aporta la identidad fiscal, pero
ningún impuesto se calcula aquí), `settings-parameters`.

### Task 2 — Esquema

Migración de `clientes` y `cliente_contactos` con los invariantes de arriba, incluido el constraint
trigger diferido de "exactamente un contacto principal" y los índices `pg_trgm` para la búsqueda.

### Task 3 — Lógica pura, sin base de datos

En `app/Support/Clientes/`, que es donde el estándar manda lo que debe ser testeable sin base:

- `TagGenerator` — el algoritmo del prototipo, más desambiguación ante colisión
- `NitVerificationDigit` — módulo 11 con los pesos de la DIAN, calcular y verificar
- `ClienteUsage` — mismo patrón que `UsageRegistry`: vacío hasta que existan Proyectos y Facturas

### Task 4 — Modelo

`app/Enums/TipoPersona.php`, `app/Models/Cliente.php`, `app/Models/ClienteContacto.php`, factories
con estados útiles (`juridica()`, `natural()`, `inactivo()`, `conContactos()`).

Declarar en `UsageRegistry::references()` las dos columnas que apuntan al catálogo, para que un
tipo de documento o un régimen en uso deje de ser borrable. Es la prueba de que el registro sirve.

### Task 5 — `ui/pagination` y el listado

El primitivo que quedó pendiente, con los tokens de Orbita. Listado con búsqueda, filtro por
estado, orden y paginación **resueltos en el servidor**, con recargas parciales de Inertia.

### Task 6 — Formulario

Página completa con los dos grupos y el resumen lateral, incluido el repetidor de contactos con la
regla del principal y el campo `Tag` con su insignia `Auto`/`Editado` y su botón de regenerar.

### Task 7 — Detalle mínimo

Portada con iniciales y chip del tag, ficha fiscal con botones de copiar, contactos con el distintivo
`Principal`, y notas. El menú con `Archivar` y `Eliminar`, y las dos confirmaciones del prototipo.

### Task 8 — Escrituras, autorización y rutas

`app/Actions/Clientes/`: `SaveCliente` (cliente y contactos en una transacción), `ArchiveCliente`,
`DeleteCliente` (traduce el error de FK, igual que `DeleteCatalogValue`). Form Requests con la
validación del DV y la del contacto principal. `ClientePolicy` solo administrativos. Rutas, y
encender `clientes` en el nav.

### Task 9 — Tests

Lo que importa, en este orden:

- **Unit sin base**: el generador de tag contra los casos del prototipo, y el DV contra NIT reales
- Los invariantes en Postgres: documento único sin distinguir mayúsculas, tag único, formato del
  tag, y el trigger que rechaza un cliente sin contacto principal o con dos
- Que eliminar un cliente con referencias sea rechazado y ofrezca archivar
- Matriz ruta × rol: un miembro no alcanza ninguna ruta de clientes
- Que un valor del catálogo en uso por un cliente no se pueda borrar — la integración con Maestros
- Búsqueda server-side: que encuentre por nombre, documento y contacto, con y sin tildes

No se testea: el render del formulario, los colores, ni snapshots de props.

## Archivos principales

| Archivo | Qué pasa |
|---|---|
| `database/migrations/*_create_clientes_tables.php` | nuevo, invariantes y trigger en SQL |
| `app/Enums/TipoPersona.php` | nuevo |
| `app/Models/{Cliente,ClienteContacto}.php` | nuevos |
| `app/Support/Clientes/{TagGenerator,NitVerificationDigit,ClienteUsage}.php` | nuevos |
| `app/Support/Catalogo/UsageRegistry.php` | declarar las dos referencias de Clientes |
| `app/Actions/Clientes/` | nuevos |
| `app/Http/Controllers/ClientesController.php` | nuevo |
| `app/Http/Requests/Clientes/` | nuevos |
| `app/Policies/ClientePolicy.php` | nuevo |
| `resources/js/components/ui/pagination.tsx` | nuevo |
| `resources/js/pages/clientes/{index,form,show}.tsx` | nuevos |
| `resources/js/features/clientes/` | nuevos |
| `app/Support/Navigation/SidebarNavigation.php` | encender `clientes` |
| `routes/web.php` | grupo de clientes |

## Verificación

1. `make fresh` siembra el catálogo y permite crear un cliente completo.
2. Un cliente sin contacto principal es rechazado por la base, no solo por PHP.
3. Un NIT con dígito de verificación incorrecto no pasa la validación.
4. Dos empresas con nombre parecido obtienen tags distintos.
5. Un tipo de documento en uso por un cliente ya no se puede borrar desde Maestros.
6. `make check` pasa todas las compuertas.
7. Verificación en navegador real en `http://localhost:8100`: alta con dos contactos, búsqueda sin
   tildes, paginación, archivado y las dos confirmaciones de borrado.

## Riesgo

`Risk: item 1 (data) + item 3 (contracts)` — crea la primera tabla de negocio, de la que colgarán
cotizaciones, proyectos, facturas y documentos, y fija el `tag` que alimentará la numeración fiscal.
Por eso la identidad va por `id` y nunca por nombre, el DV se valida, el tag resuelve colisiones y
el contacto principal se garantiza en la base.
