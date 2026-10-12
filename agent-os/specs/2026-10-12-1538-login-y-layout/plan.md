# Orbita — Login y Layout

## Context

El scaffolding ya está terminado y verificado: Laravel 13.35 + Inertia 3 + React 19 corriendo en
Docker (`app`, `postgres`, `vite`), autenticación recortada a login + 2FA, Pint y PHPStan nivel 8
limpios, 25 tests pasando. Lo que existe hoy es el starter kit con su identidad visual de shadcn y
un nav de un solo ítem ("Dashboard").

Este spec convierte ese cascarón en el shell real de Orbita: la pantalla de login y el layout
interno con navegación por rol, sobre el sistema de diseño del prototipo.

Se hace ahora, antes de cualquier módulo de negocio, por dos razones concretas:

1. **La frontera de permisos no conviene retrofitearla.** El rol decide qué ve cada usuario en el
   nav, y más adelante decidirá qué datos recibe. Introducir `system_role` después implica revisar
   cada pantalla ya construida.
2. **Los tokens de diseño se propagan.** Cada pantalla nueva nace con los colores y radios que
   encuentre. Aplicarlos con dos pantallas es barato; con veinte es un barrido completo.

Resultado buscado: un usuario entra por `/login`, cae en su panel, y el nav que ve corresponde a su
rol. Visualmente idéntico al prototipo. Sin módulos de negocio todavía.

## Alcance

**Entra**

- Roles del sistema: enum PHP `owner|admin|member|client`, columna en `users`, invariante del owner
- Nav por rol: admin ve 4 grupos; member ve 4 ítems
- Sistema de diseño del prototipo aplicado a login y layout
- Login con la identidad de Orbita
- Eliminar modo oscuro

**No entra** (decidido explícitamente)

- Paleta de comandos ⌘K — sin entidades que indexar, solo listaría secciones del nav
- Cualquier módulo de negocio
- Portal del cliente (el rol `client` existe en el enum, sin vistas)

## Decisiones tomadas

| Pregunta | Decisión |
|---|---|
| Roles en el nav | Roles del sistema **ahora**, con nav por rol |
| Sistema de diseño | Aplicar **en este spec**, derivando los valores del uso real en el prototipo |
| Paleta ⌘K | Fuera de alcance |
| Modo oscuro | Eliminarlo, solo tema claro |

Nota del usuario sobre el diseño: *"adapta el uso de los colores y estilos a los aplicados en el
diseño"*. Por eso los tokens de abajo salen de extraer el prototipo, no de una propuesta mía. Dos
cosas que yo había asumido mal y quedan corregidas: las cards son de **16px** (los 18px son solo el
hero oscuro y la paleta ⌘K), y la fuente primaria es **SF Pro con Geist como fallback**, no Geist
primero.

## Tokens extraídos del prototipo

Fuente: `Prototipo Admin.dc.html`. Valores literales del markup, no normalizados.

**Superficies** — app `#f5f5f7` · sidebar `rgba(250,250,252,.85)` con `backdrop-filter:blur(24px)
saturate(180%)` · header `rgba(245,245,247,.72)` con `blur(20px)` · card `#fff` · sutil `#f9f9fb`
(hover de fila, footer de tabla) · muted `#f2f2f7` (track de progreso) · track `#ececf0`
(segmentado).

Glow del shell, verbatim:
```css
radial-gradient(900px 360px at 85% -120px, rgba(249,115,22,.09), transparent 70%),
radial-gradient(700px 300px at 10% -140px, rgba(99,102,241,.06), transparent 70%)
```

**Texto** — primario `#1d1d1f` · secundario `#3a3a3c` (labels) · muted `#6e6e73` (subtítulos) ·
terciario `#8e8e93` (placeholders, encabezados de tabla) · enlace `#c2410c`, hover `#9a3412`.

**Bordes** — card `rgba(0,0,0,.05)` · chrome del shell `rgba(0,0,0,.06)` · **inputs**
`rgba(0,0,0,.12)`, hover `rgba(0,0,0,.24)` · divisores de tabla `#f2f2f7`.

**Acento** — `#f97316`, hover `#ea580c`, texto `#c2410c`, oscuro `#9a3412`, claro `#fdba74`,
superficie `#fff7ed`, borde tintado `#fed7aa`. Se usa con disciplina: **una** acción primaria
sólida por pantalla, más estados y rings.

Anillo de foco único para todo control interactivo, verbatim:
```css
border-color: #f97316;
box-shadow: 0 0 0 4px rgba(249,115,22,.16);
```

**Radios** — botones y chips `999px` (pill) · **card `16px`** · hero oscuro y paleta `18px` ·
modal `14px` · **inputs, selects, textarea `10px`** · ítem de nav `9px` · badge `999px`.

