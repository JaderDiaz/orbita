# Orbita — El shell fiel al diseño

## Context

Pediste ajustar el layout para respetar más el diseño. **Verifiqué antes de darte la razón**, y la
tienes: medí el shell en el navegador real y leí el markup literal del prototipo. El resultado es
nítido.

Del prototipo se portó la **piel** —colores, gradientes, tipografías, el tile coloreado de cada
sección— pero **no la estructura**. El chasis sigue siendo el de shadcn con sus valores de fábrica.
Es exactamente el mismo error que corregimos en el formulario de Clientes hace dos mensajes: se
construyó desde una extracción en prosa de los tokens, no desde el markup.

La prueba más clara: `--shadow-nav-active` está definido en `app.css:111` y **no lo usa nadie**. El
ítem activo del nav debería ser blanco con esa sombra; hoy es gris, porque hereda el
`data-[active=true]:bg-sidebar-accent` de shadcn, que es el mismo color del hover.

Resultado buscado: que el shell —sidebar, header y contenedor de contenido— sea el del prototipo,
no el del starter kit.

## Lo que está medido, no supuesto

Medido en `http://localhost:8100/clientes` con `getComputedStyle`, contra el markup literal de
`Prototipo Admin.dc.html` (líneas 25-126).

| Qué                 | Prototipo                                       | Hoy                                   | Dónde está hoy                   |
| ------------------- | ----------------------------------------------- | ------------------------------------- | -------------------------------- |
| Ancho del sidebar   | `244px` (l. 3727 `asideW`)                      | `16rem` = **256px**                   | `ui/sidebar.tsx:28`              |
| Colapsado           | `68px`                                          | `3rem` = **48px**                     | `ui/sidebar.tsx:30`              |
| Posición            | `sticky; top:0; height:100vh` + `border-right`  | `fixed`, tarjeta flotante sin borde   | `app-sidebar.tsx:18` `variant`   |
| Blur del sidebar    | `blur(24px) saturate(180%)`                     | **ninguno** (0 usos de `backdrop`)    | —                                |
| Alto del header     | `56px`                                          | **64px** (`h-16`)                     | `app-sidebar-header.tsx:11`      |
| Header              | `sticky; top:0; z-20` + `blur(20px)`            | `static`, sin blur                    | `app-sidebar-header.tsx:11`      |
| Padding del header  | `0 28px`                                        | `px-6 md:px-4` → **16px**             | `app-sidebar-header.tsx:11`      |
| Contenido           | `padding:28px 32px 56px` (l. 126)               | **0** — pegado al borde               | `app-content.tsx:11`             |
| Fondo del `<main>`  | dos `radial-gradient` (l. 88)                   | plano `#f5f5f7`                       | `@utility shell-glow` sin usar   |
| Ítem de nav         | `padding:5px 8px; gap:10px; 13.5px`             | `p-2 gap-2 text-sm` → 8/8/14px        | `ui/sidebar.tsx:470`             |
| Ítem activo         | `#fff` + `--shadow-nav-active` + `fw 600`       | `rgba(0,0,0,.04)`, sin sombra, 500    | token muerto en `app.css:111`    |
| Título de grupo     | `padding:4px 10px 3px`, peso normal, alto auto  | `h-8` (32px) y `font-semibold`        | `nav-main.tsx:154` sobre stock   |
| Sub-ítem            | `padding:6px 10px 6px 38px`, sin riel           | `mx-3.5 border-l px-2.5` (riel)       | `ui/sidebar.tsx:639`             |
| Separador de crumbs | `/` en `#d1d1d6`                                | chevron `›` de shadcn                 | `ui/breadcrumb.tsx`              |
| Control de colapso  | **dentro** del sidebar, "Colapsar menú"         | en el header, "Toggle sidebar" (en)   | `app-sidebar-header.tsx:13`      |
| Pie del sidebar     | fila de perfil **+ botón de salir** separado    | un solo dropdown                      | `nav-user.tsx`                   |
| Menú de usuario     | píldora en el **header**, a la derecha          | no existe                             | —                                |

El logo también: el prototipo usa **30px** con sombra `0 2px 6px rgba(249,115,22,.35)`; hoy es
`size-8` (32px) con `0 6px 16px -4px rgba(249,115,22,.5)` (`app-logo-icon.tsx:21-23`).

## Alcance

