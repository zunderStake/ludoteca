# Handoff: Ludoteca — gestor de colección de juegos de mesa

## Overview
Ludoteca es una aplicación personal para dar de alta los juegos de mesa que uno tiene, con su precio
aproximado, y llevar el registro de partidas, jugadores, lista de deseos y préstamos. Referencia
funcional de partida: BoardGameTracker (https://github.com/mregni/BoardGameTracker).

El prototipo cubre cinco vistas en una sola página con navegación por pestañas, tres diálogos modales
(alta de juego, registro de partida, ficha de detalle) y un hueco de carátula por juego que el usuario
rellena arrastrando una imagen.

## About the Design Files
Los archivos de este paquete son **referencias de diseño hechas en HTML**: prototipos que muestran el
aspecto y el comportamiento previstos, no código de producción para copiar tal cual. La tarea es
**recrear estos diseños en el entorno del codebase de destino** (React, Vue, SwiftUI, nativo…) usando
sus patrones y librerías establecidos. Si aún no hay entorno, elegir el framework más adecuado para el
proyecto e implementar allí los diseños.

`Ludoteca.dc.html` es un componente de diseño: un único archivo con plantilla + clase de lógica que
se abre directamente en el navegador. Su estructura sirve como referencia de marcado y de estado, no
como arquitectura a replicar.

## Fidelity
**Alta fidelidad (hifi).** Colores, tipografía, espaciado, estados e interacciones son definitivos.
La UI debe recrearse con fidelidad usando las librerías y patrones existentes del codebase.

## Design system
El diseño se apoya en el sistema **Nocturne** (hoja de estilos `_ds/nocturne-…/styles.css`,
incluida en el paquete) con una capa de tokens sobrescritos en línea para la estética industrial
descrita más abajo. Las clases usadas del sistema son `.nav`, `.nav-brand`, `.card`,
`.card-title`, `.card-meta`, `.btn` (+ `.btn-primary`, `.btn-secondary`, `.btn-ghost`,
`.btn-icon`), `.tag` (+ `.tag-outline`, `.tag-accent`, `.tag-neutral`), `.field`,
`.input`, `.table`, `.dialog-backdrop`, `.dialog`, `.dialog-title`, `.dialog-actions`,
`.text-muted`.

Si el codebase de destino ya tiene su propio sistema de diseño, priorizarlo y trasladar solo la
intención visual (fondo grafito, acento ámbar, esquinas de 2 px, numerales monoespaciados,
microetiquetas en mayúsculas, bordes de 1 px en lugar de sombras).

## Design Tokens

### Color (valores efectivos, sobrescritos sobre Nocturne)
| Token | Valor | Uso |
| --- | --- | --- |
| `--color-bg` | #17191b | Fondo de página |
| `--color-surface` | #1f2225 | Superficie de tarjetas, inputs, diálogos |
| `--color-text` | #dcdedd | Texto base |
| `--color-accent` | #d08a2c | Acento ámbar: bordes de acción, barras, línea superior de la cabecera |
| `--color-accent-2` | #8f9ba0 | Rol secundario (acero) |
| `--color-divider` | color-mix(in srgb, #dcdedd 14%, transparent) | Reglas y bordes de input |

Rampa de acento (ámbar): 100 #fbf3e8 · 200 #f5e3c9 · 300 #ecc999 · 400 #dfab63 · 500 #d08a2c ·
600 #ac7124 · 700 #85571c · 800 #5d3d15 · 900 #38260e

Rampa neutra (grafito): 100 #f4f5f5 · 200 #e4e6e6 · 300 #cbcfd0 · 400 #adb2b4 · 500 #8d9295 ·
600 #6f7478 · 700 #55595d · 800 #3c4043 · 900 #272a2c

Rampa acento-2 (acero): 100 #f3f5f5 · 200 #e2e6e7 · 300 #c8ced0 · 400 #a9b1b5 · 500 #8f9ba0 ·
600 #737d81 · 700 #586064 · 800 #3e4447 · 900 #282c2e

### Tipografía
- Titulares y nombres de juego: **Archivo Narrow** (500/600/700), mayúsculas, letter-spacing 0.02–0.14em.
- Cuerpo: **Inter** (heredado de Nocturne, `--font-body`), 14 px.
- Datos, cifras, microetiquetas: **IBM Plex Mono** (400/500), 9–28 px, letter-spacing 0.06–0.16em,
  mayúsculas en las etiquetas.
- Tamaños concretos: cifra de KPI 28 px / línea 1.05; título de tarjeta 15 px; puntuación 17 px;
  etiqueta de KPI 10 px; microetiqueta de tarjeta 10 px; etiqueta de dato en la ficha 9 px.

### Espaciado, radios y elevación
- Escala de Nocturne a densidad 0.70×; los valores literales usados son 2.8 / 5.6 / 8.4 / 11.2 / 14 /
  16.8 / 22.4 / 33.6 px.
- Radios: `--radius-sm` 1 px, `--radius-md` 2 px, `--radius-lg` 2 px (esquinas casi rectas).
- Elevación: **borde de 1 px** (`--color-neutral-800`) en lugar de sombra. Las tarjetas llevan
  `box-shadow: none`. Sombras disponibles si hacen falta:
  sm `0 0 0 1px #3c4043`; md `0 0 0 1px #55595d, 0 4px 14px rgba(0,0,0,0.5)`;
  lg `0 0 0 1px #8d9295, 0 14px 36px rgba(0,0,0,0.6)`.
- Fondo de página: retícula de plano técnico — dos gradientes lineales de 1 px al 4% de opacidad del
  color de texto, paso de 56×56 px.
- Ancho de contenido: 1180 px máximo, padding lateral 22.4 px. Padding inferior de página 56 px.

## Screens / Views

### 1. Cabecera (persistente)
- `.nav` sticky arriba (`top: 0`, `z-index: 5`), fondo `color-mix(in srgb, var(--color-bg) 94%, transparent)`
  con `backdrop-filter: blur(8px)`, **borde superior de 2 px en el acento** y borde inferior de 1 px
  `--color-divider`. Padding 11.2 px / 22.4 px, flex con `gap: 16.8px`, `flex-wrap: wrap`.
- Marca: "LUDOTECA" en Archivo Narrow 700, mayúsculas, letter-spacing 0.14em; a su derecha
  "INVENTARIO · 001" en IBM Plex Mono 10 px, letter-spacing 0.16em, color `--color-neutral-500`.
- Pestañas: cinco botones `.btn` sin borde, Mono 11 px mayúsculas, opacidad 0.55, y a 1 en hover con
  fondo `color-mix(in srgb, var(--color-text) 7%, transparent)`. Cada una lleva su contador a la
  derecha (Mono 10 px, opacidad 0.6). Etiquetas: Colección, Partidas, Jugadores, Deseos, Préstamos.
  La pestaña activa marca `aria-current="page"`.
- Acción principal a la derecha: `.btn.btn-primary` "Añadir juego" (contorno ámbar, nunca relleno).

### 2. Colección (vista inicial)
- **Banda de KPIs**: cuatro tarjetas en fila (`flex: 1 1 180px`, `gap: 11.2px`), padding 16.8 px,
  borde 1 px `--color-neutral-800` y **borde izquierdo de 2 px `--color-accent-700`**.
  Contenido por tarjeta: etiqueta Mono 10 px mayúsculas (`--color-neutral-500`), cifra Mono 28 px,
  pie de 11 px al 50% de opacidad del texto.
  Los cuatro KPIs son: Juegos (nº de juegos / "N en la lista de deseos"), Valor total (suma de precios
  pagados / "Precio pagado, sin envíos"), Partidas (nº registradas / "Registradas este año"),
  Precio medio (total ÷ nº de juegos / "Por juego de la colección").
- **Barra de filtros**: campo de búsqueda `.input` `type="search"` (`flex: 1 1 240px`,
  placeholder "Nombre o editorial"), select de Tipo (min-width 150 px, primera opción "Todos") y select
  de Orden (Nombre, Puntuación, Precio, Más jugados). Etiquetas `.field > label` de 12 px.
- **Cuadrícula de juegos**: `grid-template-columns: repeat(auto-fill, minmax(250px, 1fr))`,
  `gap: 11.2px`. Cada tarjeta (`role="button"`, `tabindex="0"`, cursor pointer, padding 0,
  borde 1 px `--color-neutral-800`, sin sombra; en hover el borde pasa a `--color-accent-700`):
  1. **Carátula**: bloque de 170 px de alto a todo el ancho, fondo `--color-neutral-900`, borde
     inferior de 1 px; contiene el hueco de imagen rellenable por el usuario (ver Assets).
  2. **Cabecera**: nombre del juego (título 15 px, mayúsculas, `text-wrap: pretty`) y editorial
     (Mono 10 px mayúsculas, `--color-neutral-500`); a la derecha la puntuación en Mono 17 px
     `--color-accent-300` sobre "/10" en Mono 9 px al 45% de opacidad. Padding lateral 14 px.
  3. **Etiquetas**: tres `.tag.tag-outline` en Mono 10 px mayúsculas — tipo (borde
     `--color-accent-700`, texto `--color-accent-300`), nº de jugadores y duración (borde
     `--color-neutral-700`, texto `--color-neutral-400`).
  4. **Pie**: regla superior de 1 px y dos datos enfrentados en Mono 10 px mayúsculas — nº de partidas
     y precio pagado. Padding `8.4px 14px 14px`.
- **Vacío**: si el filtro no devuelve nada, "Ningún juego coincide con la búsqueda." centrado,
  `.text-muted`, padding vertical 33.6 px.

### 3. Partidas
- Encabezado: h3 "PARTIDAS" (mayúsculas, letter-spacing 0.12em) y `.btn.btn-primary`
  "Registrar partida" a la derecha.
- `.table` con columnas Fecha, Juego, Jugadores, Ganador, Duración (última alineada a la derecha).
  Fecha en Mono 12 px `--color-neutral-500` formateada `es-ES` como "16 ago 2026"; juego en
  Archivo Narrow mayúsculas; jugadores en texto al 70%; ganador como `.tag.tag-outline` ámbar;
  duración en Mono 12 px ("135 min"). Orden: fecha descendente.

### 4. Jugadores
- h3 "JUGADORES". Cuadrícula `repeat(auto-fill, minmax(240px, 1fr))`, `gap: 11.2px`.
- Cada tarjeta (borde 1 px, sin sombra): avatar cuadrado de 36 px (inicial en Mono 14 px, borde 1 px
  `--color-neutral-700`, fondo `--color-neutral-900`), nombre en mayúsculas, "N PARTIDAS" en Mono
  10 px; debajo, fila "VICTORIAS / n de m" en Mono 11 px y una **barra de progreso de 6 px** con
  fondo `--color-neutral-900`, borde 1 px `--color-neutral-800` y relleno de rayas diagonales
  `repeating-linear-gradient(45deg, var(--color-accent) 0 4px, var(--color-accent-700) 4px 8px)`
  al porcentaje de victorias; pie "FAVORITO: <juego más jugado>".

### 5. Lista de deseos
- Encabezado h3 "LISTA DE DESEOS" y, a la derecha, el importe total pendiente en Mono 11 px mayúsculas.
- Filas apiladas (`gap: 8.4px`), cada una una tarjeta horizontal (borde 1 px, `flex-wrap: wrap`,
  `gap: 16.8px`): nombre en mayúsculas + editorial en Mono 10 px; `.tag.tag-outline` con la
  prioridad (Alta / Media / Baja); precio en Mono 15 px alineado a la derecha (min-width 74 px);
  botón `.btn.btn-secondary` "Ya lo tengo", que mueve el título a la colección y salta a esa vista.

### 6. Préstamos
- h3 "PRÉSTAMOS" y filas horizontales con la misma anatomía: juego en mayúsculas, "EN CASA DE <persona>"
  en Mono 10 px, etiqueta con los días fuera (`.tag-accent` si supera 30 días, `.tag-neutral` si no)
  y botón `.btn.btn-secondary` "Devuelto".
- Vacío: "Ahora mismo no has prestado nada." en `.text-muted`.

### 7. Diálogo "Añadir juego"
- `.dialog-backdrop` + `.dialog` de `min(520px, 100%)`. Título en mayúsculas, letter-spacing 0.1em.
- Rejilla de dos columnas (`gap: 11.2px`): Nombre (ocupa las dos columnas, placeholder
  "Ej. Brass: Birmingham"), Editorial, Tipo (select), Puntuación (0–10, `step="0.1"`), Precio pagado
  (number), Jugadores (texto, placeholder "2-4"), Duración (texto, placeholder "60 min").
- Validación: solo el nombre es obligatorio. Si falta, mensaje "Pon al menos un nombre." en 12 px
  `--color-accent-300` sobre las acciones; se borra al volver a escribir.
- Acciones: "Cancelar" (`.btn-secondary`) y "Guardar" (`.btn-primary`). Al guardar se aplican
  valores por defecto: editorial "Sin editorial", jugadores "2-4", duración "60 min", puntuación 0.

### 8. Diálogo "Registrar partida"
- Select de juego (ordenado alfabéticamente), selector de jugadores como **chips conmutables**
  (`.btn` de 13 px, padding 5.6/11.2 px; seleccionado = borde y texto en el acento, sin seleccionar =
  borde `--color-divider`), select de ganador limitado a los jugadores seleccionados, y duración en
  minutos (number, `step="5"`).
- Al guardar: fecha = hoy, ganador = el elegido o el primer jugador seleccionado, duración por defecto
  60; la vista salta a Partidas. Si no hay ningún jugador seleccionado no se guarda.

### 9. Ficha de detalle
- `.dialog` de `min(480px, 100%)`. Cabecera: hueco de carátula de 96×130 px (borde 1 px
  `--color-neutral-700`, placeholder "Carátula"), nombre en mayúsculas, editorial en Mono 10 px y
  botón `.btn.btn-icon.btn-secondary` "✕" para cerrar.
- Rejilla de dos columnas con seis datos, cada uno con regla superior de 1 px, etiqueta Mono 9 px
  mayúsculas `--color-neutral-500` y valor Mono 15 px: Tipo, Puntuación ("9.2 / 10"), Jugadores,
  Duración, Precio, Partidas.
- Acciones enfrentadas: "Quitar de la colección" (`.btn-ghost`) y "Registrar partida"
  (`.btn-primary`, abre el diálogo 8 con el juego preseleccionado).

## Interactions & Behavior
- Navegación por pestañas sin recarga; cada sección entra con `animation: riseIn 0.25s ease both`
  (`opacity 0 → 1`, `translateY(6px) → none`).
- Clic en cualquier tarjeta de juego abre la ficha de detalle. Los diálogos se cierran con Cancelar o ✕.
- Búsqueda: filtra por nombre **o** editorial, sin distinguir mayúsculas, al teclear.
- Filtro de tipo y orden se combinan con la búsqueda. Orden por nombre usa `localeCompare` en `es`;
  puntuación, precio y "más jugados" ordenan descendente.
- Hover de tarjeta: el borde pasa al acento (`--color-accent-700`). Hover de pestaña: opacidad plena
  y fondo tenue. Botones: contorno ámbar con relleno al 12% en hover y 22% en activo.
- Foco de teclado: `:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px }`
  (viene del sistema; no sustituir por el anillo azul del navegador).
- Responsive: todas las filas usan `flex-wrap: wrap` y las cuadrículas `auto-fill`, así que la
  página colapsa a una columna en móvil sin reglas extra. La cabecera envuelve sus pestañas.
- No hay estados de carga ni de error: el prototipo trabaja con datos en memoria. En la implementación
  real hacen falta estado de carga en la colección y manejo de error del alta.

## State Management
Estado del prototipo (todo en memoria, sin persistencia):
- `view`: 'coleccion' | 'partidas' | 'jugadores' | 'deseos' | 'prestamos'.
- `games[]`: { id, nombre, editorial, tipo, puntuacion, precio, jugadores, duracion }.
- `plays[]`: { id, gameId, fecha (ISO), jugadores[], ganador, duracion (min) }.
- `wishlist[]`: { id, nombre, editorial, precio, prioridad }.
- `loans[]`: { id, gameId, persona, dias }.
- `query`, `tipoFilter`, `sort`: filtros de la colección.
- `dialog`: null | 'add' | 'play'; `detailId`: id del juego abierto en la ficha; `formError`.
- `form` y `playForm`: borradores de los dos formularios.

Derivados (calculados, no almacenados): lista visible tras filtrar y ordenar, KPIs (total, media),
lista de jugadores (deducida de las partidas), victorias y juego favorito por jugador, nº de partidas
por juego, total de la lista de deseos.

Transiciones relevantes: guardar juego → añade a `games`, cierra diálogo, limpia búsqueda y formulario;
guardar partida → añade a `plays` y salta a Partidas; "Ya lo tengo" → mueve de `wishlist` a `games`
y salta a Colección; "Devuelto" → elimina de `loans`; "Quitar de la colección" → elimina de `games`
y cierra la ficha.

Datos que el backend real necesitará servir: colección, partidas, jugadores, deseos y préstamos por
usuario, más las agregaciones de gasto y victorias.

## Tipos de juego
El selector de tipo usa la taxonomía habitual del hobby, no etiquetas inventadas:
Eurogame · Ameritrash / temático · Abstracto · Familiar · Filler · Party · Deducción social ·
Legacy / campaña · Construcción de mazos · Wargame · Cooperativo.

Referencias consultadas: menteludic.com/blog/clasificacion-de-juegos-de-mesa,
zacatrus.es/blog/juegos-mesa-categorias.html, arcana-artesania.es/clasificacion-de-los-juegos.

## Assets
- **Carátulas**: el prototipo no incluye imágenes. Cada juego tiene un hueco rellenable
  (`image-slot.js`, incluido) donde el usuario arrastra la portada; la imagen se guarda en un fichero
  lateral del prototipo. El id del hueco es `cover-<idDelJuego>`, compartido entre la tarjeta de la
  colección y la ficha de detalle, de modo que una sola imagen sirve para ambas.
- **En producción**: BoardGameGeek expone su XMLAPI2
  (`https://boardgamegeek.com/xmlapi2/search?query=<nombre>&type=boardgame` y
  `/xmlapi2/thing?id=<id>`), que devuelve la URL de la carátula. **No admite CORS**, así que la
  llamada tiene que hacerse desde el servidor: buscar por nombre, guardar el `objectid` y la URL de
  imagen junto al juego, y servirla desde el propio backend o una caché de imágenes.
- **Iconos**: Nocturne especifica Phosphor Icons (https://phosphoricons.com). El prototipo solo usa el
  carácter "✕" para cerrar; sustituirlo por el icono correspondiente.
- **Tipografías**: Archivo Narrow e IBM Plex Mono desde Google Fonts; Inter desde el sistema de diseño.
- Datos de ejemplo (14 juegos, 7 partidas, 3 deseos, 2 préstamos) son ficticios y solo sirven para
  poblar las vistas.

## Files
- `Ludoteca.dc.html` — el prototipo completo: plantilla (marcado y estilos en línea) y clase de
  lógica con el estado y los datos de ejemplo.
- `image-slot.js` — el componente del hueco de carátula que usa el prototipo.
- `support.js` — runtime que permite abrir el prototipo en el navegador. No forma parte del diseño.
- `_ds/nocturne-cbfc4c59-94fa-5fb6-8809-f12b94c5087f/styles.css` — hoja de tokens y componentes del
  sistema Nocturne sobre la que se construye la interfaz.
- `_ds/nocturne-cbfc4c59-94fa-5fb6-8809-f12b94c5087f/readme.md` — guía del sistema de diseño.
