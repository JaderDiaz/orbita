# Orbita — Maestros y Listas

## Context

Pediste el **módulo de Clientes** (listar, crear, editar, eliminar) y que consultara el diseño para
actualizar la información del módulo.

La exploración encontró un bloqueo antes de poder planear Clientes: el cliente necesita listas
(régimen tributario, tipo de documento, estado) y tanto la misión como los estándares exigen que esas
listas sean **filas editables con llave foránea**, no enums de PHP:

- `agent-os/product/mission.md:38` — *"Enumerations are data, not code."*
- `agent-os/standards/backend/catalog-master-data.md` — las tablas transaccionales referencian
  `catalog_values` con `on delete restrict`, y **nunca** se compara por `name`.
- `agent-os/product/roadmap.md:10-11` — *"Everything else holds foreign keys to catalog values and
  reads parameters. Retrofitting these is the most expensive mistake available."*

Esas tablas no existen. Ante el fork, elegiste **"Maestros y Listas completo primero"**: cerrar
Phase 0 antes de abrir Phase 1.

Este spec construye Maestros y Listas. **Clientes es el spec siguiente**, con su investigación ya
extraída del prototipo y guardada en memoria, así que no se repite.

Resultado buscado: un administrador gestiona las listas del negocio sin un deploy, y Clientes nace
con las llaves foráneas correctas desde el primer día.

El pedido original del usuario para este módulo, verbatim del transcript del diseño:

> "Del mismo modo optimina la vista de "Maestros y Listas" agrupandolos y optmizandolos para que
> podamos tener control de las mismas, las listas pueden tener metadatos relacionados segun donde se
> use pueden tener parametros adicionales por ejempli las etiquetas pueden tener color primario y
> secunfario para hacer bages, y otros pueden tener iconos para los timpos de tareas por ejempli, o
> los tipos de documento pueden tener codigos para usar en vistas mas cortas, evalua las listas que
> usamos a lo largo del desarrollo del panel de adminsitrador y optimiza esta vista a criterio,
> sobra, funcional, y con el CRUD includo por listas."

## Alcance

**Entra**

- Extensiones `citext`, `pg_trgm`, `unaccent` por migración
- `catalog_lists` y `catalog_values` con los invariantes del estándar en Postgres
- Semilla completa: **16 listas, 7 grupos, 73 valores** extraídos del prototipo
- `system_key` + resolver cacheado + enums de PHP para el comportamiento que el código posee
- `flags` jsonb para la semántica extensible por el usuario
- `UsageRegistry` — un `UNION ALL` cacheado, sin triggers ni columnas contador
- Pantallas: maestro/detalle, editor en línea, desactivación y borrado bloqueado
- Primitivos faltantes: `ui/table`, `ui/pagination`, y los 8 pares de badge semánticos
- Sub-nav: abrir Configuración cuando la URL es de un hijo, y marcar el sub-ítem activo
- `CatalogListPolicy` / `CatalogValuePolicy`, rutas, y encender `maestros`
- Test de arquitectura que prohíbe comparar `catalog_values.name` con un literal

**No entra**

- **Clientes** — spec siguiente, investigación ya capturada
- Resto de Phase 0: Parámetros, audit trail, `Money`, esqueleto de ⌘K
- Cablear las listas a los módulos que aún no existen. `Cobra IVA` queda declarado pero sin consumir
  hasta Facturación; el estándar exige que un parámetro o flag que nadie lee sea una mentira en la
  UI, así que esto se registra como deuda explícita, no como hecho.

## Decisiones tomadas

