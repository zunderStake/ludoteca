# 🎲 Ludoteca

**Tu colección de juegos de mesa, con vida propia.**

Colección, partidas, rankings, lista de deseos, préstamos, multiusuario con permisos
de verdad y un "Quiero jugar" con notificaciones — todo en una aplicación PHP + MySQL
ligera, sin build step, pensada para desplegarse en cualquier hosting compartido
(Hestia, por ejemplo) tan fácil como subir una carpeta por FTP.

Nació como el diseño de alta fidelidad de un grupo de amigos cansado de apuntar las
partidas en una hoja de cálculo. Se convirtió en una app con autenticación real por
persona, temas de color, vista responsive completa, carátulas traídas en vivo desde
BoardGameGeek, y hasta un carrusel tipo tragaperras para decidir qué se juega esta
noche cuando nadie se pone de acuerdo.

## Tabla de contenidos

- [✨ Características](#-características)
- [📋 Requisitos](#-requisitos)
- [🚀 Instalación](#-instalación)
- [🔄 Actualizar tras subir una nueva versión](#-actualizar-tras-subir-una-nueva-versión)
- [🖼️ Carátulas: siempre desde la API de BoardGameGeek](#-carátulas-siempre-desde-la-api-de-boardgamegeek)
- [🧱 Complementos 3D](#-complementos-3d)
- [🎰 Juego aleatorio](#-juego-aleatorio)
- [🧩 Expansiones](#-expansiones)
- [📂 Menú lateral](#-menú-lateral)
- [📱 Responsive (móvil y tablet)](#-responsive-móvil-y-tablet)
- [🎨 Temas y vista de la colección](#-temas-y-vista-de-la-colección)
- [👥 Usuarios y permisos](#-usuarios-y-permisos)
- [🙋 Quiero jugar](#-quiero-jugar)
- [💾 Copia de seguridad](#-copia-de-seguridad)
- [🗂️ Estructura](#-estructura)
- [🏷️ Versión y aviso de actualización](#-versión-y-aviso-de-actualización)
- [🐳 Probar en local con Docker](#-probar-en-local-con-docker)
- [🔒 Notas de seguridad para producción](#-notas-de-seguridad-para-producción)
- [📜 Historial de versiones](#-historial-de-versiones)

## ✨ Características

- 📚 **Colección** con puntuación, precio, jugadores, duración, edad recomendada y 14
  tipos de juego — carátula siempre traída en vivo desde BoardGameGeek, nunca subida
  ni guardada en el servidor.
- 🎰 **Juego aleatorio** — un carrusel gira entre tus juegos (de un tipo o de todos) y
  decide qué toca jugar hoy.
- 🧩 **Expansiones** enlazadas a su juego base en los dos sentidos.
- 🏆 **Ranking** y estadísticas de partidas, jugadores y rentabilidad.
- 💜 Lista de **deseos** y 📦 **préstamos**.
- 🙋 **"Quiero jugar"** — propón una partida a quien quieras; puede aceptarla (con
  aviso por email) o descartarla, y al registrar la partida la propuesta se resuelve
  sola.
- 👥 **Multiusuario real** — admin / coleccionista / jugador, cada uno con su propio
  correo y contraseña, permisos aplicados también en el servidor (no solo ocultando
  botones).
- 🎨 **4 temas de color** y dos vistas de colección (Carátula tipo kiosco / Ficha).
- 📱 **Responsive de verdad** — menú lateral colapsable en escritorio, cajón
  deslizante en móvil y tablet.
- 💾 **Copia de seguridad** con un clic — el propio número de versión es el botón.
- 🧱 **Sin build step** — PHP + MySQL puro, sube los ficheros y listo.

## 📋 Requisitos

- PHP 7.4 o superior con las extensiones `pdo_mysql` y `curl`.
- MySQL / MariaDB.
- Acceso de escritura a la carpeta del proyecto (para que `install.php` cree `config.php`).

## 🚀 Instalación

1. Sube toda esta carpeta al dominio/subdominio en Hestia (por ejemplo, vía SFTP o el
   gestor de archivos del panel).
2. En Hestia, crea una base de datos MySQL y su usuario (menú **BBDD**) si no lo has
   hecho ya.
3. Abre `https://tu-dominio/install.php` en el navegador.
4. Rellena el host, puerto, nombre, usuario y contraseña de la base de datos, y define
   la contraseña de la cuenta admin.
5. Al enviar el formulario, el instalador:
   - comprueba la conexión a MySQL,
   - crea la base de datos si no existía,
   - crea las tablas (`games`, `players`, `plays`, `play_players`, `wishlist`, `loans`,
     `app_users`...) con `CREATE TABLE IF NOT EXISTS`, así que se puede volver a
     ejecutar sin perder datos,
   - escribe `config.php` con la conexión y el hash de la contraseña de admin.
6. Entra en `login.php` con correo `admin` y la contraseña que acabas de definir.

Para reconfigurar la conexión a MySQL, la contraseña de admin o el token de BGG más
adelante, vuelve a `install.php`: si ya existe `config.php` te pedirá la contraseña
actual antes de permitir sobrescribirlo. Al reinstalar, el formulario ya sale relleno
con el host/puerto/nombre/usuario de la base de datos que tenías, y la contraseña de la
base de datos, la contraseña de admin y el token de BGG se pueden dejar en blanco para
mantener los que ya había — así, por ejemplo, para pegar solo un token de BGG nuevo no
hace falta volver a escribir el resto.

## 🔄 Actualizar tras subir una nueva versión

Cuando subas ficheros nuevos de Ludoteca (por ejemplo, tras una actualización que añada
columnas o tablas), no hace falta volver a pasar por `install.php` ni reintroducir la
contraseña de MySQL: entra en `https://tu-dominio/update.php` (pide el login normal de
la app) y pulsa "Actualizar ahora". Reutiliza la conexión de `config.php`, vuelve a
ejecutar los `CREATE TABLE IF NOT EXISTS` (no tocan las tablas existentes) y aplica las
migraciones de columnas nuevas definidas en `includes/schema.php`
(`ludoteca_run_migrations`), sin borrar ni modificar los datos que ya tienes. Es el mismo
mecanismo que usa `install.php` al crear la base de datos por primera vez, así que
instalación y actualización comparten una única fuente de verdad del esquema.

## 🖼️ Carátulas: siempre desde la API de BoardGameGeek

Ludoteca no almacena ni sube ficheros de imagen. Al añadir o editar un juego (y también
al añadir un deseo a la lista), el diálogo ofrece un buscador que llama a la API de
BoardGameGeek (XMLAPI2) **desde el servidor** (`api/bgg_search.php` y `api/bgg_thing.php`,
ver [`includes/bgg.php`](includes/bgg.php)), porque esa API no admite CORS desde el
navegador. Al elegir un resultado se copian nombre, editorial, nº de jugadores, duración
y, sobre todo, la **URL** de la carátula que devuelve BGG — el `<img>` la carga
directamente desde el CDN de BGG, nunca se descarga ni se guarda una copia en el servidor.

El nombre solo se rellena si el campo está vacío: si ya habías escrito uno (o estás
editando un juego que ya tiene nombre), se respeta y no se sobrescribe. Es así a
propósito — BGG solo expone un nombre "primary" (normalmente en inglés) y nombres
alternativos sin ninguna etiqueta de idioma, así que no hay forma fiable de pedirle el
nombre en español; el resto de datos (editorial, jugadores, duración, edad, puntuación
e imagen) sí se aplican siempre que BGG los tenga.

El buscador pide hasta 50 resultados (antes se cortaba en 12, y por eso algunos juegos
que sí existían en BGG no aparecían) y busca tanto en juegos base como en expansiones
(`boardgame,boardgameexpansion` — antes solo pedía `boardgame`, así que ninguna
expansión salía nunca); cada resultado que sea una expansión lo indica junto al nombre.

**Importante:** desde octubre de 2025 BoardGameGeek exige que cualquier aplicación esté
registrada y envíe un token en cada petición (`Authorization: Bearer <token>`); sin él,
la API devuelve 401 aunque el juego exista. Para que el buscador funcione:

1. Inicia sesión en tu cuenta de BGG y regístrala en
   [boardgamegeek.com/applications/create](https://boardgamegeek.com/applications/create).
2. Pega el token que te den en `install.php` (campo "Token de API de BoardGameGeek"; si ya
   instalaste la app, vuelve a `install.php`, marca "Sobrescribir configuración existente"
   y pon el token ahí).

Sin token, el resto de la aplicación funciona con normalidad: solo falla la búsqueda en
BGG, con un mensaje explicando qué falta — no un fallo silencioso.

## 🧱 Complementos 3D

En la ficha de detalle de cada juego hay un enlace "Complementos 3D" que abre en una
pestaña nueva la búsqueda de ese nombre en [MakerWorld](https://makerworld.com/es)
(`makerworld.com/es/search/models?keyword=<nombre>`) — organizadores, insertos y bandejas
imprimibles en 3D que la comunidad haya subido para ese juego. Es solo un enlace de
búsqueda (MakerWorld no tiene API pública), así que no depende de ninguna cuenta ni token.

## 🎰 Juego aleatorio

El botón "🎲 Juego aleatorio" de la pestaña Colección pregunta de qué tipo lo quieres
(incluye "Todos") y hace girar un carrusel con carátulas de la colección — empieza
rápido y frena hasta detenerse justo en el resultado, marcado con un recuadro en el
centro. El sorteo elige el juego al azar entre los que coincidan con el tipo elegido
antes de empezar a girar (la animación no decide nada: solo "cuenta" hasta llegar ahí),
así que siempre acaba en un juego que existe en tu colección. Desde el resultado puedes
"Ver ficha" (abre su detalle normal), "Girar otra vez" o cerrar. Si no tienes ningún
juego de ese tipo, avisa en vez de girar.

Los tipos de juego (`includes/helpers.php`, constante `LUDOTECA_TIPOS`) incluyen
Eurogame, Ameritrash/temático, Abstracto, Familiar, Filler, Party, Deducción social,
Legacy/campaña, Construcción de mazos, Wargame, Cooperativo, Juegos Rol, Roll&Write y
Colocación de losetas.

## 🧩 Expansiones

Al marcar un juego como expansión y elegir su juego base (al añadirlo o editarlo), la
relación se muestra en los dos sentidos sin tener que buscarla a mano: en el juego base
—al pasar el ratón en la vista Carátula, en la propia tarjeta en Ficha, o en su ficha de
detalle— aparece "Expansiones:" con un enlace a cada una; en cada expansión aparece "Se
necesita el juego base:" con un enlace al juego base. Los enlaces abren directamente la
ficha de detalle del otro juego (`data-action="open-detail"` en `assets/js/app.js`), así
que se puede saltar de una expansión a su base y viceversa sin volver a la colección.

## 📂 Menú lateral

La navegación vive en una barra lateral (no arriba): pestañas, tema y usuario, siempre
ocupando la altura completa de la pantalla (`.app-shell` es quien no hace scroll; el
contenido se desplaza dentro de sí mismo, no la página). En escritorio se puede colapsar
a solo iconos con el botón «/» de su cabecera — útil en pantallas más estrechas o
simplemente para ganar espacio en la colección. El colapsado es solo densidad visual del
dispositivo actual (no una preferencia de la cuenta como el tema), así que se guarda en
`localStorage`, no en la base de datos, y se aplica antes de pintar nada para que no haya
parpadeo al recargar.

## 📱 Responsive (móvil y tablet)

Por debajo de 760px de ancho el menú lateral deja de ocupar sitio fijo y se convierte en
un cajón deslizante: aparece una barra superior con el botón ☰ que lo abre sobre el
contenido (con un fondo oscurecido detrás); se cierra tocando ese fondo o elegiendo una
pestaña. El resto de la interfaz también se adapta — cuadrícula de carátulas y de fichas
de una sola columna cuando no cabe más de una, formularios de los diálogos a una columna
por debajo de 480px, y las tablas (Partidas, Ranking) se desplazan en horizontal dentro de
sí mismas si no caben, en vez de forzar el scroll de toda la página.

## 🎨 Temas y vista de la colección

Cuatro paletas (Crema, Pergamino, Tinta, Nocturno) seleccionables desde los 4 cuadraditos
de la cabecera; cada una define solo 5 colores base y el resto de rampas (100-900,
sombras) se derivan por interpolación — misma matemática en
[`includes/theme.php`](includes/theme.php) (resuelve el tema en el propio HTML para que no
haya parpadeo al cargar) y en `assets/js/app.js` (para el cambio en caliente). La
colección también tiene dos vistas intercambiables — "Carátula" (cuadrícula tipo kiosco:
zoom suave y brillo al pasar el ratón sobre la portada, con los datos revelándose encima)
y "Ficha" (la tarjeta con tags y pie que ya había) — con el mismo selector `Vista` de la
barra de filtros.

Ambas preferencias (tema y vista) se guardan en la tabla `settings` de la base de datos
(`api/preferences.php`) en cuanto se cambian, así que la próxima vez que inicies sesión
— desde el mismo dispositivo o desde otro — se carga tal cual se dejó. El tema es global
(no por persona) y se aplica también fuera de la SPA — `login.php`, `users.php` y
`update.php` resuelven y pintan el mismo `<style>` de `includes/theme.php` — para que no
haya ningún salto visual al color por defecto en ninguna pantalla de la aplicación.

## 👥 Usuarios y permisos

Hay tres roles:

- **Admin** — entra en `login.php` con el correo literal `admin` y la contraseña que se
  definió en `install.php`. Acceso completo y es el único que puede gestionar usuarios
  (`users.php`, enlace 👤 en la cabecera).
- **Coleccionista** — todo lo que hace hoy la app salvo gestionar usuarios: crear, editar
  y eliminar juegos, deseos, préstamos, temas, copia de seguridad...
- **Jugador** — ve toda la app en modo lectura, pero solo puede registrar partidas y usar
  "Quiero jugar". El resto de botones (añadir/editar/eliminar juegos, deseos, préstamos,
  descargar backup) ni siquiera se pintan para este rol, y el servidor los rechaza con
  403 aunque se llame a la API a mano — la restricción real está en
  `ludoteca_require_collector_api()` (`includes/auth.php`), no en la interfaz.

**Autenticación real por persona:** cada usuario que el admin añade en `users.php` tiene
su propio correo y su propia contraseña (se piden al crearlo, con confirmación). En
`login.php` se entra con correo + contraseña, y el sistema resuelve solo quién eres y
qué rol tienes — no hay ninguna pantalla intermedia de "elige quién eres". El admin es
el único caso especial: no tiene un correo real, usa el literal `admin` junto con la
contraseña de `install.php`. Las contraseñas se guardan siempre con `password_hash()`
(bcrypt), nunca en texto plano; el admin puede restablecer la contraseña de cualquier
usuario desde `users.php` si la olvida.

Crear un usuario crea (o vincula, si ya existía uno con ese nombre) su jugador en la
pestaña "Jugadores" automáticamente — no hay que darlo de alta por separado para que
sus partidas y estadísticas se atribuyan bien. Esto repara también las instalaciones que
ya tenían usuarios de antes de esta versión: `install.php`/`update.php` recorren los
`app_users` sin jugador vinculado y les crean uno con su mismo nombre
(`ludoteca_run_migrations` en [`includes/schema.php`](includes/schema.php)).

## 🙋 Quiero jugar

Cualquiera puede proponer un juego de la colección a una o varias de las personas de la
lista (pestaña "Quiero jugar" o botón "Proponer partida" desde la ficha de un juego). A
quien se lo propongan le sale un aviso destacado en la cabecera la próxima vez que entre
— y un contador en la propia pestaña — con dos opciones: **Descartar** (solo afecta a su
propia notificación, no a las de los demás destinatarios) o **Aceptar**. Quien la propuso
o el admin pueden cancelarla del todo en cualquier momento ("✕").

Aceptar deja la propuesta como "pendiente de registrar la partida": sigue apareciendo
(ya no se puede ni aceptar ni descartar, solo se ve su estado) hasta que alguien registra
una partida de ese juego — en ese momento se borra sola, porque ya "se convirtió" en esa
partida. Si nadie la acepta nunca, no pasa nada especial: se queda como una propuesta
normal hasta que se descarta o se cancela a mano.

En cuanto alguien la acepta, la tarjeta muestra un botón **Registrar** que abre el
diálogo de "Registrar partida" con el juego y los jugadores ya rellenos (quien la
propuso más quienes la hayan aceptado) — solo falta poner la duración y quién ganó.

Al crear la propuesta, cada destinatario recibe además un email automático avisándole
(`includes/mailer.php`, con `mail()` de PHP — el correo que ya usa para entrar, sin pedir
ni guardar ningún dato nuevo). No depende de ninguna cuenta ni servicio externo: en Hestia
el dominio ya sale listo para enviar correo. Si el envío falla (o el hosting no tiene
correo saliente configurado), la propuesta se crea igual — el aviso por email es un
extra, nunca una condición para que funcione "Quiero jugar". Se descartó WhatsApp
automático porque desde julio de 2025 Meta no ofrece capa gratuita para mensajes que
inicia la aplicación (se cobra por mensaje) y exige una plantilla aprobada de antemano
por Meta — cambiar a eso requeriría dar de alta una cuenta de WhatsApp Business aparte.

Cada intento de envío se registra en `includes/mail.log` (a quién, el asunto, si
`mail()` dijo que sí y el error de PHP si dijo que no) — esa carpeta ya está bloqueada
por completo por su `.htaccess`, así que el fichero no se puede descargar por la web,
pero se puede abrir por FTP o por el gestor de archivos de Hestia sin necesitar SSH. Que
`mail()` devuelva `true` solo significa que el servidor lo aceptó para intentar
entregarlo, no que haya llegado — si el log dice `ok=true` pero el correo no aparece
(ni en spam), hay que mirar el log de correo del propio servidor (en Hestia, panel del
dominio → **Mail** → revisar que el dominio tenga el correo activado, y el log de Exim
del servidor) y la configuración de SPF/DKIM del dominio.

El contador y el aviso significan cosas distintas según el rol: para **jugador** son
"invitaciones que me proponen a mí" (solo cuentan las propuestas donde él es
destinatario); **admin** y **coleccionista** ven en cambio el total de propuestas
pendientes de toda la colección, sean para quien sean, ya que pueden ver y gestionar
todo lo demás — el aviso se redacta como actividad general ("sin resolver") en vez de
como algo dirigido a ellos en persona.

## 💾 Copia de seguridad

El número de versión de la cabecera (`v1.9.2`) es también un enlace "oculto": para quien
puede gestionar la colección (admin/coleccionista), pulsarlo descarga un `.sql` con el
esquema completo (`CREATE TABLE IF NOT EXISTS`) más todos los datos como `INSERT`
(`backup.php` / `includes/backup.php`) — restaurable directamente con
`mysql basedatos < backup.sql` sobre una base de datos vacía. Para jugador es texto
plano sin enlace (y `backup.php` lo rechaza con 403 aunque se llame directamente).

## 🗂️ Estructura

```
install.php          Asistente de instalación (BBDD + esquema + contraseña + token BGG)
update.php           Reaplica el esquema (tablas/columnas nuevas) sin tocar datos ni credenciales
backup.php           Descarga el .sql completo (esquema + datos); solo admin/coleccionista
version.txt          Versión de los ficheros desplegados; se compara con la de la BBDD
login.php / logout.php   Acceso con correo + contraseña (resuelve la identidad directamente)
users.php            Alta/baja/rol/contraseña de usuarios permitidos (solo admin)
index.php             Cascarón de la aplicación (SPA ligera sin build step)
config.php            Generado por install.php (no se versiona)
includes/             Conexión PDO, autenticación+roles, BGG, correo, helpers, temas, backup, esquema y migraciones
api/                  Endpoints JSON consumidos por assets/js/app.js (preferences.php, want_to_play.php...)
assets/css/           Nocturne (base) + tokens ámbar y componentes propios de Ludoteca
assets/js/app.js      Render de las 7 vistas y los diálogos, llamadas fetch a la API
_design_reference/    Paquetes de diseño originales (handoff), sin usar en producción
docker/               Entorno de desarrollo (PHP+Apache y MySQL) para probar en local
```

## 🏷️ Versión y aviso de actualización

`version.txt` guarda la versión de los ficheros que hay desplegados; la tabla `settings`
de la base de datos guarda la última versión que se aplicó con `install.php`/`update.php`.
Si no coinciden, la cabecera de la app muestra "Actualizar a la versión X.X.X" enlazando a
`update.php`; si coinciden, muestra solo "vX.X.X" en gris. Sube el número de `version.txt`
cada vez que cambies el esquema o publiques una versión nueva.

## 🐳 Probar en local con Docker

`docker/docker-compose.yml` levanta un PHP 8.2 + Apache (con `pdo_mysql` y `curl`) y un
MySQL 8. Desde `docker/`:

```bash
docker compose up -d --build
```

Abre `http://localhost:8080/install.php` y usa `db` como host de MySQL (nombre del
servicio en la red interna de Docker) con las credenciales del `docker-compose.yml`
(usuario/contraseña `ludoteca`). Para parar el entorno: `docker compose down` (añade
`-v` si además quieres borrar los datos de MySQL).

## 🔒 Notas de seguridad para producción

- Cambia la contraseña de admin desde `install.php` si sospechas que se ha filtrado.
- `config.php` está bloqueado por `.htaccess`; no lo expongas por otra vía (backups
  públicos, etc.).
- Las contraseñas de `app_users` se guardan con `password_hash()` (bcrypt), nunca en
  texto plano; si alguien olvida la suya, restablécela desde `users.php`.
- Los backups (`backup.php`) contienen todos tus datos, incluidos los hashes de
  contraseña de la lista de usuarios; trátalos con el mismo cuidado que `config.php`.

## 📜 Historial de versiones

> Reconstruido a partir del desarrollo de la aplicación (el proyecto no llevaba control
> de versiones desde el principio), así que las fechas exactas de cada versión no están
> disponibles — el orden y el contenido sí son fieles a como se fue construyendo.

| Versión | Resumen |
|---|---|
| **1.9.2** | Corrección: el diálogo de "Juego aleatorio" podía renderizarse fuera de la pantalla (un `<div>` con contenido muy ancho inflaba la rejilla que lo centraba). |
| **1.9.1** | Corrección: el carrusel del "Juego aleatorio" no se deslizaba — saltaba directo al resultado en vez de animarse. |
| **1.9.0** | Tres tipos de juego nuevos (Juegos Rol, Roll&Write, Colocación de losetas) y la función **Juego aleatorio**: un carrusel de carátulas que gira y frena hasta un resultado. |
| **1.8.6** | El buscador de BoardGameGeek trae hasta 50 resultados (antes 12) e incluye expansiones en la búsqueda. |
| **1.8.5** | El nombre de un juego ya no se sobrescribe con el de BGG si ya estaba definido (BGG no permite pedir el nombre en español). |
| **1.8.4** | `install.php` permite dejar en blanco los campos que no cambian al reinstalar (contraseña de BBDD, contraseña de admin, token de BGG) y mantiene los valores actuales. |
| **1.8.3** | Botón "Registrar" directo desde una propuesta de "Quiero jugar" ya aceptada, con el juego y los jugadores precargados. |
| **1.8.2** | Registro (`includes/mail.log`) de cada intento de envío de email, para poder diagnosticar por qué no llega un aviso. |
| **1.8.1** | Aviso automático por email a cada destinatario cuando se crea una propuesta de "Quiero jugar" (se valoró y descartó WhatsApp automático por su coste y por exigir una plantilla aprobada por Meta). |
| **1.8.0** | Las propuestas de "Quiero jugar" se pueden **aceptar** (no solo descartar); al registrar la partida correspondiente, la propuesta se resuelve sola. |
| **1.7.2** | El contador de "Quiero jugar" refleja el total de propuestas pendientes para admin y coleccionista, no solo las que le proponen a él. |
| **1.7.1** | Crear un usuario crea (o vincula) su jugador automáticamente; las instalaciones con usuarios antiguos se repararan solas al actualizar. |
| **1.7.0** | Diseño responsive completo: el menú lateral se convierte en cajón deslizante en móvil/tablet; diálogos y tablas se adaptan a pantallas pequeñas. |
| **1.6.1** | Se retira la rejilla de fondo decorativa; el menú lateral ocupa siempre toda la altura de la pantalla (antes podía dejar un hueco al hacer scroll). |
| **1.6.0** | Menú de navegación lateral colapsable (antes arriba); vista Carátula con efecto "kiosco" (zoom y brillo al pasar el ratón); la relación entre un juego base y sus expansiones se muestra en los dos sentidos. |
| **1.5.2** | El tema de color elegido se aplica también fuera de la colección: login, gestión de usuarios y pantalla de actualización. |
| **1.5.1** | Autenticación real por persona: cada usuario tiene su propio correo y contraseña; el admin entra con el correo literal `admin`. |
| **1.5.0** | Copia de seguridad completa descargable desde el número de versión; multiusuario real con roles (admin/coleccionista/jugador); "Quiero jugar" dirigido a personas concretas, con notificación. |
| **1.4.1** | Enlace "Complementos 3D" en la ficha de cada juego, con búsqueda directa en MakerWorld. |
| **1.4.0** | Nueva estética visual: cuatro temas de color y dos vistas de colección (Carátula/Ficha), guardadas como preferencia. |
| **1.3.0** | Partidas cooperativas: al registrar una partida de un juego cooperativo se pide Victoria/Derrota en vez de un ganador individual. |
| **1.2.0** | Edad mínima recomendada y "Versión Premium" en juegos y deseos; los juegos se pueden marcar como expansión de otro juego. |
| **1.1.0** | Edición de juegos; el buscador de BGG llega también a la lista de deseos; pestaña Ranking; control de versión de fichero vs. base de datos con aviso de actualización; entorno Docker para desarrollo; `install.php` actualiza instalaciones existentes sin perder datos. |
| **1.0.0** | Primera versión: colección, partidas, jugadores, deseos y préstamos; instalador con contraseña de acceso; carátulas siempre traídas en vivo desde la API de BoardGameGeek. |
