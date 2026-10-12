# Visuales

El diseño vive en Claude Design, no como archivos en este repo.

**Proyecto:** `4ba0fa0d-992f-4439-a02a-311350030ea9` — "Orbita - Administrador de Proyectos"
https://claude.ai/design/p/4ba0fa0d-992f-4439-a02a-311350030ea9

El módulo de Clientes está en `Prototipo Admin.dc.html` (5799 líneas), repartido en tres bloques de
markup no contiguos más su lógica en el bloque `<script type="text/x-dc">` del final.

**Verificado al planear este spec:** `etag` `1791839678113816`, 775 640 bytes — idénticos a los de
la extracción previa, así que el diseño no ha cambiado.

| Región | Líneas |
| --- | --- |
| Listado `<sc-if value="{{ isClientes }}">` | 1166-1235 |
| Detalle `<sc-if value="{{ isCliente }}">` | 1547-1796 |
| ├─ modales de borrado | 1585-1609 |
| ├─ tarjetas KPI (fuera de alcance) | 1613-1627 |
| ├─ pestañas (fuera de alcance) | 1631-1744 |
| └─ riel derecho: ficha fiscal, contactos, notas | 1748-1793 |
| Formulario `<sc-if value="{{ isClienteNuevo }}">` | 1798-1937 |
| Datos semilla `const clientes = [...]` | 4613-4632 |
| Lógica del listado (`cliFiltros`, `cliVisibles`, `estBadge`) | 5105-5112 |
| Lógica del detalle (`cv0`, `cvKpis`, `cvInfo`, `cvTabsDef`) | 5114-5153 |
| `cvVals` — ficha, contactos, menú, borrado | 5159-5221 |
| Lógica del formulario (`cfEstados`, `cfContactos`, `cfFaltan`, `cfChecks`) | 5225-5260 |
| Bindings del formulario y `guardarCliente` | 5577-5632 |
| **`genTag()` — el generador del tag** | **3707-3715** |
| Paginación genérica | 3735-3757 |

El copy literal y los defectos del prototipo están en `plan.md` y en `references.md`.
