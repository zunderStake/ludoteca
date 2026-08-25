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
    $action = str_or($b['action'] ?? '', 'create');

    if ($action === 'buy') {
        $id = (int) ($b['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM wishlist WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            json_error('No se encontró el deseo.', 404);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO games (nombre, editorial, tipo, puntuacion, precio, jugadores, duracion, bgg_id,
                        imagen_url, edad_minima, premium)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $item['nombre'], $item['editorial'] ?: 'Sin editorial', $item['tipo'] ?: 'Eurogame',
                $item['puntuacion'] ?? 0, $item['precio'], $item['jugadores'] ?: '2-4', $item['duracion'] ?: '60 min',
                $item['bgg_id'], $item['imagen_url'], $item['edad_minima'] ?? 0, $item['premium'] ?? 0,
            ]);
            $newId = (int) $pdo->lastInsertId();
            $pdo->prepare('DELETE FROM wishlist WHERE id = ?')->execute([$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_error('No se pudo mover el juego a la colección.', 500);
        }
        json_ok(['game_id' => $newId]);
    }

    // create
    $nombre = str_or($b['nombre'] ?? '');
    if ($nombre === '') {
        json_error('Pon al menos un nombre.');
    }
    $prioridad = str_or($b['prioridad'] ?? '', 'Media');
    if (!in_array($prioridad, ['Alta', 'Media', 'Baja'], true)) {
        $prioridad = 'Media';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO wishlist (nombre, editorial, tipo, puntuacion, precio, jugadores, duracion, prioridad,
                bgg_id, imagen_url, edad_minima, premium)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $nombre,
        str_or($b['editorial'] ?? '', 'Sin editorial'),
        str_or($b['tipo'] ?? '', 'Eurogame'),
        max(0, min(10, num_or($b['puntuacion'] ?? null, 0))),
        max(0, num_or($b['precio'] ?? null, 0)),
        str_or($b['jugadores'] ?? '', '2-4'),
        str_or($b['duracion'] ?? '', '60 min'),
        $prioridad,
        !empty($b['bgg_id']) ? (int) $b['bgg_id'] : null,
        !empty($b['imagen_url']) ? str_or($b['imagen_url']) : null,
        (int) max(0, min(99, num_or($b['edad_minima'] ?? null, 0))),
        !empty($b['premium']) ? 1 : 0,
    ]);
    json_ok(['id' => (int) $pdo->lastInsertId()]);
} elseif ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        json_error('Falta el id.');
    }
    $pdo->prepare('DELETE FROM wishlist WHERE id = ?')->execute([$id]);
    json_ok();
} else {
    json_error('Método no permitido.', 405);
}