| Decisión | Razón |
|---|---|
| **Maestros completo antes de Clientes** | Tu elección. Es el orden del roadmap: retrofitear llaves foráneas a `catalog_values` es el error más caro disponible. |
| Extensiones por **migración**, no por el script de init | Verificado: `docker/postgres/init/01-init.sql` crea `pg_trgm` y `unaccent`, pero corre **solo** al inicializar un volumen vacío. Una base de CI o producción no creada por ese script no las tendría. |
| **`citext` se agrega** | Verificado contra la base real: solo hay `pg_trgm`, `plpgsql` y `unaccent`. `citext` está disponible en la imagen (1.8) pero **no instalada**, y el estándar lo usa en `key citext unique` y `system_key citext`. Hoy cualquier columna `citext` fallaría. |
| **Se agrega lo que el prototipo no tiene**: `system_key`, `is_system`, `position`, `allows_user_values`, `fields`, `used_in` | El prototipo **no los modela** y une todo por el label string: `msAct()` devuelve solo etiquetas, y `cfVacio()` guarda `tipoDoc: "NIT"` como texto. Renombrar un valor huerfaniza todos los registros existentes. Además hay valores que el código necesita (`Aceptada`, `Hecho`, `Pagada`, `Precio cerrado`) y el prototipo los deja borrables. El estándar ya exige estas columnas. |
| **El color se guarda como clave de paleta**, no como hex libre | El prototipo ofrece dos `input type="color"` libres (`Fondo` / `Texto`), con lo que blanco sobre blanco es alcanzable. La semilla solo usa las 8 entradas de `msPal`, y el estándar tiene una sola columna `color`. Guardar la clave da el par determinista y elimina el problema de contraste. |
| **El conteo de uso es un `COUNT` real** | En el prototipo `uso` es dato semilla que nunca se actualiza: un valor creado hoy y usado en 50 registros sigue diciendo `Sin uso` y es borrable. El guard de borrado tiene que ser una referencia real, reforzada por la FK `on delete restrict`. |
| **Reordenar por columna `position`** | El prototipo intercambia vecinos del arreglo completo mientras el usuario ve una vista filtrada, así que con filtro activo la flecha `▲` intercambia con un vecino oculto y nada se mueve visualmente. Con `position` explícito se reordena dentro del conjunto visible. |
| Editor y confirmación de borrado **en línea**, no modales | Es lo que hace el prototipo, y mantiene el contexto de la fila a la vista. |
| Borrado **bloqueado**, desactivación siempre permitida | Estándar y prototipo coinciden. La FK `on delete restrict` lo garantiza en la base. Nadie necesita borrar "Pausado"; necesita que deje de aparecer en los selectores. |

## El catálogo semilla

Fuente: `msSeed()`, líneas 3395-3418 de `Prototipo Admin.dc.html`. 16 listas, 7 grupos, 73 valores.
Todos los valores de la semilla van `is_active: true`. `★` marca el `is_default`.

**Paleta** (8 pares background/foreground, idénticos a los del spec de login):
`gris #f0f0f2/#48484a` · `azul #e8f1ff/#0a64d6` · `verde #e8f7ee/#1a7f37` · `naranja #fff3e0/#b25000` ·
`rojo #ffeceb/#d70015` · `morado #f5edfd/#8e3fcf` · `turquesa #e3f6f9/#0f7c8c` · `indigo #eef0ff/#4b44c9`

**Iconos** (16 claves, en orden del selector): `sparkle, bug, up, down, minus, flag, file, pen,
check, lock, box, card, bank, cash, tag, clock`

### Clientes y fiscal

**Tipos de documento** — `Identificación de clientes y contactos. El código se usa en vistas compactas.`
· campos `code, desc` · sin flag
`NIT` NIT ★ *Personas jurídicas* · `Cédula de ciudadanía` CC · `Cédula de extranjería` CE ·
`Pasaporte` PA · `PPT` PPT *Permiso por protección temporal*

**Regímenes tributarios** — `Define el tratamiento de IVA y retenciones del cliente.`
· campos `code` · flag **`Cobra IVA`**
`Responsable de IVA` R-48 ★ flag✓ · `No responsable de IVA` R-49 · `Régimen simple de tributación` RST flag✓ ·
`Gran contribuyente` GC flag✓

### Comercial

**Estados de cotización** — `Ciclo de una cotización desde el borrador hasta la decisión del cliente.`
· campos `color` · flag **`Estado final`**
`Borrador` gris ★ · `Enviada` azul · `Aprobada` verde flag✓ · `Rechazada` rojo flag✓ · `Vencida` naranja flag✓

### Proyectos

**Modalidades de contrato** — `Cómo se cobra el proyecto. Determina el cálculo de presupuesto y margen.`
· campos `code, desc` · sin flag
`Precio cerrado` PC ★ *Valor fijo por alcance* · `Por hora` PH *Se factura por horas registradas* ·
`Retainer mensual` RM *Cuota fija mensual*

**Estados de proyecto** — `Etapas generales del proyecto en la lista y el panel.`
· campos `color` · flag **`Cuenta como activo`**
`En negociación` indigo · `En desarrollo` azul ★ flag✓ · `En pruebas` morado flag✓ · `Pausado` naranja ·
`Entregado` verde

