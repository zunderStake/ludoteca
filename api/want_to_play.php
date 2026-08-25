<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/../includes/mailer.php';

ludoteca_require_login_api();
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido.', 405);
}

$identity = ludoteca_identity();
$b = json_body();
$action = str_or($b['action'] ?? '', 'create');

if ($action === 'create') {
    $gameId = (int) ($b['game_id'] ?? 0);
    $targetIds = array_values(array_unique(array_map('intval', (array) ($b['target_ids'] ?? []))));

    if (!$gameId) {
        json_error('Selecciona un juego.');
    }
    if (!$targetIds) {
        json_error('Elige al menos una persona con quien jugar.');
    }

    $gameStmt = $pdo->prepare('SELECT nombre FROM games WHERE id = ?');
    $gameStmt->execute([$gameId]);
    $gameNombre = $gameStmt->fetchColumn();
    if ($gameNombre === false) {
        json_error('El juego no existe.');
    }

    $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
    $validStmt = $pdo->prepare("SELECT id, email, nombre FROM app_users WHERE id IN ($placeholders)");
    $validStmt->execute($targetIds);
    $validTargets = $validStmt->fetchAll();
    if (!$validTargets) {
        json_error('Ninguno de los destinatarios existe.');
    }
    $validIds = array_map(fn ($t) => (int) $t['id'], $validTargets);

    $requesterNombre = $identity['nombre'] ?? 'Admin';

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO want_to_play (game_id, requested_by_user_id, requested_by_nombre) VALUES (?, ?, ?)')
            ->execute([$gameId, $identity['userId'], $requesterNombre]);
        $id = (int) $pdo->lastInsertId();

        $link = $pdo->prepare('INSERT INTO want_to_play_targets (want_to_play_id, user_id) VALUES (?, ?)');
        foreach ($validIds as $userId) {
            $link->execute([$id, $userId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_error('No se pudo crear la propuesta: ' . $e->getMessage(), 500);
    }

    // El aviso por email es un extra, no una condición para crear la propuesta: si
    // falla el envío (o no hay correo válido), la propuesta ya se guardó igualmente.
    foreach ($validTargets as $target) {
        if ($target['email'] !== '' && filter_var($target['email'], FILTER_VALIDATE_EMAIL)) {
            ludoteca_notify_want_to_play($target['email'], $target['nombre'], $requesterNombre, (string) $gameNombre);
        }
    }

    json_ok(['id' => $id]);
} elseif ($action === 'dismiss') {
    // Cada destinatario descarta solo su propia notificación; no afecta a los demás.
    if (!$identity['userId']) {
        json_error('El admin no es destinatario de propuestas; usa "Cancelar" para borrarla del todo.');
    }
    $id = (int) ($b['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id.');
    }
    $pdo->prepare('UPDATE want_to_play_targets SET dismissed_at = NOW() WHERE want_to_play_id = ? AND user_id = ?')
        ->execute([$id, $identity['userId']]);
    json_ok();
} elseif ($action === 'accept') {
    // Aceptar deja la propuesta "pendiente de registrar partida": se queda visible
    // hasta que alguien registre una partida de ese juego, momento en el que
    // api/plays.php la borra sola (ya "se convirtió" en la partida).
    if (!$identity['userId']) {
        json_error('El admin no es destinatario de propuestas.');
    }
    $id = (int) ($b['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id.');
    }
    $pdo->prepare('UPDATE want_to_play_targets SET accepted_at = NOW(), dismissed_at = NULL WHERE want_to_play_id = ? AND user_id = ?')
        ->execute([$id, $identity['userId']]);
    json_ok();
} elseif ($action === 'delete') {
    $id = (int) ($b['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id.');
    }
    $stmt = $pdo->prepare('SELECT requested_by_user_id FROM want_to_play WHERE id = ?');
    $stmt->execute([$id]);
    $requesterId = $stmt->fetchColumn();
    if ($requesterId === false) {
        json_ok(); // ya no existe
    }
    $isOwner = $identity['userId'] !== null && (int) $requesterId === (int) $identity['userId'];
    if (!ludoteca_is_admin() && !$isOwner) {
        json_error('Solo quien la propuso (o el admin) puede cancelarla.', 403);
    }
    $pdo->prepare('DELETE FROM want_to_play WHERE id = ?')->execute([$id]);
    json_ok();
} else {
    json_error('Acción no válida.');
}
