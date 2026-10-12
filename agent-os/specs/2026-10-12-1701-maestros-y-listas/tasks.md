# Maestros y Listas — bitácora

Objetivo: el catálogo de enumeraciones editables del que depende el resto del producto.
Rama: `feat/maestros-y-listas` (desde `feat/login-y-layout`, que aún no está en `main`).
Runner: `make test` (PHPUnit sobre Postgres real), `make check` para todas las compuertas.

## Tareas

| ID | Tarea | Estado | Commit |
| --- | --- | --- | --- |
| T1 | Guardar la documentación del spec | hecho | 107def0 |
| T2 | Extensiones y esquema del catálogo | hecho | 595b6a0 |
| T3 | Modelos y soporte (`Catalogo`, enums de `system_key`) | hecho | 2e37c69 |
| T4 | Semilla del catálogo (16 listas, 73 valores) | hecho | b864422 |
| T5 | `UsageRegistry` | hecho | b5f418f |
| T6 | Primitivos de UI y sub-nav activo | hecho | d0500b9 |
| T7 | Pantallas de Maestros | hecho | d75f047 |
| T8 | Escrituras, autorización y rutas | hecho | d75f047 |
| T9 | Tests transversales | hecho | — |

Fuera de las tareas: `1051c8f` corrige el entorno de pruebas (ver L4).

T7 y T8 quedaron en un solo commit porque son mutuamente dependientes: la página no compila sin
las rutas de Wayfinder, y el test de la ruta no pasa sin el componente de la página.

## Bitácora

**L1 — pedido del usuario, verbatim**

> /agent-os:shape-spec módulo de clientes, listar, crear, editar, eliminar, consulta el diseño y
> actualiza la información que tengas de este módulo para tener todo lo ultimo

**L2 — fork y decisión del usuario**

Clientes necesita listas con llave foránea a `catalog_values`, y esas tablas no existen. Ante tres
opciones (catálogo mínimo dentro del spec de Clientes / Maestros completo primero / columnas simples
y migrar después), el usuario eligió **"Maestros y Listas completo primero"**. Clientes pasa a ser
el spec siguiente; su extracción del prototipo ya está guardada en memoria para no repetirla.

**L3 — hallazgos verificados contra el entorno real**

- `citext` no está instalada en la base (disponible en la imagen, 1.8), y los estándares la usan.
- Las extensiones las crea `docker/postgres/init/01-init.sql`, que solo corre al inicializar un
  volumen vacío. Van por migración.
- `maestros` es hijo del colapsable `Configuración`, y `NestedItem` no se auto-abre ni marca el
  sub-ítem activo.

**L4 — bug encontrado al escribir el primer test de cache**

La suite corría con cache, sesión y cola en base de datos, no con los drivers que declara
`phpunit.xml`. Compose inyecta `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` y `MAIL_MAILER`
en `$_SERVER`, que `env()` lee antes que `$_ENV`, y esas entradas no llevaban `force="true"`. Es el
mismo agujero ya corregido para `DB_*`. Corregido en `1051c8f` con un test de guarda.

**L5 — tres cosas que solo aparecieron contra Postgres real o en el navegador**

1. Un `ON DELETE RESTRICT` lanza **SQLSTATE 23001** (`restrict_violation`), no 23503. Asumir 23503
   hacía que la negativa saliera como un 500 en vez de una explicación.
2. Una sentencia fallida aborta la transacción envolvente en Postgres, así que el intento de
   borrado corre en su propio savepoint y deja utilizable la transacción del llamador.
3. Las flechas de reordenar quedaban **siempre** deshabilitadas: la guarda exigía el toggle de
   inactivos, que arranca apagado, así que una lista sin inactivos nunca se podía reordenar aunque
   ocultarlos no escondiera nada.

**L6 — desviaciones del plan aprobado, con su razón**

- **No se construyó `ui/pagination`.** La pantalla de Maestros del prototipo no pagina: la lista
  más grande tiene siete valores, así que el primitivo habría quedado sin quien lo use. Pertenece
  al spec de Clientes, cuyo listado sí pagina a 5/10/25 filas.
- **Los 8 pares de badge no se duplicaron en el frontend.** Se resuelven desde el enum
  `CatalogColor` en el servidor y viajan con el dato, así la paleta tiene una sola definición.
- **El filtrado es del lado del cliente.** El catálogo completo son 73 valores en un set acotado y
  administrativo; la regla de filtrado server-side del estándar existe para tablas
  transaccionales sin cota, donde el problema es enviar las filas.