**Estados de fase** — `Avance de cada fase y su aceptación por el cliente.`
· campos `color` · flag **`Estado final`**
`Planeada` gris ★ · `En curso` azul · `En aceptación` morado · `Aceptada` verde flag✓

**Disciplinas** — `Áreas de trabajo dentro de cada fase.` · campos `code, color` · sin flag ·
**sin predeterminado**
`Descubrimiento` DSC indigo · `UI/UX` UX morado · `Backend` BE azul · `Frontend` FE turquesa ·
`QA y entrega` QA verde · `Soporte` SOP gris

### Tareas

**Tipos de tarea** — `Clasificación de las tarjetas del tablero.` · campos `code, color, icon` · sin flag
`Feature` FEAT azul sparkle ★ · `Bug` BUG rojo bug · `Mejora` MEJ morado up

**Prioridades** — `Urgencia de las tareas.` · campos `color, icon` · sin flag
`Alta` rojo up · `Media` naranja minus ★ · `Baja` gris down

**Columnas del tablero** — `Estados de una tarea en el tablero kanban.` · campos `color` ·
flag **`Cierra la tarea`**
`Por hacer` gris ★ · `En progreso` azul · `Revisión` morado · `Hecho` verde flag✓

### Finanzas

**Estados de factura** — `Situación de cobro de cada factura.` · campos `color` · flag **`Saldo en cero`**
`Pendiente` azul ★ · `Parcial` naranja · `Pagada` verde flag✓ · `Vencida` rojo

**Métodos de pago** — `Cómo se recibe un pago dentro de una factura.` · campos `code, icon` · sin flag
`Transferencia` TRF bank ★ · `Consignación` CON cash · `Efectivo` EFE cash · `PSE` PSE card

**Categorías de egreso** — `Agrupan los gastos en el resumen de egresos.` · campos `code, color, desc` · sin flag
`Infraestructura` INF azul ★ *Servidores, dominios y nube* · `Licencias` LIC morado *Software y suscripciones* ·
`Contratistas` CTR naranja *Servicios profesionales externos* · `Operación` OPE gris *Oficina y gastos generales*

### Documentos

**Categorías de documento** — `Organizan los archivos de clientes y proyectos.` · campos `code, icon` · sin flag
`Contrato` CTO pen ★ · `Requerimientos` REQ file · `Acta` ACT check · `Manual` MAN file ·
`Técnico` TEC box · `Soporte` SOP card

**Etiquetas de documento** — `Marcas libres para filtrar documentos.` · campos `color` · sin flag
`Firmado` verde · `Vigente` azul · `Confidencial` rojo · `Cliente` turquesa · `Interno` gris ★ · `Borrador` naranja

### Equipo

**Roles del equipo** — `Cargo de cada miembro en Equipo y costos.` · campos `code, desc` · sin flag
`Frontend` FE ★ · `Backend` BE · `Full stack` FS · `UI/UX` UX · `QA` QA · `Project manager` PM ·
`Administrador` ADM *Dirección y operación*

> Estas son **cargos**, no roles del sistema. `backend/authorization` lo marca como el error clásico
> de conflación: los roles del sistema son el enum PHP `owner|admin|member|client` y no se tocan aquí.

**`system_key` que el código va a poseer** (el prototipo no los tiene y por eso deja borrables valores
que sostienen lógica): `estados_cotizacion.aprobada`, `estados_fase.aceptada`,
`columnas_tablero.hecho`, `estados_factura.pagada`, `regimenes.responsable_iva`,
`modalidades.precio_cerrado`. Los valores con `system_key` llevan `is_system: true`: se pueden
renombrar y recolorear, pero no borrar ni desactivar.

## Hechos verificados del repo

Todo comprobado directamente, no inferido.

