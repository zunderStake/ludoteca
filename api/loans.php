<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';

ludoteca_require_login_api();
ludoteca_require_collector_api();
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $b = json_body();
    $gameId = (int) ($b['game_id'] ?? 0);
    $persona = str_or($b['persona'] ?? '');
    if (!$gameId || $persona === '') {
        json_error('Indica el juego y a quién se lo prestas.');
    }
    $fecha = str_or($b['fecha_prestamo'] ?? '', date('Y-m-d'));

    $stmt = $pdo->prepare('INSERT INTO loans (game_id, persona, fecha_prestamo) VALUES (?, ?, ?)');
    $stmt->execute([$gameId, $persona, $fecha]);
    json_ok(['id' => (int) $pdo->lastInsertId()]);
} elseif ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id.');
    }
    $pdo->prepare('DELETE FROM loans WHERE id = ?')->execute([$id]);
    json_ok();
} else {
    json_error('Método no permitido.', 405);
}
