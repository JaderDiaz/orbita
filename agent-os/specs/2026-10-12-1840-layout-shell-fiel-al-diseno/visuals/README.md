# Visuales

El diseño vive en Claude Design, no como archivos en este repo.

**Proyecto:** `4ba0fa0d-992f-4439-a02a-311350030ea9` — "Orbita - Administrador de Proyectos"
**Archivo:** `Prototipo Admin.dc.html` — 5799 líneas, etag `1791839678113816`

## Dónde está el shell

El chasis completo ocupa las **líneas 25-126**, antes de cualquier pantalla. Es la primera mitad del
archivo; las medidas viven en la segunda, dentro del bloque `<script type="text/x-dc">`.

| Líneas      | Qué contiene                                                                      |
| ----------- | --------------------------------------------------------------------------------- |
| `14-22`     | El único bloque `<style>`: `body` con `background:#f5f5f7` y el stack tipográfico  |
| `25`        | Marco raíz: `display:flex;min-height:100vh;background:#f5f5f7`                     |
| `27`        | `<aside>`: `width:{{asideW}}`, `sticky top:0 height:100vh`, `blur(24px) saturate(180%)`, `border-right` |
| `28-37`     | Bloque de marca: tile de 30px, "Orbita / Agencia", y el control de colapsar (l. 34) |
| `39-62`     | Zona de navegación: grupos (`gap:12px`), títulos (l. 43), ítems (l. 47), sub-ítems (l. 55) |
| `64-85`     | Pie del sidebar: botón de expandir (l. 66), fila de perfil (l. 70-80), salir (l. 81-83) |
| `88`        | `<main>`: `flex:1` con los dos `radial-gradient` del glow                          |
| `90`        | `<header>`: `height:56px`, `sticky top:0 z-index:20`, `blur(20px)`, `padding:0 28px` |
| `91-95`     | Breadcrumbs: `{{crumb0}}` `/` `{{crumb1}}`, separador en `#d1d1d6`                 |
| `96-107`    | Cluster derecho: buscador (**fuera de alcance**) y píldora de usuario              |
| `108-124`   | Menú de usuario: 270px, radio 16px, "Mi perfil", "Contraseña y seguridad", "Cerrar sesión" |
| `126`       | Contenedor de contenido: `flex:1;padding:28px 32px 56px;width:100%;min-width:0`    |
| `3727-3757` | `renderVals()`: `asideW` 244/68px, `navPadX` 12/10px, `logoPadR` 12/18px, `footPad` 8/0px |
| `3897`      | Mapa `TILE` de hues por sección                                                    |
| `3899-3902` | `item()`: estado activo del nav — `bg #fff`, `fg #1d1d1f`, `fw 600`, con su sombra |
| `3903-3911` | `subItem()`: sub-nav activo `rgba(249,115,22,.1)` / `#c2410c`                      |

## Fuera de alcance en este spec

- `96-102` — la píldora de búsqueda del header
- `3328-3371` — la paleta de comandos `⌘K`

## Nota sobre responsive

El prototipo **no tiene ninguno**: cero `@media`, cero drawer, cero hamburguesa. El único modo es
colapsar a 68px. El `Sheet` de móvil de shadcn se conserva como adición deliberada.