| Área | Convención real | Evidencia |
|---|---|---|
| Modelo | Español en el dominio, PHPDoc `@property` completo, `#[Fillable]`/`#[Hidden]` de Laravel 13, `casts()` como método | `app/Models/User.php:16-50` |
| Enum | `enum X: string`, `match` exhaustivo **sin** `default`, `values()` reutilizado por validación y por el check constraint | `app/Enums/SystemRole.php` |
| Controller | Clase con métodos nombrados, **siempre** Form Request, `Inertia::flash('toast', …)` + `to_route()`, `assert($x instanceof Y)` para Larastan 8 | `app/Http/Controllers/Settings/ProfileController.php` |
| Form Request | Solo `rules()`; reglas compartidas en traits bajo `app/Concerns/` | `app/Http/Requests/Settings/ProfileUpdateRequest.php` |
| Rutas | Escritas a mano, nombres en punto, `Route::middleware(['auth'])->group()`. **`Route::resource` no se usa en el proyecto** | `routes/web.php`, `routes/settings.php` |
| Migración | Schema builder para columnas e índices; `DB::statement()` con nombres explícitos para checks e índices parciales; `down()` con `IF EXISTS` en orden inverso | `database/migrations/2026_10_12_160000_add_system_role_to_users_table.php` |
| Policy | Una por modelo, autodescubierta. El owner pasa por `Gate::before` salvo las habilidades protegidas | `app/Policies/UserPolicy.php`, `app/Providers/AppServiceProvider.php:38-47` |
| Formulario | `<Form {...Controller.method.form()}>` con render prop. **Nunca `useForm`.** Errores con `<InputError message={errors.x} />` | `resources/js/pages/settings/profile.tsx:32-90` |
| Página | `setLayoutProps({ breadcrumbs })` + `<Head title>`; el layout se resuelve centralmente, así que `pages/maestros/*` hereda `AppLayout` sin cablear nada | `resources/js/pages/dashboard.tsx`, `resources/js/app.tsx:15-26` |
| Tests | **Clases PHPUnit con `test_snake_case()`, no Pest funcional.** `use RefreshDatabase`, assertions sobre el payload de Inertia, mensajes de fallo en español | `tests/Feature/NavigationTest.php` |

**Faltantes confirmados:** `ui/table.tsx`, `ui/pagination.tsx`, la extensión `citext`, los pares de
badge semánticos (`ui/badge.tsx` sigue siendo shadcn de fábrica con clases `dark:` inertes), y
`app/Actions/` existe **vacío** — este spec crea el primer Action del proyecto.

`table` y `pagination` de shadcn son markup plano: **cero dependencias nuevas**. `tabs` requeriría
`@radix-ui/react-tabs`, que no está instalado — el diseño de Maestros no lo necesita.

**Dos cosas del nav que hay que corregir** (`app/Support/Navigation/SidebarNavigation.php:72-77`,
`resources/js/components/nav-main.tsx:73-113`): `maestros` es hijo de `Configuración`, y `NestedItem`
arranca con `useState(false)` sin auto-abrirse cuando la URL actual corresponde a un hijo, además de
no pasar `isActive` al sub-botón. Navegar a `/maestros` dejaría el grupo cerrado y sin indicación.

## Tokens disponibles

Verificados en `resources/css/app.css`: `--brand*` (7), `--surface-subtle` `#f9f9fb` (hover de fila y
footer de tabla), `--surface-muted`, `--surface-track`, `--text-secondary`, `--text-tertiary`
`#8e8e93` (encabezados de tabla), `--radius-card` 16px, `--radius-pill` 999px, `--shadow-card`, las
utilidades `shell-glow` y `focus-ring-brand`, y la clase `.tabular`.

Del prototipo: encabezado de tabla `11/600/.04em uppercase`, divisor `#f2f2f7`, badge
`Predeterminado` en `#fff7ed`/`#c2410c` 10px/600 pill, fila inactiva a `opacity:.45`, flag como texto
`✓ {flag}` en `#1a7f37`, fila en edición `#fff7ed`, sub-ítem activo
`rgba(249,115,22,.1)`/`#c2410c`/600.

## Tareas

### Task 1 — Guardar la documentación del spec

`agent-os/specs/2026-10-12-<hhmm>-maestros-y-listas/` replicando el formato del spec anterior:
`plan.md`, `shape.md`, `standards.md` (copia verbatim de los estándares que aplican, cerrando con
`## No aplican`), `references.md` (bloques Ubicación / Relevancia / Qué reutilizar con `path:line`),
`visuals/README.md` con los rangos de línea del prototipo.

Aplican: `backend/catalog-master-data` (el central), `backend/authorization`, `database/postgres`,
`javascript/react-inertia`, `global/conventions`, `testing/testing`.
No aplican: `money`, `taxes-colombia`, `document-numbering`, `settings-parameters`.

### Task 2 — Extensiones y esquema