Elegiste **chasis + menú de usuario**. Sin píldora de búsqueda ni paleta `⌘K`: esos son una
funcionalidad aparte, y un buscador que no busca es un control que miente.

## Decisiones

| Decisión                                                        | Razón                                                                                                                                                                                                                 |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Se conserva el primitivo `Sidebar` de shadcn**, no se reemplaza | Aporta cosas que el prototipo no tiene: persistencia por cookie, atajo `⌘B`, tooltips al colapsar y el `Sheet` de móvil. Basta cambiar `variant="inset"` → `"sidebar"` y las constantes de ancho para matar la tarjeta flotante. |
| **El móvil se mantiene con el `Sheet` de shadcn**                | El prototipo **no tiene responsive**: cero `@media`, cero drawer. No es una omisión que debamos copiar. Queda documentado como adición deliberada, no como desvío.                                                      |
| **El toggle vuelve al sidebar**                                  | Es donde el prototipo lo pone (l. 34, "Colapsar menú"; l. 66, "Expandir menú" al estar colapsado). De paso elimina el `Toggle sidebar` en inglés que hoy se ve en el header.                                            |
| **El acceso a la cuenta queda duplicado**                        | No es un descuido mío: el prototipo tiene la fila de perfil en el pie del sidebar (l. 70-83) **y** la píldora con menú en el header (l. 103-124). Se portan ambos.                                                     |
| **Las medidas se vuelven tokens CSS**                            | `--sidebar-width`, `--sidebar-width-icon` y un nuevo `--header-height` en `app.css`, no literales repartidos por el TSX. Hoy `clientes/form.tsx:349` usa `lg:top-[84px]` y `maestros/list-rail.tsx:26` usa `lg:top-20`: dos offsets distintos para el mismo header. |
| **Nada de tests nuevos**                                         | La convención del repo lo dice: cambios de estilo y layout no los piden. `tests/Feature/NavigationTest.php` prueba las props del servidor, que no se tocan, y debe seguir verde.                                        |

## Tareas

### Task 1 — Guardar la documentación del spec

`agent-os/specs/2026-10-12-1840-layout-shell-fiel-al-diseno/` con `plan.md`, `shape.md`,
`standards.md`, `references.md` y `visuals/README.md` con los rangos de línea del prototipo
(25-126 el shell, 3727-3757 `renderVals()` donde viven `asideW` y los paddings).

Aplican: `javascript/react-inertia`, `global/conventions`. No aplican los de backend: no se toca
PHP.

### Task 2 — Las medidas del chasis

- `resources/css/app.css` — añadir `--sidebar-width: 244px`, `--sidebar-width-icon: 68px`,
  `--header-height: 56px` y `--content-offset: 84px` (header + los 28px de padding superior).
- `resources/js/components/ui/sidebar.tsx` — `SIDEBAR_WIDTH` y `SIDEBAR_WIDTH_ICON` pasan a leer
  esos tokens en vez de `16rem`/`3rem`.
- `resources/js/components/app-sidebar.tsx:18` — `variant="inset"` → `variant="sidebar"`. Esto solo
  ya elimina el `m-2 ml-0 rounded-xl shadow-sm` del `SidebarInset`, que es la tarjeta flotante que
  hoy se ve.
- `resources/js/components/app-content.tsx:11` — el `SidebarInset` recibe el padding
  `px-8 pt-7 pb-14` (28/32/56) y la utilidad `shell-glow`, que ya existe en `app.css:202` y hoy solo
  consume el login.
- `resources/views/app.blade.php` — `<html>` se queda con el fondo oscuro de shadcn
  (`oklch(0.145 0 0)`, medido); debe ser `#f5f5f7` para que el rebote del scroll no muestre negro.

### Task 3 — La piel del sidebar

- `app-sidebar-header.tsx` deja de llevar el `SidebarTrigger`; se mueve al bloque del logo en
  `app-sidebar.tsx`, con `title="Colapsar menú"`, 28px y radio 8px, más el de expandir en el pie.
- `app-logo-icon.tsx:21-23` — 30px y la sombra correcta.
- `app-logo.tsx:11` — "Agencia" pasa de `text-text-tertiary` (`#8e8e93`) a `#6e6e73`.
- `nav-main.tsx` — ítem a `px-2 py-[5px] gap-2.5 text-[13.5px]`; el activo a `bg-white font-semibold
  shadow-nav-active` consumiendo por fin el token; el título de grupo pierde `h-8` y
  `font-semibold`; el sub-ítem pierde el riel (`border-l`) y gana `pl-[38px]`.
