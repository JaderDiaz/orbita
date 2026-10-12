# El shell fiel al diseño — Shaping Notes

## Scope

Ajustar el shell de la aplicación —sidebar, header y contenedor de contenido— para que sea el del
prototipo y no el del starter kit de shadcn.

El diagnóstico, medido y no supuesto: del prototipo se portó la **piel** (colores, gradientes,
tipografías, el tile coloreado por sección) pero **no la estructura**. Las medidas, el
posicionamiento, el desenfoque y el padding siguen siendo los valores de fábrica de shadcn.

## Decisions

- **Alcance elegido por el usuario: chasis + menú de usuario.** Queda fuera la píldora de búsqueda
  del header y la paleta `⌘K` (prototipo líneas 3328-3371): son una funcionalidad aparte, sobre
  módulos que en su mayoría no existen, y un buscador que no busca es un control que miente.
- **Se conserva el primitivo `Sidebar` de shadcn.** Aporta persistencia por cookie, atajo `⌘B`,
  tooltips al colapsar y el `Sheet` de móvil. Basta cambiar `variant="inset"` → `"sidebar"` y las
  constantes de ancho para eliminar la tarjeta flotante.
- **El móvil se mantiene con el `Sheet`.** El prototipo no tiene responsive: cero `@media`, cero
  drawer, cero hamburguesa. Es una adición deliberada, no un desvío del diseño.
- **El control de colapso vuelve al sidebar**, donde el prototipo lo pone (l. 34 "Colapsar menú",
  l. 66 "Expandir menú"). De paso desaparece el `Toggle sidebar` en inglés del header.
- **El acceso a la cuenta queda duplicado a propósito:** el prototipo tiene la fila de perfil en el
  pie del sidebar (l. 70-83) **y** la píldora con menú en el header (l. 103-124).
- **Las medidas del shell se vuelven tokens CSS**, no literales repartidos por el TSX. Antes de
  esto, `clientes/form.tsx` y `maestros/list-rail.tsx` usaban dos offsets sticky distintos para el
  mismo header.
- **Sin tests nuevos.** La convención del repo exime a los cambios de estilo y layout.
  `tests/Feature/NavigationTest.php` prueba las props del servidor, que no se tocan.

## Context

- **Visuals:** `Prototipo Admin.dc.html` en Claude Design. El shell vive en las líneas 25-126 y sus
  medidas en `renderVals()`, líneas 3727-3757. Detalle en `visuals/README.md`.
- **References:** el propio código actual del shell. Detalle en `references.md`.
- **Product alignment:** N/A — no cambia el alcance del producto, solo la fidelidad de su chasis.

## Standards Applied

- `javascript/react-inertia` — se tocan componentes de layout de React e Inertia, el reparto
  container/presentational y los primitivos de `components/ui/`.
- `global/conventions` — copy de UI en español, código y comentarios en inglés, commits
  convencionales.

No aplican los estándares de backend (`authorization`, `catalog-master-data`,
`document-numbering`, `money`, `settings-parameters`, `taxes-colombia`), `database/postgres` ni
`testing/testing`: este trabajo no toca PHP, ni el esquema, ni añade comportamiento que probar.