**Elevación** — la sombra canónica, repetida en casi toda card:
```css
box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 8px 24px -6px rgba(0,0,0,.06);
```
Botón primario: `0 1px 2px rgba(249,115,22,.3), inset 0 1px 0 rgba(255,255,255,.2)`.

**Tipografía** — `-apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Geist', system-ui, sans-serif`;
mono `'Geist Mono', monospace`. Body `letter-spacing:-.005em`, antialiased. Escala usada: `h1` 28/700
`-.03em` · KPI 25/600 `-.025em` tabular · título de card 14/600 `-.01em` · body 13/400 `lh 1.6` ·
label 12.5/500 · encabezado de tabla 11/600 `.04em` uppercase · badge 10.5/600 · título de grupo del
sidebar 10/`.1em` uppercase `#8e8e93`. Columnas numéricas: Geist Mono + `tabular-nums`.

**Layout** — sidebar `244px`, colapsado `68px`, `transition:width .22s ease` · header `56px`,
padding `0 28px`, sticky · contenido `28px 32px 56px`, sin `max-width` · card padding `20px 22px`.

**Badges** — 8 pares background/foreground sin borde: gris `#f0f0f2`/`#48484a` · azul
`#e8f1ff`/`#0a64d6` · verde `#e8f7ee`/`#1a7f37` · naranja `#fff3e0`/`#b25000` · rojo
`#ffeceb`/`#d70015` · morado `#f5edfd`/`#8e3fcf` · turquesa `#e3f6f9`/`#0f7c8c` · índigo
`#eef0ff`/`#4b44c9`.

**Ítem de nav** — padding `5px 8px`, radio `9px`, 13.5px, gap 10px, con tile de icono de 24px
(radio `7px`, gradiente por sección). Default `transparent`/`#3a3a3c`/400 · hover
`rgba(0,0,0,.04)` · activo `#fff`/`#1d1d1f`/600 con `0 1px 2px rgba(0,0,0,.06), 0 0 0 1px
rgba(0,0,0,.04)`. Sub-ítem activo `rgba(249,115,22,.1)`/`#c2410c`/600.

Hues de los tiles por sección: panel `#5856d6` · clientes `#007aff` · cotizaciones `#30b0c7` ·
proyectos `#f97316` · documentos `#32ade6` · facturas `#34c759` · egresos `#ff3b30` · equipo
`#af52de` · tiempo `#ff9f0a` · configuración `#8e8e93`.

**Chip de filtro** — activo es **oscuro** (`#1d1d1f` fondo, `#fff` texto), no naranja. El naranja
(`#fff7ed`/`#c2410c`/borde `#f97316`) es para *selección*, no para filtro activo.

## Tareas

### Task 1 — Guardar la documentación del spec

Crear `agent-os/specs/2026-10-12-1538-login-y-layout/` con:

- `plan.md` — este plan
- `shape.md` — alcance, las cuatro decisiones y su razón, contexto del prototipo
- `standards.md` — contenido completo de los estándares que aplican
- `references.md` — el mapa del starter kit (rutas y líneas) y los tokens extraídos
- `visuals/` — referencia al proyecto de Claude Design `4ba0fa0d-992f-4439-a02a-311350030ea9`

Estándares que aplican, de `agent-os/standards/index.yml`: `backend/authorization` (el central),
`javascript/react-inertia`, `global/conventions`, `database/postgres`, `testing/testing`.
No aplican: `money`, `taxes-colombia`, `document-numbering`, `catalog-master-data`,
`settings-parameters`.

### Task 2 — Roles del sistema

`app/Enums/SystemRole.php` — enum respaldado por string: `Owner`, `Admin`, `Member`, `Client`.
Con `label()` en español para la UI y `isStaff()` para distinguir interno de cliente.

Migración nueva sobre `users`:

```php
$table->string('system_role')->default(SystemRole::Member->value);
$table->boolean('is_owner')->default(false);
```

Invariantes **en la base**, no solo en PHP (estándar `database/postgres`):

- índice único parcial: un solo owner — `create unique index ... on users (is_owner) where is_owner`
- check: `(not is_owner) or system_role = 'owner'`
- check: `system_role in ('owner','admin','member','client')`

`app/Models/User.php` — cast del enum, y helpers `isOwner()`, `isAdmin()`, `isMember()`.

`app/Providers/AppServiceProvider.php` — `Gate::before` que concede todo al owner.

`app/Policies/UserPolicy.php` — niega bloquear, degradar y eliminar al owner.

`database/seeders/DatabaseSeeder.php` — siembra el owner con las credenciales del prototipo
(`owner@orbita.co`) y un miembro (`camila@orbita.co`), ambos con la contraseña del prototipo. Esto
reemplaza el target `make user`, que queda como atajo.

