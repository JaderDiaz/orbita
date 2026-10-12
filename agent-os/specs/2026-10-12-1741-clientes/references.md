# Referencias para Clientes

## Maestros y Listas — la implementación de referencia

Construido en el spec anterior, en este mismo repo. Es el patrón para casi todo lo que hace
Clientes, y conviene copiarlo en vez de inventar variantes.

### Migración con invariantes en la base

- **Ubicación:** `database/migrations/2026_10_12_170200_create_catalog_tables.php`
- **Qué reutilizar:** Schema builder para columnas e índices, `DB::statement()` con nombres de
  restricción explícitos para check constraints e índices parciales y expresiones `lower()`,
  `down()` que los elimina en orden inverso, y comentarios que explican por qué cada invariante
  vive en la base.
- **Nota:** las extensiones `citext`, `pg_trgm` y `unaccent` ya están creadas por
  `2026_10_12_170100_create_postgres_extensions.php`. No hay que volver a declararlas.

### El patrón de borrado bloqueado

- **Ubicación:** `app/Actions/Catalogo/DeleteCatalogValue.php`
- **Relevancia:** es exactamente la regla que Clientes necesita — borrar solo si nada referencia,
  archivar si hay historial.
- **Qué reutilizar:** la comprobación doble (registro de uso para la pantalla, llave foránea como
  garantía real), la traducción de la violación a una excepción de dominio, y el `DB::transaction`
  que la envuelve.
- **Lo que costó descubrir:** Postgres lanza **`23001` (`restrict_violation`)** para un
  `ON DELETE RESTRICT`, no `23503`. Y una sentencia fallida aborta la transacción envolvente, por
  eso el intento va en su propio savepoint.

### El registro de uso

- **Ubicación:** `app/Support/Catalogo/UsageRegistry.php`
- **Relevancia:** Clientes es su **primer consumidor**: debe declarar `tipo_documento_value_id` y
  `regimen_value_id` en `references()` para que un valor del catálogo en uso deje de ser borrable.
- **Qué reutilizar:** la forma de la clase para `ClienteUsage`, que contará las referencias a
  `clientes` cuando existan Proyectos y Facturas.

### Resolver del catálogo

- **Ubicación:** `app/Support/Catalogo/Catalogo.php`, `app/Models/CatalogValue.php`
- **Qué reutilizar:** `CatalogValue::matches(SystemKey)` para cualquier bifurcación por régimen.
  **Nunca** comparar contra `name`: hay un test de arquitectura que lo prohíbe
  (`tests/Unit/Catalogo/CatalogArchitectureTest.php`).
- **Nota:** el método se llama `matches` y no `is` porque Eloquent ya define `Model::is($model)`.

### Controller, policy y rutas

- **Ubicación:** `app/Http/Controllers/MaestrosController.php`, `app/Policies/CatalogValuePolicy.php`
- **Qué reutilizar:** atributos `#[Authorize]` por método con `only:`, Form Requests siempre,
  `Inertia::flash('toast', …)` seguido de `to_route()`, y `assert($x instanceof Y)` como idioma de
  estrechamiento para Larastan nivel 8.
- **Cuidado con el gate del owner:** `AppServiceProvider::configureAuthorization()` concede al
  owner toda habilidad salvo las que cada policy declara como no delegables. Si `ClientePolicy`
  necesita una habilidad que ningún rol pueda recibir a ciegas, hay que declararla ahí.

### Pantalla, editor en línea y confirmación en la fila

- **Ubicación:** `resources/js/pages/maestros/index.tsx`, `resources/js/features/maestros/`
- **Qué reutilizar:** la estructura de `features/<modulo>/` con `types.ts`, el corte
  contenedor/presentación, `<Form {...Controller.method.form()}>` con render prop,
  `<InputError message={errors.x} />`, y los toggles y píldoras ya resueltos.
- **Qué cambiar:** Clientes usa páginas separadas para listado, formulario y detalle, no un
  maestro/detalle en una sola pantalla.

### Tabla

- **Ubicación:** `resources/js/components/ui/table.tsx`
- **Qué reutilizar:** tal cual. Ya trae el encabezado de 11px en mayúsculas con el color terciario,
  el divisor y el hover de fila del prototipo.

### Convenciones de test

- **Ubicación:** `tests/Feature/Catalogo/`, `tests/Unit/Catalogo/`
- **Qué reutilizar:** clases PHPUnit con `test_snake_case()` — **el repo no usa Pest funcional** —,
  `RefreshDatabase`, assertions sobre el payload de Inertia, mensajes de fallo en español, y la
  técnica de insertar crudo con `DB::table()` para ejercitar un check constraint que un cast de
  enum interceptaría antes.
- **Para los invariantes nuevos:** `tests/Feature/Catalogo/CatalogSchemaTest.php` es el modelo.

## El prototipo

- **Ubicación:** ver `visuals/README.md` para los rangos de línea.
- **Qué reutilizar:** el copy literal, la estructura del formulario en dos columnas con resumen
  lateral, y el algoritmo de `genTag()`.
- **Qué NO reutilizar:** la identidad por nombre de empresa, el `uso` estático, el tag sin
  desambiguar, las notas por `dangerouslySetInnerHTML`, y los campos fiscales incompletos.

## El starter kit

- **Ubicación:** `resources/js/app.tsx:15-26`
- **Relevancia:** los layouts se resuelven centralmente por nombre de página. `pages/clientes/*`
  cae en el `default` y hereda `AppLayout` sin tocar nada.
- **Ubicación:** `resources/js/hooks/use-current-url.ts`
- **Relevancia:** `isCurrentOrParentUrl` ya existe, así que `/clientes/5/editar` marcará activo el
  ítem del nav sin trabajo extra.
