<?php
/**
 * Integración con la XMLAPI2 de BoardGameGeek (https://boardgamegeek.com/xmlapi2/).
 * No admite CORS, así que las llamadas se hacen siempre desde el servidor
 * (ver api/bgg_search.php y api/bgg_thing.php). Las imágenes NO se descargan
 * ni se copian: solo guardamos la URL que devuelve BGG y el navegador la
 * carga directamente desde su CDN.
 *
 * Desde el 2 de julio de 2025 BGG exige que cualquier aplicación (salvo un
 * usuario descargando su propia colección con sesión iniciada) esté
 * registrada y envíe un token de autorización como
 * "Authorization: Bearer <token>" en cada petición; sin él, tanto /search
 * como /thing devuelven 401 Unauthorized aunque el juego exista. El token
 * se obtiene registrando una aplicación en
 * https://boardgamegeek.com/applications/create (requiere iniciar sesión
 * en BGG) y se configura en install.php.
 */

require_once __DIR__ . '/db.php';

class BggApiError extends RuntimeException
{
}

function bgg_token(): string
{
    $cfg = ludoteca_config();
    return trim((string) ($cfg['bgg_token'] ?? ''));
}

/** @return array{status:int, body:?string} */
function bgg_http_get(string $url): array
{
    $token = bgg_token();
    $headers = ['Accept: application/xml'];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => 'Ludoteca/1.0 (+personal collection manager)',
        CURLOPT_HTTPHEADER => $headers,
    ]);

    // BGG a veces responde 202 "procesando" en peticiones bajo carga; reintentamos brevemente.
    $status = 0;
    $body = false;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 202) {
            break;
        }
        usleep(700000);
    }
    curl_close($ch);

    return ['status' => $status, 'body' => $body === false ? null : $body];
}

/** Traduce un fallo HTTP de BGG a un mensaje en español entendible por el usuario. */
function bgg_error_message(int $status): string
{
    if ($status === 401 || $status === 403) {
        $hasToken = bgg_token() !== '';
        if (!$hasToken) {
            return 'BoardGameGeek exige desde 2025 un token de aplicación registrado. '
                . 'Regístrate en https://boardgamegeek.com/applications/create (con tu cuenta de BGG) '
                . 'y pega el token en install.php.';
        }
        return 'BoardGameGeek ha rechazado el token configurado (401/403). Revisa que el token '
            . 'en install.php sea correcto y no haya caducado.';
    }
    if ($status === 0) {
        return 'No se pudo contactar con BoardGameGeek (sin respuesta del servidor). Inténtalo de nuevo en unos segundos.';
    }
    return "BoardGameGeek respondió con un error inesperado (HTTP {$status}).";
}

/** Busca juegos por nombre. Devuelve [{id, nombre, anio}]. @throws BggApiError */
function bgg_search(string $query): array
{
    // "boardgame" solo son los juegos base; las expansiones son un tipo aparte en BGG
    // (boardgameexpansion) y se quedaban fuera de la búsqueda aunque existieran.
    $url = 'https://boardgamegeek.com/xmlapi2/search?type=boardgame,boardgameexpansion&query=' . rawurlencode($query);
    $res = bgg_http_get($url);
    if ($res['status'] !== 200 || $res['body'] === null) {
        throw new BggApiError(bgg_error_message($res['status']));
    }

    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($res['body']);
    libxml_use_internal_errors($prev);
    if ($xml === false) {
        throw new BggApiError('BoardGameGeek devolvió una respuesta que no se pudo interpretar.');
    }

    $results = [];
    foreach ($xml->item as $item) {
        $name = (string) ($item->name['value'] ?? '');
        if ($name === '') {
            continue;
        }
        $results[] = [
            'id' => (int) $item['id'],
            'nombre' => $name,
            'anio' => isset($item->yearpublished) ? (string) $item->yearpublished['value'] : '',
            'es_expansion' => (string) $item['type'] === 'boardgameexpansion',
        ];
        // BGG puede devolver muchas coincidencias parciales; con 12 se quedaban fuera
        // resultados legítimos (sobre todo expansiones, que ahora también se piden).
        if (count($results) >= 50) {
            break;
        }
    }
    return $results;
}

/** Obtiene el detalle de un juego por id de BGG. @throws BggApiError */
function bgg_thing(int $id): array
{
    $url = 'https://boardgamegeek.com/xmlapi2/thing?stats=1&id=' . $id;
    $res = bgg_http_get($url);
    if ($res['status'] !== 200 || $res['body'] === null) {
        throw new BggApiError(bgg_error_message($res['status']));
    }

    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($res['body']);
    libxml_use_internal_errors($prev);
    if ($xml === false || !isset($xml->item)) {
        throw new BggApiError('BoardGameGeek devolvió una respuesta que no se pudo interpretar.');
    }

    $item = $xml->item;
    $name = '';
    foreach ($item->name as $n) {
        if ((string) $n['type'] === 'primary') {
            $name = (string) $n['value'];
            break;
        }
    }
    if ($name === '' && isset($item->name[0])) {
        $name = (string) $item->name[0]['value'];
    }

    $publisher = '';
    foreach ($item->link as $link) {
        if ((string) $link['type'] === 'boardgamepublisher') {
            $publisher = (string) $link['value'];
            break;
        }
    }

    $minPlayers = (int) ($item->minplayers['value'] ?? 0);
    $maxPlayers = (int) ($item->maxplayers['value'] ?? 0);
    $jugadores = $minPlayers > 0
        ? ($maxPlayers > $minPlayers ? "{$minPlayers}-{$maxPlayers}" : (string) $minPlayers)
        : '';

    $playTime = (int) ($item->playingtime['value'] ?? 0);
    $duracion = $playTime > 0 ? "{$playTime} min" : '';

    $edadMinima = (int) ($item->minage['value'] ?? 0);

    $rating = null;
    if (isset($item->statistics->ratings->average['value'])) {
        $rating = round((float) $item->statistics->ratings->average['value'], 1);
    }

    return [
        'bgg_id' => $id,
        'nombre' => $name,
        'editorial' => $publisher,
        'jugadores' => $jugadores,
        'duracion' => $duracion,
        'edad_minima' => $edadMinima,
        'puntuacion_bgg' => $rating,
        'imagen_url' => isset($item->image) ? trim((string) $item->image) : '',
        'thumbnail_url' => isset($item->thumbnail) ? trim((string) $item->thumbnail) : '',
    ];
}
