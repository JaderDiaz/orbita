# Visuales

El diseño vive en Claude Design, no como archivos en este repo.

**Proyecto:** `4ba0fa0d-992f-4439-a02a-311350030ea9` — "Orbita - Administrador de Proyectos"
https://claude.ai/design/p/4ba0fa0d-992f-4439-a02a-311350030ea9

El módulo de Maestros está en `Prototipo Admin.dc.html` (5799 líneas), repartido en dos mitades del
archivo: el markup con estilos en línea, y un bloque `<script type="text/x-dc">` al final con la
semilla y la lógica. **Un port fiel necesita leer ambas mitades.**

| Región | Líneas |
| --- | --- |
| Markup `<sc-if value="{{ isMaestros }}">` | 1423-1552 |
| `msAct(k)` | 3382 |
| `get listas()` | 3383 |
| `msPal` — la paleta de 8 pares | 3384 |
| `msIcons` — 16 iconos SVG en línea | 3385-3394 |
| **`msSeed()` — la semilla completa** | **3395-3418** |
| **`maestrosVals(pick)` — todo el comportamiento** | **3419-3500** |
| Nav: `SUBIC.maestros`, `subItem()`, `configScreens` | 3908-3914 |
| `navGrupos` — Administración → Configuración → maestros | 3916-3931 |
| `titles` — breadcrumbs `["Configuración", "Maestros y listas"]` | 3938-3952 |

Los valores extraídos están en la sección "El catálogo semilla" de `plan.md`.
