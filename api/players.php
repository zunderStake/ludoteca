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
$nombre = str_or($b['nombre'] ?? '');
if ($nombre === '') {
    json_error('Pon un nombre de jugador.');
}

$id = repo_find_or_create_player($pdo, $nombre);
json_ok(['id' => $id, 'nombre' => $nombre]);
