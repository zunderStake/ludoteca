<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';

ludoteca_require_login_api();
ludoteca_require_collector_api(); // crear/editar/eliminar juegos es cosa de admin/coleccionista
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);
$method = $_SERVER['REQUEST_METHOD'];

/** Normaliza edad/premium/expansión a partir del cuerpo de la petición. */
function games_extra_fields(array $b, ?int $selfId = null): array
{
    $edad = (int) max(0, min(99, num_or($b['edad_minima'] ?? null, 0)));
    $premium = !empty($b['premium']);
    $esExpansion = !empty($b['es_expansion']);
    $baseGameId = $esExpansion && !empty($b['base_game_id']) ? (int) $b['base_game_id'] : null;
    if ($baseGameId !== null && $selfId !== null && $baseGameId === $selfId) {
        $baseGameId = null; // un juego no puede ser expansión de sí mismo
    }
    return [$edad, $premium ? 1 : 0, $esExpansion ? 1 : 0, $baseGameId];
}

if ($method === 'POST') {
    $b = json_body();
    $nombre = str_or($b['nombre'] ?? '');
    if ($nombre === '') {
        json_error('Pon al menos un nombre.');
    }
    [$edad, $premium, $esExpansion, $baseGameId] = games_extra_fields($b);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO games (nombre, editorial, tipo, puntuacion, precio, jugadores, duracion, bgg_id,
                    imagen_url, edad_minima, premium, es_expansion, base_game_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $nombre,
            str_or($b['editorial'] ?? '', 'Sin editorial'),
            str_or($b['tipo'] ?? '', 'Eurogame'),
            max(0, min(10, num_or($b['puntuacion'] ?? null, 0))),
            max(0, num_or($b['precio'] ?? null, 0)),
            str_or($b['jugadores'] ?? '', '2-4'),
            str_or($b['duracion'] ?? '', '60 min'),
            !empty($b['bgg_id']) ? (int) $b['bgg_id'] : null,
            !empty($b['imagen_url']) ? str_or($b['imagen_url']) : null,
            $edad, $premium, $esExpansion, $baseGameId,
        ]);
    } catch (Throwable $e) {
        json_error('No se pudo guardar el juego (¿el juego base elegido existe?): ' . $e->getMessage(), 500);
    }

    json_ok(['id' => (int) $pdo->lastInsertId()]);
} elseif ($method === 'PUT') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id del juego.');
    }
    $b = json_body();
    $nombre = str_or($b['nombre'] ?? '');
    if ($nombre === '') {
        json_error('Pon al menos un nombre.');
    }
    [$edad, $premium, $esExpansion, $baseGameId] = games_extra_fields($b, $id);

    try {
        $stmt = $pdo->prepare(
            'UPDATE games SET nombre = ?, editorial = ?, tipo = ?, puntuacion = ?, precio = ?,
                    jugadores = ?, duracion = ?, bgg_id = ?, imagen_url = ?,
                    edad_minima = ?, premium = ?, es_expansion = ?, base_game_id = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $nombre,
            str_or($b['editorial'] ?? '', 'Sin editorial'),
            str_or($b['tipo'] ?? '', 'Eurogame'),
            max(0, min(10, num_or($b['puntuacion'] ?? null, 0))),
            max(0, num_or($b['precio'] ?? null, 0)),
            str_or($b['jugadores'] ?? '', '2-4'),
            str_or($b['duracion'] ?? '', '60 min'),
            !empty($b['bgg_id']) ? (int) $b['bgg_id'] : null,
            !empty($b['imagen_url']) ? str_or($b['imagen_url']) : null,
            $edad, $premium, $esExpansion, $baseGameId,
            $id,
        ]);
    } catch (Throwable $e) {
        json_error('No se pudo guardar el juego (¿el juego base elegido existe?): ' . $e->getMessage(), 500);
    }

    json_ok();
} elseif ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id del juego.');
    }
    $pdo->prepare('DELETE FROM games WHERE id = ?')->execute([$id]);
    json_ok();
} else {
    json_error('Método no permitido.', 405);
}
