# Referencias para Login y Layout

## El starter kit oficial de Laravel React

Es la implementación de referencia principal: ya está instalado en este repo y es lo que se adapta.
Stack confirmado: `laravel/framework ^13.17`, `laravel/fortify ^1.37.2`, `@inertiajs/react ^3.0.0`,
`react ^19.2.0`, `tailwindcss ^4.0.0`.

### Resolución de layouts

- **Ubicación:** `resources/js/app.tsx:13-24`
- **Relevancia:** los layouts se eligen centralmente por nombre de página, no por página.
  `welcome` → `null`; `auth/*` → `AuthLayout`; `settings/*` → `[AppLayout, SettingsLayout]`
  (el arreglo significa anidado); el resto → `AppLayout`.
- **Qué reutilizar:** el mecanismo tal cual. No hay que tocarlo para este spec.

### El mecanismo `Page.layout`

- **Ubicación:** `node_modules/@inertiajs/react/dist/index.js:169-183`; usos en
  `resources/js/pages/auth/login.tsx:91`, `pages/dashboard.tsx:29`, `pages/settings/security.tsx:133`
- **Relevancia:** cuando `Page.layout` es un **objeto plano**, Inertia 3 lo trata como _props del
  layout_, no como componente de layout. Las claves aceptadas son simplemente las que acepte el
  layout resuelto: `title` y `description` para `AuthLayout`, `breadcrumbs` para `AppLayout`.
- **Orden de precedencia:** props de página → props estáticas del layout → `dynamicLayoutProps`.
- **Variante dinámica:** `setLayoutProps` se usa en exactamente un lugar,
  `resources/js/pages/auth/two-factor-challenge.tsx:41-44`, para alternar título y descripción en
  tiempo de ejecución.

### Navegación — está duplicada en tres lugares

- `resources/js/components/app-sidebar.tsx:19-38` — `mainNavItems` (un solo ítem, "Dashboard") y
  `footerNavItems` (enlaces externos al repo y a la documentación)
- `resources/js/components/app-header.tsx:42-61` — copia paralela, **código muerto**: no es
  alcanzable desde `app.tsx`
- `resources/js/layouts/settings/layout.tsx:13-29` — sub-nav de ajustes: Profile, Security,
  Appearance

**Qué tomar de aquí:** el tipo `NavItem` y el patrón de ítem activo. **Qué cambiar:** consolidar en
`resources/js/config/navigation.ts` como fuente única.

### Tipo de ítem de navegación

- **Ubicación:** `resources/js/types/navigation.ts:9-14`

```ts
export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};
```

- **Relevancia:** `href` acepta objetos de ruta de Wayfinder, no solo strings. Hay que extenderlo
  con `disabled` y envolverlo en grupos.

### Breadcrumbs

- **Ubicación:** `resources/js/components/breadcrumbs.tsx:13-49`; tipo en
  `resources/js/types/navigation.ts:4-7`
- **Cadena:** `Page.layout.breadcrumbs` → `AppLayout` → `AppSidebarLayout` → `AppSidebarHeader` →
  `Breadcrumbs`
- **Qué reutilizar:** todo. Funciona y el prototipo usa breadcrumbs igual.

### Props compartidas de Inertia

- **Ubicación:** `app/Http/Middleware/HandleInertiaRequests.php:36-46`
- **Hoy comparte:** `name` (de `config('app.name')`), `auth.user`, `sidebarOpen` (desde la cookie
  `sidebar_state`), más el spread de `parent::share()`
- **Tipado:** `resources/js/types/global.d.ts:9-17` aumenta
  `InertiaConfig['sharedPageProps']`, y eso es lo que hace que `usePage().props.auth` quede tipado
  sin genéricos. **Cualquier prop nueva hay que declararla ahí** o el tipo queda `unknown`.

### Flash y toasts

- **Ubicación:** `Inertia::flash('toast', [...])` en el servidor (ejemplo en
  `app/Http/Controllers/Settings/SecurityController.php:51`), consumido por
  `resources/js/hooks/use-flash-toast.ts` vía `router.on('flash')`, montado en
  `resources/js/components/ui/sonner.tsx:8`
- **Qué reutilizar:** todo. El prototipo usa toasts y esto ya está resuelto.

### Shell y sidebar

- `resources/js/components/app-shell.tsx` — `SidebarProvider` con
  `defaultOpen={usePage().props.sidebarOpen}`. **Reutilizar:** la persistencia del estado colapsado
  por cookie ya hace lo que el prototipo hace con `localStorage`.
