# Visuales

El diseño vive en Claude Design, no como archivos en este repo.

**Proyecto:** `4ba0fa0d-992f-4439-a02a-311350030ea9` — "Orbita - Administrador de Proyectos"
https://claude.ai/design/p/4ba0fa0d-992f-4439-a02a-311350030ea9

| Archivo                    | Tamaño | Qué contiene                                                                          |
| -------------------------- | ------ | ------------------------------------------------------------------------------------- |
| `Prototipo Login.dc.html`  | 8 KB   | Pantalla de login. Fuente de la piel de `auth-simple-layout`                          |
| `Prototipo Admin.dc.html`  | 770 KB | Vista de administrador completa. Fuente de los tokens de diseño y del nav de 4 grupos |
| `Prototipo Equipo.dc.html` | 399 KB | Vista de miembro. Fuente del nav reducido y del alcance de datos por rol              |

Los tres son archivos `.dc.html`: plantilla `<x-dc>` con `style` inline, más un bloque
`<script type="text/x-dc">` al final con los datos semilla y la lógica.

**Dónde está el diseño en cada archivo:** la mitad de la plantilla lleva los estilos inline; el
bloque de script lleva los tokens como constantes de JavaScript (`const ac = "#f97316"`, el mapa
`msPal` de badges, los hues `TILE` del nav, los helpers `pick()`/`chip()` de estado). Un port fiel
necesita leer ambas mitades.

Los valores extraídos están en `references.md` y en la sección de tokens de `plan.md`.