### Task 3 — Tokens de diseño

`resources/css/app.css` — reescribir el bloque `:root` con los tokens de arriba. Mantener la
sintaxis Tailwind 4 (`@theme` mapeando utilidades a variables) que ya usa el kit. Conservar los
nombres de variable de shadcn (`--background`, `--card`, `--primary`, `--ring`, `--sidebar*`) para
no romper los componentes de `components/ui/`, cambiando solo sus valores.

Añadir los tokens propios de Orbita que shadcn no tiene: `--accent-hover`, `--accent-text`,
`--accent-surface`, `--surface-subtle`, `--surface-track`, la sombra de card, el glow del shell y
los ocho pares de badge.

Fuentes: cargar Geist y Geist Mono (el kit usa `bunny()` en `vite.config.ts` para Instrument Sans;
reemplazar ahí), con SF Pro primero en el stack tal como el prototipo.

Radio: `--radius: 16px` para cards. Los botones ya no derivan del radio global — pasan a `999px`
explícito en la variante del componente `Button`.

### Task 4 — Eliminar modo oscuro

- Borrar `resources/js/hooks/use-appearance.tsx`, `components/appearance-tabs.tsx`,
  `pages/settings/appearance.tsx`
- Quitar la ruta `appearance.edit` de `routes/settings.php` y su entrada en la sub-nav de
  `layouts/settings/layout.tsx`
- Quitar `initializeTheme()` de `resources/js/app.tsx:40`
- Quitar el bloque `.dark { … }` y el `@custom-variant dark` de `app.css`
- Barrer las clases `dark:` de los componentes que toquemos

Verificación obligatoria: `pnpm run build` tiene que pasar — Wayfinder genera las rutas en build
time y una referencia a `appearance` ya eliminada rompe el build. Es el mismo modo de falla que ya
nos costó una corrida.

### Task 5 — Navegación por rol

Extraer la navegación a **una sola fuente**: `resources/js/config/navigation.ts`. Hoy está duplicada
en tres lugares (`components/app-sidebar.tsx:19-38`, `components/app-header.tsx:42-61`,
`layouts/settings/layout.tsx:13-29`).

Estructura con grupos, derivada del prototipo:

```ts
type NavGroup = { label?: string; items: NavItem[] };
```

- **admin** — `Panel` · grupo *Comercial*: Clientes, Cotizaciones · grupo *Operación*: Proyectos,
  Documentos · grupo *Finanzas*: Facturas y pagos, Egresos · grupo *Administración*: Equipo y
  costos, Configuración (colapsable: Usuarios y roles, Parámetros, Maestros y listas)
- **member** — `Mi panel` · grupo *Operación*: Proyectos, Mi tiempo, Documentos

Los destinos que aún no existen quedan deshabilitados y visibles, no ocultos: el nav es el mapa del
producto y ocultarlos lo haría mentir. Un ítem deshabilitado no navega.

`HandleInertiaRequests::share()` pasa a compartir el rol y el nav ya resuelto en el servidor, para
que el cliente no decida permisos. Hoy comparte solo `name`, `auth.user`, `sidebarOpen`
(`app/Http/Middleware/HandleInertiaRequests.php:36-46`).

Actualizar `resources/js/types/global.d.ts`, que es lo que tipa `usePage().props` vía
`InertiaConfig['sharedPageProps']`.

Borrar el código muerto que el mapa encontró: `components/app-header.tsx`,
`layouts/app/app-header-layout.tsx`, `layouts/auth/auth-card-layout.tsx`,
`layouts/auth/auth-split-layout.tsx` — ninguno es alcanzable desde `app.tsx:13-24`.

### Task 6 — Shell visual

`components/app-sidebar.tsx` — 244px/68px colapsado, fondo translúcido con blur, grupos con título
en 10px uppercase, ítems con tile de icono coloreado por sección, estados default/hover/activo del
prototipo.

`components/app-sidebar-header.tsx` — 56px, sticky, translúcido, con breadcrumbs. Conservar el
mecanismo de breadcrumbs tal cual: `Page.layout.breadcrumbs` →
`AppLayout` → `AppSidebarLayout` → `AppSidebarHeader` → `Breadcrumbs`
(`components/breadcrumbs.tsx:13-49`, tipo en `types/navigation.ts:4-7`).

`components/app-content.tsx` — padding `28px 32px 56px`, sin `max-width`.

`components/ui/button.tsx` — variante primaria pill con el gradiente de sombra del prototipo;
secundaria con borde `rgba(0,0,0,.1)` y hover `#f2f2f7`.

`components/ui/input.tsx` y `select` — radio `10px`, borde `rgba(0,0,0,.12)`, hover `.24`, y el
anillo de foco naranja.