- `resources/js/components/app-content.tsx` — `SidebarInset` en variante sidebar
- `resources/js/components/app-sidebar-header.tsx` — `header h-16` + `SidebarTrigger` +
  `Breadcrumbs`. El prototipo usa 56px, no 64px.
- `resources/js/components/nav-user.tsx` + `user-menu-content.tsx` — menú de cuenta con
  Settings → `profile.edit()` y Log out → `logout()`. **Reutilizar:** cubre lo que el prototipo
  pide (mi perfil, cerrar sesión).

### Código muerto a eliminar

Ninguno es alcanzable desde `resources/js/app.tsx:13-24`:

- `resources/js/components/app-header.tsx`
- `resources/js/layouts/app/app-header-layout.tsx`
- `resources/js/layouts/auth/auth-card-layout.tsx`
- `resources/js/layouts/auth/auth-split-layout.tsx`

### Tema y modo oscuro

- `resources/css/app.css` — Tailwind 4: `@import 'tailwindcss'`, bloque `@theme` (líneas 10-62) que
  mapea utilidades a variables, `:root` (64-98) con los valores, `.dark` (100-133).
  `@custom-variant dark (&:is(.dark *))` en la línea 8: el modo oscuro es por clase en `<html>`.
- **Radio actual:** `--radius: 0.625rem`. **Fuente actual:** `--font-sans: 'Instrument Sans'`,
  cargada vía `bunny()` en `vite.config.ts`.
- **La paleta actual es completamente acromática** (chroma 0 en oklch) salvo `destructive` y los
  colores de chart: no hay ningún hue de marca definido todavía.
- `resources/js/hooks/use-appearance.tsx` — persiste en `localStorage['appearance']` y en una cookie
  `appearance`, aplica `classList.toggle('dark')`, y se inicializa en `resources/js/app.tsx:40`.
  La ruta `routes/settings.php:26` es solo vista, sin controlador ni persistencia en servidor.

### Página de login actual

- **Ubicación:** `resources/js/pages/auth/login.tsx`
- **Estado:** el copy ya está en español y el formulario es correcto — `Form` de Inertia 3 con
  `{...store.form()}`, `resetOnSuccess={['password']}`, render-prop `({processing, errors})`, y los
  tres grupos de campo. Ya no quedan enlaces de registro ni de recuperación.
- **Falta:** solo la piel. Props: `{ status?: string }`, desde
  `app/Providers/FortifyServiceProvider.php:37-39`.
- **Shell visual:** `resources/js/layouts/auth/auth-simple-layout.tsx` — columna centrada
  `max-w-sm`, logo `h-9 w-9 rounded-md`, `h1.text-xl.font-medium`. El prototipo usa logo de 48px con
  gradiente, `h1` de 26px/700 y una card con sombra propia.

## Tokens extraídos del prototipo

La tabla completa está en la sección "Tokens extraídos del prototipo" de `plan.md`. Resumen de lo
que no se debe perder:

| Token                                | Valor                                                                                                             |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------- |
| Sombra canónica de card              | `0 1px 2px rgba(0,0,0,.04), 0 8px 24px -6px rgba(0,0,0,.06)`                                                      |
| Anillo de foco (único, todo control) | `border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.16)`                                                 |
| Botón primario                       | `#f97316` / `#fff`, `999px`, `0 1px 2px rgba(249,115,22,.3), inset 0 1px 0 rgba(255,255,255,.2)`, hover `#ea580c` |
| Radio de card                        | `16px` (no 18px)                                                                                                  |
| Radio de input                       | `10px`, borde `rgba(0,0,0,.12)`, hover `rgba(0,0,0,.24)`                                                          |
| Sidebar                              | `244px` / `68px`, `rgba(250,250,252,.85)` + `blur(24px) saturate(180%)`                                           |
| Header                               | `56px`, `rgba(245,245,247,.72)` + `blur(20px)`                                                                    |
| Stack de fuente                      | `-apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Geist', system-ui, sans-serif`                                |

**Dónde viven los tokens en el prototipo:** no solo en los `style` inline. El bloque de script los
tiene como constantes — `const ac = "#f97316"` (línea 3844), el mapa `msPal` de badges (3356), los
hues `TILE` del nav (3869), y los helpers `pick()`/`chip()` de estado (3917-3922). Quien vuelva a
consultar el prototipo necesita leer ambas mitades del archivo.
