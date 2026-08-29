<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';

ludoteca_require_login_api();
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'DELETE') {
    $playId = (int) ($_GET['id'] ?? 0);
    if (!$playId) {
        json_error('Falta el id de la partida.');
    }
    // play_players tiene ON DELETE CASCADE (ver includes/schema.php), así que basta con
    // borrar la partida para que se lleve también sus jugadores.
    $pdo->prepare('DELETE FROM plays WHERE id = ?')->execute([$playId]);
    json_ok();
}

if ($method !== 'POST' && $method !== 'PUT') {
    json_error('Método no permitido.', 405);
}

$b = json_body();
$gameId = (int) ($b['game_id'] ?? 0);
$jugadores = array_values(array_filter(array_map('trim', (array) ($b['jugadores'] ?? []))));

if (!$gameId) {
    json_error('Selecciona un juego.');
}
if (!$jugadores) {
    json_error('Selecciona al menos un jugador.');
}

$gameStmt = $pdo->prepare('SELECT tipo FROM games WHERE id = ?');
$gameStmt->execute([$gameId]);
$tipo = $gameStmt->fetchColumn();
if ($tipo === false) {
    json_error('El juego no existe.');
}
$isCoop = $tipo === 'Cooperativo';

$playerIds = [];
foreach ($jugadores as $nombre) {
    $playerIds[$nombre] = repo_find_or_create_player($pdo, $nombre);
}

// Los juegos cooperativos se ganan o se pierden en grupo: no hay un "ganador" individual.
$ganadorId = null;
$resultado = null;
$empate = false;
$ganadorPlayerIds = [];
if ($isCoop) {
    $resultado = str_or($b['resultado'] ?? '', 'Victoria');
    if (!in_array($resultado, ['Victoria', 'Derrota'], true)) {
        $resultado = 'Victoria';
    }
} else {
    $empate = !empty($b['empate']);
    if ($empate) {
        $ganadoresNombres = array_values(array_unique(array_filter(array_map('trim', (array) ($b['ganadores'] ?? [])))));
        $ganadoresNombres = array_values(array_intersect($ganadoresNombres, $jugadores));
        if (count($ganadoresNombres) < 2) {
            json_error('En un empate, selecciona al menos dos jugadores empatados.');
        }
        foreach ($ganadoresNombres as $nombre) {
            $ganadorPlayerIds[] = $playerIds[$nombre];
        }
    } else {
        $ganadorNombre = str_or($b['ganador'] ?? '', $jugadores[0]);
        $ganadorId = $playerIds[$ganadorNombre] ?? repo_find_or_create_player($pdo, $ganadorNombre);
    }
}

$duracion = max(1, (int) num_or($b['duracion'] ?? null, 60));

if ($method === 'POST') {
    $fecha = date('Y-m-d');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO plays (game_id, fecha, ganador_id, resultado, empate, duracion) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$gameId, $fecha, $ganadorId, $resultado, $empate ? 1 : 0, $duracion]);
        $playId = (int) $pdo->lastInsertId();

        $link = $pdo->prepare('INSERT INTO play_players (play_id, player_id, es_ganador) VALUES (?, ?, ?)');
        foreach ($playerIds as $pid) {
            $link->execute([$playId, $pid, in_array($pid, $ganadorPlayerIds, true) ? 1 : 0]);
        }

        // Las propuestas de "Quiero jugar" de este juego que alguien había aceptado ya
        // cumplieron su función: se registró la partida, así que se convierten en ella
        // (se borran) en vez de quedarse pendientes para siempre.
        $pdo->prepare(
            'DELETE w FROM want_to_play w
             WHERE w.game_id = ?
               AND EXISTS (SELECT 1 FROM want_to_play_targets t WHERE t.want_to_play_id = w.id AND t.accepted_at IS NOT NULL)'
        )->execute([$gameId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_error('No se pudo registrar la partida: ' . $e->getMessage(), 500);
    }

    json_ok(['id' => $playId]);
} else {
    // PUT: editar una partida ya registrada (fecha, juego, jugadores, ganador/resultado, duración).
    $playId = (int) ($_GET['id'] ?? 0);
    if (!$playId) {
        json_error('Falta el id de la partida.');
    }
    $existsStmt = $pdo->prepare('SELECT 1 FROM plays WHERE id = ?');
    $existsStmt->execute([$playId]);
    if (!$existsStmt->fetchColumn()) {
        json_error('Esa partida ya no existe.', 404);
    }

    $fecha = str_or($b['fecha'] ?? '', date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        json_error('Fecha no válida.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE plays SET game_id = ?, fecha = ?, ganador_id = ?, resultado = ?, empate = ?, duracion = ? WHERE id = ?')
            ->execute([$gameId, $fecha, $ganadorId, $resultado, $empate ? 1 : 0, $duracion, $playId]);

        $pdo->prepare('DELETE FROM play_players WHERE play_id = ?')->execute([$playId]);
        $link = $pdo->prepare('INSERT INTO play_players (play_id, player_id, es_ganador) VALUES (?, ?, ?)');
        foreach ($playerIds as $pid) {
            $link->execute([$playId, $pid, in_array($pid, $ganadorPlayerIds, true) ? 1 : 0]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_error('No se pudo guardar la partida: ' . $e->getMessage(), 500);
    }

    json_ok();
}
