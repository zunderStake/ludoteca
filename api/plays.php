<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';

ludoteca_require_login_api();
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
if ($isCoop) {
    $resultado = str_or($b['resultado'] ?? '', 'Victoria');
    if (!in_array($resultado, ['Victoria', 'Derrota'], true)) {
        $resultado = 'Victoria';
    }
} else {
    $ganadorNombre = str_or($b['ganador'] ?? '', $jugadores[0]);
    $ganadorId = $playerIds[$ganadorNombre] ?? repo_find_or_create_player($pdo, $ganadorNombre);
}

$fecha = date('Y-m-d');
$duracion = max(1, (int) num_or($b['duracion'] ?? null, 60));

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO plays (game_id, fecha, ganador_id, resultado, duracion) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$gameId, $fecha, $ganadorId, $resultado, $duracion]);
    $playId = (int) $pdo->lastInsertId();

    $link = $pdo->prepare('INSERT INTO play_players (play_id, player_id) VALUES (?, ?)');
    foreach ($playerIds as $pid) {
        $link->execute([$playId, $pid]);
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
