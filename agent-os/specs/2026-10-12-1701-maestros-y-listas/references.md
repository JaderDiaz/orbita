# Referencias para Maestros y Listas

## El prototipo

### El módulo de Maestros

- **Ubicación:** `Prototipo Admin.dc.html` (5799 líneas) del proyecto de Claude Design
  `4ba0fa0d-992f-4439-a02a-311350030ea9`
  - markup `<sc-if value="{{ isMaestros }}">`: líneas **1423-1552**
  - `msAct(k)`: 3382 · `get listas()`: 3383 · `msPal`: 3384 · `msIcons`: 3385-3394
  - **`msSeed()`: 3395-3418** — la semilla completa
  - **`maestrosVals(pick)`: 3419-3500** — todo el comportamiento
  - nav (`SUBIC.maestros`, `subItem()`, `configScreens`): 3908-3914; `navGrupos`: 3916-3931;
    breadcrumbs: 3938-3952
- **Relevancia:** es la fuente de la semilla y del copy literal.
- **Qué reutilizar:** la semilla, el copy y la forma maestro/detalle. **Qué no reutilizar:** la
  identidad derivada del índice (`id = key + "-" + index`), el conteo `uso` estático, y el
  intercambio de vecinos para reordenar.

### Codificación de la semilla

`L(key, grupo, titulo, desc, usado, campos, flag, rows)` donde
`rows[i] = [label, code, col, icon, desc, flag, uso, def]`. Un `0` en `col` o `icon` significa
"ninguno" y cae a `gris`. Todos los valores de la semilla nacen activos.

## El shell construido en el spec anterior

### Tokens de diseño

- **Ubicación:** `resources/css/app.css`
- **Qué reutilizar:** `--brand*` (7 tokens), `--surface-subtle` `#f9f9fb` (hover de fila y footer de
  tabla), `--surface-muted`, `--surface-track`, `--text-secondary`, `--text-tertiary` `#8e8e93`
  (encabezados de tabla), `--radius-card` 16px, `--radius-pill` 999px, `--shadow-card`, las
  utilidades `shell-glow` y `focus-ring-brand`, y la clase `.tabular` para columnas numéricas.
- **Nota:** `@custom-variant dark (&:not(*))` en la línea 25 hace que cualquier clase `dark:` nunca
  coincida. No agregar variantes oscuras.

### Navegación resuelta en el servidor

- **Ubicación:** `app/Support/Navigation/SidebarNavigation.php:72-77` (el ítem),
  `:114-122` (el helper `item()`)
- **Relevancia:** `maestros` es el tercer hijo del colapsable `Configuración`, dentro del grupo
  `Administración`. `enabled` se deriva de `$href !== null`.
- **Qué reutilizar:** el mecanismo tal cual. Encenderlo es pasar `route('maestros.index')` como
  tercer argumento.

### El renderizador del nav

- **Ubicación:** `resources/js/components/nav-main.tsx:73-113` (`NestedItem`)
- **Relevancia:** el colapsable arranca con `useState(false)` y **no** se auto-abre cuando la URL
  actual corresponde a un hijo, ni pasa `isActive` al sub-botón.
- **Qué cambiar:** abrir el grupo cuando un hijo coincide con la URL, usando `isCurrentOrParentUrl`
  de `resources/js/hooks/use-current-url.ts`, y marcar el sub-ítem activo con el estilo del
  prototipo (`rgba(249,115,22,.1)` / `#c2410c` / 600).

### Estilo de migración con invariantes en la base

- **Ubicación:** `database/migrations/2026_10_12_160000_add_system_role_to_users_table.php`
- **Qué reutilizar:** Schema builder para columnas e índices; `DB::statement()` con nombres de
  restricción explícitos para check constraints e índices parciales; `down()` que los elimina con
  `IF EXISTS` en orden inverso; comentarios que explican por qué el invariante vive en la base.

### Patrón de controller

- **Ubicación:** `app/Http/Controllers/Settings/ProfileController.php`
- **Qué reutilizar:** clase con métodos nombrados, **siempre** Form Request,
  `Inertia::flash('toast', ['type' => ..., 'message' => ...])` seguido de `to_route(...)`, y
  `assert($x instanceof Y)` como idioma de estrechamiento para Larastan nivel 8.

### Patrón de formulario

- **Ubicación:** `resources/js/pages/settings/profile.tsx:32-90`
- **Qué reutilizar:** `<Form {...Controller.method.form()}>` con render prop. **Nunca `useForm`.**
  Errores siempre vía `<InputError message={errors.campo} />`.

### Confirmación destructiva

- **Ubicación:** `resources/js/components/delete-user.tsx`
- **Relevancia:** es el patrón de confirmación del proyecto, pero usa `Dialog`.
- **Qué tomar:** el uso de `Form` con una action de Wayfinder. **Qué cambiar:** en Maestros la
  confirmación es una tira **en línea dentro de la fila**, como el prototipo.

### Convenciones de test

- **Ubicación:** `tests/Feature/NavigationTest.php`
- **Qué reutilizar:** clases PHPUnit con `test_snake_case()` — **el repo no usa Pest funcional** —,
  `use RefreshDatabase`, `actingAs(User::factory()->admin()->create())`, assertions sobre el payload
  de Inertia con `AssertableInertia`, extracción cruda con `viewData('page')['props']`, y mensajes
  de fallo en español.

### Promoción del entorno de PHPUnit

- **Ubicación:** `tests/TestCase.php:31-49`, con guarda en `tests/Unit/TestDatabaseTargetTest.php`
- **Relevancia:** `env()` de Laravel lee `$_SERVER` antes que `$_ENV`, y Docker inyecta la base de
  desarrollo en `$_SERVER`. Sin esta promoción la suite correría contra la base de desarrollo y
  `RefreshDatabase` la borraría.
- **Qué reutilizar:** nada que cambiar. Solo no romperlo.

## El estado real de la base

Verificado ejecutando contra el contenedor:

- Extensiones instaladas: `pg_trgm` 1.6, `plpgsql` 1.0, `unaccent` 1.1.
- **`citext` NO está instalada**, aunque está disponible en la imagen en versión 1.8.
- Tablas existentes: solo las del framework (`cache`, `cache_locks`, `failed_jobs`, `job_batches`,
  `jobs`, `migrations`, `sessions`) más `users`.