Reutilizar lo que ya sirve: `SidebarProvider` con `sidebarOpen` desde cookie
(`components/app-shell.tsx`), `nav-user.tsx` + `user-menu-content.tsx` para el menú de cuenta,
`use-flash-toast.ts` + `sonner` para los toasts.

### Task 7 — Login

`resources/js/pages/auth/login.tsx` ya tiene el copy en español y el formulario correcto. Falta la
piel.

`layouts/auth/auth-simple-layout.tsx` — centrado con el glow radial del prototipo, logo de 48px con
el gradiente naranja verbatim, `h1` 26/700 `-.03em` "Inicia sesión en Orbita", subtítulo 13.5px
`#6e6e73` "Usa la cuenta que te asignó tu agencia.", y la card de login con radio 18px y la sombra
del prototipo de login.

Añadir el bloque de **cuentas de prueba** del prototipo: dos filas con borde dashed
`1px dashed #e5e5ea` que rellenan el formulario al hacer clic. Solo cuando
`config('app.debug')` — nunca en producción.

### Task 8 — Tests

Según `testing/testing`, lo que importa aquí es la frontera de autorización:

- **Matriz rol × nav**: para cada rol, el nav compartido contiene exactamente los ítems esperados.
  Un member no recibe Clientes, Cotizaciones, Facturas ni Egresos — ni siquiera deshabilitados.
- **Invariante del owner**: la base rechaza un segundo owner (índice único parcial) y rechaza
  `is_owner` con un `system_role` distinto de `owner` (check constraint). Se afirma contra Postgres
  real, porque es donde vive el invariante.
- **Policy del owner**: no se puede bloquear, degradar ni eliminar.
- **Login**: los tests existentes de `AuthenticationTest` siguen pasando; añadir que el redirect
  posterior al login lleva al panel correspondiente al rol.
- **Seeder**: siembra exactamente un owner.

No vale la pena testear: colores, render del sidebar, snapshots de props de Inertia.

## Archivos principales

| Archivo | Qué pasa |
|---|---|
| `app/Enums/SystemRole.php` | nuevo |
| `database/migrations/*_add_system_role_to_users_table.php` | nuevo, con los invariantes en SQL |
| `app/Models/User.php` | cast del enum y helpers |
| `app/Policies/UserPolicy.php` | nuevo |
| `app/Providers/AppServiceProvider.php` | `Gate::before` del owner |
| `app/Http/Middleware/HandleInertiaRequests.php` | compartir rol y nav resuelto |
| `database/seeders/DatabaseSeeder.php` | owner + member |
| `resources/css/app.css` | tokens del prototipo, sin bloque `.dark` |
| `vite.config.ts` | Geist y Geist Mono en lugar de Instrument Sans |
| `resources/js/config/navigation.ts` | nuevo, fuente única del nav |
| `resources/js/components/app-sidebar.tsx` | shell visual y grupos por rol |
| `resources/js/components/app-sidebar-header.tsx` | header 56px translúcido |
| `resources/js/components/ui/{button,input,select}.tsx` | variantes del prototipo |
| `resources/js/layouts/auth/auth-simple-layout.tsx` | piel del login |
| `resources/js/types/global.d.ts` | tipar las props compartidas nuevas |
| borrados | `app-header.tsx`, `app-header-layout.tsx`, `auth-card-layout.tsx`, `auth-split-layout.tsx`, `use-appearance.tsx`, `appearance-tabs.tsx`, `pages/settings/appearance.tsx` |

## Verificación

1. `make fresh` siembra exactamente un owner y un member; un segundo owner es rechazado por la base.
2. `make test` pasa, incluyendo la matriz rol × nav y los dos invariantes del owner.
3. `pnpm run build` pasa — prueba que no quedaron referencias de Wayfinder a `appearance`.
4. `make stan` sigue en nivel 8 sin errores; `make pint-test` pasa.
5. Login manual con `owner@orbita.co`: se ven los 4 grupos del nav.
6. Login manual con `camila@orbita.co`: se ven solo `Mi panel`, `Proyectos`, `Mi tiempo`,
   `Documentos`. El payload de Inertia **no contiene** las secciones de admin.
7. Comparación visual del login contra `Prototipo Login.dc.html`: fuente, acento naranja, radio de
   la card, glow de fondo y el bloque de cuentas de prueba.
8. No queda ninguna clase `dark:` activa ni la ruta `appearance.edit` en `make routes`.

## Riesgo

`Risk: item 2 (security)` — introduce la frontera de autorización por rol y el invariante del
owner. Por eso los invariantes van en la base además de en PHP, y la matriz rol × nav se verifica
contra Postgres real en lugar de confiar en que el nav se oculte en el cliente.