Migración de extensiones: `citext`, `pg_trgm`, `unaccent` con `IF NOT EXISTS`.

Migración del esquema del estándar, más `position int`. Invariantes en Postgres:

```sql
unique (list_id, lower(name));
unique (list_id, lower(code)) where code is not null;
unique (list_id, system_key) where system_key is not null;
unique (list_id) where is_default;
```

Más `catalog_values.list_id references catalog_lists on delete restrict`, y un check de `color`
contra las 8 claves de paleta.

### Task 3 — Modelos y soporte

`app/Models/CatalogList.php`, `app/Models/CatalogValue.php` con el cast de `flags`.

`app/Support/Catalogo/` — resolver cacheado por `system_key`, `is(...)` y `flag(...)`, y los enums de
PHP de los `system_key` listados arriba. `match` sobre `system_key` exhaustivo con
`default => throw`, para que un valor agregado por el usuario nunca caiga en silencio por una rama
financiera. Esto vive en `app/Support` porque debe ser testeable sin base de datos.

### Task 4 — Semilla del catálogo

`database/seeders/CatalogSeeder.php` con las 16 listas y 73 valores de la sección anterior, con
`position` explícito, `system_key` donde corresponde, e idempotente con `updateOrCreate` —
`firstOrCreate` ya nos costó una instalación sin owner en el spec pasado.

### Task 5 — UsageRegistry

`app/Support/Catalogo/UsageRegistry.php` — un `UNION ALL` cacheado que cuenta usos por valor, con el
registro de relaciones declarado en código. Sin triggers y sin columnas contador.

Hoy ninguna tabla transaccional existe, así que el registro arranca vacío y todo valor reporta cero.
Eso es correcto y queda cubierto por un test: la intención es que Clientes solo tenga que registrar
su relación, no inventar el mecanismo.

### Task 6 — Primitivos de UI

`ui/table.tsx` y `ui/pagination.tsx` con los tokens de Orbita. Los 8 pares de badge como variantes
reales de `ui/badge.tsx`, quitando sus clases `dark:` inertes.

Corregir el sub-nav: abrir `Configuración` cuando la URL actual corresponda a un hijo
(`isCurrentOrParentUrl` ya existe en `resources/js/hooks/use-current-url.ts`) y marcar el sub-ítem
activo con el estilo del prototipo.

### Task 7 — Pantallas

`resources/js/pages/maestros/index.tsx` — maestro/detalle en dos columnas, no una tabla de listas:

- Encabezado `Maestros y listas` con el resumen
  `16 listas · 73 valores. Cambiarlos afecta registros nuevos; los existentes conservan su valor.`
- Riel izquierdo sticky, 220-270px: buscador con placeholder `Buscar lista o valor` que filtra por
  título y por etiquetas de valores, luego un bloque por grupo con el conteo de valores **activos**.
  Vacío: `Ninguna lista coincide.`
- Panel derecho: card de encabezado con el grupo en 11px uppercase, título, descripción, botón
  `Agregar valor`, y las filas meta `Usado en` y `Campos de cada valor`.
- Tabla de valores con encabezados literales `Valor` · `Vista` · `Activo`, barra con el conteo
  (`5 activos`), el enlace `Mostrar inactivos` / `Ocultar inactivos` y el buscador
  `Buscar en la lista`. Acciones por fila con sus títulos:
  `Marcar como predeterminado`, `Editar`, `Eliminar`. Vacío: `No hay valores que coincidan.`
  Footer siempre presente: `+ Agregar valor`.
- Editor **en línea** (no modal) con borde `#fed7aa`: título `Nuevo valor en {lista}` o
  `Editar "{label}"`, vista previa en vivo del badge, y solo los campos que la lista declara —
  `Nombre`, `Código corto · hasta 5`, `Descripción · opcional`, `Color del badge`, `Icono`, el toggle
  de la flag, y `Predeterminado` con la ayuda `Se preselecciona en formularios`. Botones `Cancelar` y
  `Guardar valor`.
- Confirmación de borrado **en línea dentro de la fila**:
  - sin uso: `¿Eliminar "{label}"? Esta acción no se puede deshacer.` con `Cancelar` / `Eliminar`
  - en uso: `"{label}" está en uso en {n} registros. Desactívalo para que no aparezca en registros
    nuevos.` con `Cancelar` / `Desactivar`, **sin renderizar `Eliminar`**

