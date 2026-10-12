# Login y Layout — Notas de shaping

## Alcance

Convertir el cascarón del starter kit en el shell real de Orbita: pantalla de login y layout
interno con navegación por rol, sobre el sistema de diseño del prototipo.

**Entra**

- Roles del sistema: enum PHP `owner|admin|member|client`, columna en `users`, invariante del owner
- Nav por rol: admin ve 4 grupos; member ve 4 ítems
- Sistema de diseño del prototipo aplicado a login y layout
- Login con la identidad de Orbita
- Eliminar modo oscuro

**No entra**

- Paleta de comandos ⌘K
- Cualquier módulo de negocio
- Portal del cliente (el rol existe en el enum, sin vistas)

## Decisiones

| Decisión                                | Razón                                                                                                                                                                                             |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Roles del sistema **ahora**, no después | La frontera de permisos no conviene retrofitearla. El rol decide qué ve cada usuario en el nav y más adelante qué datos recibe; introducirlo después implica revisar cada pantalla ya construida. |
| Tokens de diseño **en este spec**       | Cada pantalla nueva nace con los colores y radios que encuentre. Aplicarlos con dos pantallas es barato; con veinte es un barrido completo.                                                       |
| ⌘K **fuera**                            | No existen las entidades que indexaría. Solo podría listar secciones del nav.                                                                                                                     |
| Modo oscuro **eliminado**               | El prototipo define un único tema claro y no tiene media queries. Mantener oscuro obliga a interpretar cada pantalla dos veces y a duplicar cada token.                                           |

Instrucción explícita del usuario sobre el diseño: _"adapta el uso de los colores y estilos a los
aplicados en el diseño"_. Por eso los tokens se extrajeron del prototipo en lugar de proponerse.

### Decisiones de implementación derivadas

- **Los ítems del nav sin módulo quedan visibles pero deshabilitados**, no ocultos. El nav es el
  mapa del producto; ocultarlos lo haría mentir. Un ítem deshabilitado no navega.
- **El nav se resuelve en el servidor** y se comparte ya filtrado por rol. El cliente no decide
  permisos. El test verifica que el payload de Inertia de un member no contenga siquiera las
  secciones de admin.
- **Los invariantes del owner van en Postgres**, no solo en PHP: índice único parcial y check
  constraints. Romper ese invariante no tiene vuelta.
- **Una sola fuente de navegación.** Hoy está duplicada en tres archivos.

## Contexto

- **Visuales:** proyecto de Claude Design `4ba0fa0d-992f-4439-a02a-311350030ea9`
  ("Orbita - Administrador de Proyectos"), archivos `Prototipo Login.dc.html`,
  `Prototipo Admin.dc.html`, `Prototipo Equipo.dc.html`. Ver `visuals/README.md`.
- **Referencias de código:** el starter kit oficial de Laravel React, ya instalado. Ver
  `references.md`.
- **Alineación con el producto:** `agent-os/product/roadmap.md` ubica esto en la Fase 0
  (fundaciones), antes de cualquier módulo. `mission.md` define los tres roles y la regla de que un
  Administrador también actúa como Miembro.

## Correcciones a supuestos previos

Dos cosas que se habían asumido mal y la extracción del prototipo corrigió:

1. Las cards son de **16px** de radio, no 18px. Los 18px son solo el hero oscuro de KPI y la paleta
   de comandos.
2. La fuente primaria es **SF Pro Text con Geist como fallback**, no Geist primero. El stack real es
   `-apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Geist', system-ui, sans-serif`.

## Hallazgos sobre el uso del color

- El **chip de filtro activo es oscuro** (`#1d1d1f` fondo, `#fff` texto), no naranja. El naranja
  (`#fff7ed`/`#c2410c`/borde `#f97316`) está reservado para _selección_, que es otra cosa.
- Hay **un solo anillo de foco** repetido en todo control interactivo. Es un token reutilizable, no
  una decisión por componente.
- Los **tiles de icono del sidebar tienen un hue por sección**. El naranja no domina el nav; se usa
  con disciplina: una acción primaria sólida por pantalla.

## Estándares aplicados

- `backend/authorization` — es el central: roles del sistema como enum separados de los cargos,
  invariante del owner, defensa en profundidad
- `javascript/react-inertia` — Wayfinder genera rutas en build time, estructura de carpetas,
  container-presentational
- `global/conventions` — código en inglés, vocabulario de dominio en español, UI en español
- `database/postgres` — invariantes en la base, no solo en PHP
- `testing/testing` — matriz rol × ruta, tests sin base de datos donde aplique

No aplican: `backend/money`, `backend/taxes-colombia`, `backend/document-numbering`,
`backend/catalog-master-data`, `backend/settings-parameters`.