- `nav-user.tsx` — se convierte en la fila de perfil del prototipo (avatar redondo de 30px con
  `linear-gradient(135deg,#a78bfa,#6366f1)`, nombre 12.5px/600, rol 11px) más un botón de salir
  aparte, 32px, `title="Cerrar sesión"`, hover `bg-[#ffeceb] text-destructive`.

### Task 4 — El header

- `app-sidebar-header.tsx` — `h-[--header-height] sticky top-0 z-20`, fondo `rgba(245,245,247,.72)`
  con `backdrop-blur-[20px] saturate-180`, `border-b`, `px-7`, y `justify-between`.
- `breadcrumbs.tsx` — separador `/` en `#d1d1d6`, crumbs intermedios `#8e8e93`, el último
  `font-medium`, todo a 13.5px con `gap-[9px]`.
- Nuevo `components/user-pill.tsx` a la derecha: avatar de 28px, nombre de pila 12.5px/500, `▾`, y
  el dropdown de 270px con radio 16px, sombra `0 18px 50px -12px rgba(0,0,0,.28)`, cabecera con
  nombre, correo y la píldora de rol (`bg-[#fff7ed] text-[#c2410c]`), y los ítems **en español**:
  `Mi perfil`, `Contraseña y seguridad`, `Cerrar sesión` en `#d70015`.
- `user-menu-content.tsx` — se reusa para los dos menús (pie y header) con el copy en español, para
  no tener dos listas que se desincronicen.

### Task 5 — Reconciliar lo que dependía del header

`clientes/form.tsx:349` (`lg:top-[84px]`) y `maestros/features/list-rail.tsx:26` (`lg:top-20`)
pasan a `lg:top-(--content-offset)`. Hoy discrepan entre sí y ninguno coincidía con un header real.

## Archivos principales

| Archivo                                            | Qué pasa                                           |
| -------------------------------------------------- | -------------------------------------------------- |
| `resources/css/app.css`                            | tokens de medida del shell                         |
| `resources/js/components/ui/sidebar.tsx`           | anchos desde tokens                                |
| `resources/js/components/app-sidebar.tsx`          | `variant="sidebar"`, toggle en el logo             |
| `resources/js/components/app-content.tsx`          | padding 28/32/56 y `shell-glow`                    |
| `resources/js/components/app-sidebar-header.tsx`   | 56px sticky translúcido, `justify-between`         |
| `resources/js/components/nav-main.tsx`             | métricas del ítem y el activo blanco               |
| `resources/js/components/nav-user.tsx`             | fila de perfil + botón de salir                    |
| `resources/js/components/user-pill.tsx`            | **nuevo** — píldora y menú del header              |
| `resources/js/components/breadcrumbs.tsx`          | separador `/`                                      |
| `resources/js/components/app-logo-icon.tsx`        | 30px y sombra correcta                             |
| `resources/views/app.blade.php`                    | fondo de `<html>`                                  |

## Verificación

1. `make check` en verde, con `tests/Feature/NavigationTest.php` intacto.
2. En `http://localhost:8100/clientes`, volver a medir con `getComputedStyle` y confirmar:
   sidebar `244px`, header `56px` y `position: sticky`, contenido con `padding: 28px 32px 56px`,
   `backdrop-filter` presente en sidebar y header, y **ninguna** `border-radius` en el `SidebarInset`.
3. Colapsar el sidebar y confirmar `68px`, con el tile centrado y los tooltips vivos.
4. El ítem activo del nav es blanco con sombra, distinguible del hover gris.
5. El menú de usuario del header y el pie del sidebar llevan el mismo copy en español.
6. A 390px de ancho el `Sheet` de móvil sigue funcionando.
7. Recargar tras colapsar: la cookie `sidebar_state` conserva el estado.

## Riesgo

`Risk: item 3 (contracts)` — se cambian `ui/sidebar.tsx` y `breadcrumbs.tsx`, que consumen **todas**
las pantallas, no solo Clientes. Por eso la verificación recorre también Maestros y Ajustes, y por
eso los offsets sticky de las dos pantallas que dependían del header se reconcilian en la misma
tarea en vez de quedar a la deriva.