Corregir dos defectos de copy del prototipo: la pluralización de `1 inactivos`, y el desajuste
`Color de badge` (chip) vs `Color del badge` (label) — se usa `Color del badge` en ambos.

El filtrado y la paginación se resuelven **en el servidor** (`database/postgres` lo exige), con
recargas parciales de Inertia.

### Task 8 — Escrituras, autorización y rutas

`app/Actions/Catalogo/` — los primeros Actions del proyecto: guardar un valor, marcar predeterminado
(exclusivo, en transacción), desactivar, reordenar, eliminar. Form Requests en
`app/Http/Requests/Maestros/`, con la unicidad de nombre y de código por lista.

Mensaje de validación del prototipo, verbatim: `Ya existe un valor con ese nombre en la lista.`

`CatalogListPolicy` / `CatalogValuePolicy`: solo administrativos; `is_system` niega borrar y
desactivar. Rutas bajo `['auth']`. Encender `maestros` en `SidebarNavigation.php:76`.

### Task 9 — Tests

Según `testing/testing`, lo que importa aquí **no** es el CRUD:

- Los cuatro índices únicos y el check de color, afirmados contra Postgres real
- Que la FK `on delete restrict` impida borrar un valor en uso
- Que marcar predeterminado sea exclusivo, y que desactivar limpie el predeterminado
- Que un valor `is_system` no se pueda borrar ni desactivar
- Test de arquitectura: ningún literal comparado contra `catalog_values.name`
- Que `match` sobre `system_key` lance ante un valor desconocido
- Unit sin base de datos para el resolver y para `UsageRegistry`
- Matriz ruta × rol: un miembro no alcanza ninguna ruta de maestros

El estándar pone *"Catalog and settings CRUD beyond one representative list"* entre lo que **no** se
testea. Una lista representativa, no las dieciséis.

## Archivos principales

| Archivo | Qué pasa |
|---|---|
| `database/migrations/*_create_postgres_extensions.php` | nuevo |
| `database/migrations/*_create_catalog_tables.php` | nuevo, invariantes en SQL |
| `app/Models/CatalogList.php`, `CatalogValue.php` | nuevos |
| `app/Support/Catalogo/` | resolver, flags, `UsageRegistry`, enums de `system_key` |
| `app/Actions/Catalogo/` | nuevos, primeros Actions del proyecto |
| `app/Http/Controllers/MaestrosController.php` | nuevo |
| `app/Http/Requests/Maestros/` | nuevos |
| `app/Policies/CatalogListPolicy.php`, `CatalogValuePolicy.php` | nuevos |
| `database/seeders/CatalogSeeder.php` | nuevo, 16 listas / 73 valores |
| `resources/js/pages/maestros/index.tsx` | nuevo |
| `resources/js/components/ui/{table,pagination}.tsx` | nuevos |
| `resources/js/components/ui/badge.tsx` | 8 pares semánticos, sin `dark:` |
| `resources/js/components/nav-main.tsx` | abrir el grupo y marcar el sub-ítem activo |
| `app/Support/Navigation/SidebarNavigation.php` | encender `maestros` |
| `routes/web.php` | grupo de maestros |

## Verificación

1. `make fresh` siembra 16 listas y 73 valores; `make psql` confirma los cuatro índices únicos.
2. Un valor en uso no se puede borrar — lo rechaza Postgres, no PHP.
3. Marcar predeterminado deja exactamente uno activo por lista.
4. `make test` pasa, incluyendo la matriz ruta × rol y el test de arquitectura.
5. `make stan` nivel 8 limpio; `make pint-test` pasa.
6. `pnpm run build` pasa — única comprobación autoritativa de las rutas de Wayfinder.
7. Verificación en navegador real en `http://localhost:8100` (el stack está corriendo y saludable):
   Configuración se abre con Maestros activo, el riel y la tabla se ven como el prototipo, el editor
   en línea previsualiza el badge, y el borrado bloqueado muestra su copy con `Desactivar`.

## Riesgo

`Risk: item 1 (data) + item 3 (contracts)` — crea el esquema del que **todo** el resto del producto
va a depender por llave foránea, y define los `system_key` que el código va a comparar para siempre.
Por eso los invariantes van en Postgres y no solo en PHP, por eso `is_system` protege los valores que
sostienen lógica, y por eso el orden elegido fue cerrar Maestros antes de abrir Clientes.
