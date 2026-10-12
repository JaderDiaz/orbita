# Referencias para el shell fiel al diseño

La referencia principal es el **código actual del shell**: no se construye nada desde cero, se
corrigen medidas y posicionamiento sobre lo que ya existe.

## El primitivo de sidebar de shadcn

- **Ubicación:** `resources/js/components/ui/sidebar.tsx`
- **Relevancia:** es el origen de casi todas las desviaciones. Sus constantes de fábrica
  (`SIDEBAR_WIDTH = "16rem"` l. 28, `SIDEBAR_WIDTH_ICON = "3rem"` l. 30) nunca se sobrescribieron.
- **Qué reutilizar:** todo el mecanismo — contexto, persistencia en la cookie `sidebar_state`
  (l. 84), atajo `⌘B`, `Sheet` de móvil, tooltips al colapsar.
- **Qué cambiar:** solo las constantes de ancho y las variantes de clase del botón de menú
  (`sidebarMenuButtonVariants`, l. 470) y del sub-menú (l. 639).
- **Dato clave:** el `m-2 ml-0 rounded-xl shadow-sm` que hoy produce la tarjeta flotante está en
  `SidebarInset` (l. 305-306) **condicionado a `peer-data-[variant=inset]`**. Cambiar la variante en
  `app-sidebar.tsx` lo desactiva sin tocar el primitivo.

## La cadena de layout

- **Ubicación:** `resources/js/app.tsx:15-26` resuelve el layout por nombre de página; de ahí
  `layouts/app-layout.tsx` → `layouts/app/app-sidebar-layout.tsx` →
  `AppShell` + `AppSidebar` + `AppContent` + `AppSidebarHeader`.
- **Relevancia:** ninguna página importa un layout, así que el shell se corrige en un solo sitio y
  todas las pantallas lo heredan.
- **Qué reutilizar:** el mecanismo entero, incluida la cadena de breadcrumbs
  `Page.layout.breadcrumbs` → `AppLayout` → `AppSidebarLayout` → `AppSidebarHeader` → `Breadcrumbs`.

## Los tokens de diseño ya existentes

- **Ubicación:** `resources/css/app.css` — `@theme` (l. 27-113), `:root` (l. 115-179),
  `@utility shell-glow` (l. 202-214).
- **Relevancia:** la paleta ya es la correcta. Lo que falta es **consumirla**:
  - `--shadow-nav-active` (l. 111) está definido y **no lo usa nadie**. Es exactamente el estado
    activo del nav del prototipo (`0 1px 2px rgba(0,0,0,.06), 0 0 0 1px rgba(0,0,0,.04)`).
  - `shell-glow` (l. 202) solo lo consume el login (`auth-simple-layout.tsx:14`), pero el prototipo
    lo pone en el `<main>` del admin (l. 88).
- **Qué cambiar:** añadir los tokens de medida que faltan (`--sidebar-width`,
  `--sidebar-width-icon`, `--header-height`, `--content-offset`).

## El nav ya resuelto en el servidor

- **Ubicación:** `app/Support/Navigation/SidebarNavigation.php`, compartido como prop `navigation`
  en `app/Http/Middleware/HandleInertiaRequests.php:51-53`.
- **Relevancia:** la estructura de grupos e ítems ya coincide con la del prototipo y el rol ya se
  resuelve en el servidor. **No se toca PHP en este spec.**
- **Qué reutilizar:** todo. `nav-main.tsx` solo cambia métricas de CSS.

## El spec anterior de layout

- **Ubicación:** `agent-os/specs/2026-10-12-1538-login-y-layout/`
- **Relevancia:** extrajo los valores correctos (sidebar 244/68, header 56px, contenido 28/32/56) y
  los dejó escritos en `plan.md`, pero la implementación no los aplicó: se quedó con los de shadcn.
- **Lección que hereda este spec:** los valores en prosa no bastan. Este spec se construyó leyendo
  el markup literal del prototipo, igual que la reconstrucción del formulario de Clientes.
