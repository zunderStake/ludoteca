# Ludoteca — cambio estético (temas + vista carátula)

Solo el cambio visual sobre el prototipo anterior. Abre `Ludoteca.dc.html` en el navegador (sirviendo la carpeta con cualquier servidor estático).

## 1. Sistema de temas

Cuatro paletas, seleccionables desde el selector de la barra superior (`state.theme`):

| id | fondo | tinta | acento | secundario |
|---|---|---|---|---|
| `crema` (por defecto) | `#f3ecdd` | `#262231` | morado `#6d5aa6` | dorado `#b8892f` |
| `pergamino` | `#ece2cd` | `#2b2418` | dorado `#a8792a` | morado `#6d5aa6` |
| `tinta` | `#1c1726` | `#ece7f5` | morado `#a08ce0` | dorado `#d1a94e` |
| `nocturno` | `#161826` | `#e9e9ed` | morado `#9184d9` | oro apagado `#c2b273` |

Cada tema define solo 5 colores. Las rampas 100–900 se derivan por interpolación en `themeVars()` / `ramp()`:

- **Convención de rampa**: `900` es el paso más cercano al fondo, `100` el más cercano a la tinta. Esto vale igual en temas claros y oscuros, así que el mismo token sirve en los cuatro: `--color-neutral-900` fondo de superficie, `-800` borde suave, `-500` texto secundario, `-200/300` texto fuerte; `--color-accent-700` borde de acento, `--color-accent-300` texto de acento legible.
- Los tres `--shadow-*` y `--color-divider` también se derivan del par fondo/tinta.
- El tema se aplica como variables CSS inline en un contenedor que envuelve toda la app. En producción conviene moverlo a `:root` con un atributo `data-theme` y guardar la elección del usuario.

## 2. Vista Colección: Carátula / Ficha

Conmutador en la barra de filtros (`state.mode`).

- **Carátula** (por defecto): cuadrícula de cuadrados (`aspect-ratio: 1`, mínimo 210px) solo con la imagen. Al pasar el ratón, la carátula escala a 1.06 y sube un panel con nombre, editorial, puntuación y cuatro datos (tipo, jugadores, duración, partidas). El hover se controla por estado (`state.hoverId`) con `onMouseEnter` / `onMouseLeave`, no por CSS, para que el mismo comportamiento pueda dispararse por foco de teclado o tap en móvil: pendiente decidir el equivalente táctil (primer tap muestra ficha, segundo abre detalle).
- **Ficha**: la cuadrícula anterior con carátula, tags y pie de datos.
- El fondo del panel usa `color-mix()` sobre `--color-surface`, por lo que se adapta al tema activo.

## 3. Carátulas

Las 14 imágenes de `covers/` son **marcadores generados por código** (canvas, geometría en crema/morado/dorado, 720×720), no arte real. Existen solo para poder maquetar con imágenes.

- Se cargan como `background-image` en el tile, no como `<img src>`, resueltas por `coverOf(id)`.
- Fallback para juegos nuevos: `id > 14` reutiliza `covers/(id % 14) + 1`.
- **En producción se sustituyen por las carátulas de BoardGameGeek** (XMLAPI2, `thing?id=<bggId>` → campo `image` / `thumbnail`). Requiere proxy en servidor: la API no envía cabeceras CORS. Recomendado: cachear la imagen en almacenamiento propio al dar de alta el juego, guardando `bgg_id` e `image_url` en la tabla de juegos.

## Archivos

- `Ludoteca.dc.html` — prototipo completo (plantilla + lógica).
- `support.js` — runtime del prototipo.
- `covers/1–14.png` — carátulas marcador.
- `_ds/nocturne-…/` — hoja de estilos y bundle del sistema Nocturne (base de componentes: `.btn`, `.card`, `.table`, `.dialog`, `.field`).
