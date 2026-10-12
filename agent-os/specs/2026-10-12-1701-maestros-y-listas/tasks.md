# Maestros y Listas — bitácora

Objetivo: el catálogo de enumeraciones editables del que depende el resto del producto.
Rama: `feat/maestros-y-listas` (desde `feat/login-y-layout`, que aún no está en `main`).
Runner: `make test` (PHPUnit sobre Postgres real), `make check` para todas las compuertas.

## Tareas

| ID | Tarea | Estado | Commit |
| --- | --- | --- | --- |
| T1 | Guardar la documentación del spec | hecho | |
| T2 | Extensiones y esquema del catálogo | pendiente | |
| T3 | Modelos y soporte (`Catalogo`, enums de `system_key`) | pendiente | |
| T4 | Semilla del catálogo (16 listas, 73 valores) | pendiente | |
| T5 | `UsageRegistry` | pendiente | |
| T6 | Primitivos de UI y sub-nav activo | pendiente | |
| T7 | Pantallas de Maestros | pendiente | |
| T8 | Escrituras, autorización y rutas | pendiente | |
| T9 | Tests transversales | pendiente | |

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
