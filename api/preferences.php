<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/repo.php';
require_once __DIR__ . '/../includes/theme.php';

ludoteca_require_login_api();
$pdo = ludoteca_db();
ludoteca_require_up_to_date_api($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Método no permitido.', 405);
}

$b = json_body();
$name = str_or($b['name'] ?? '');
$value = str_or($b['value'] ?? '');

$allowed = [
    'theme' => array_keys(LUDOTECA_THEMES),
    'view_mode' => LUDOTECA_VIEW_MODES,
];

if (!isset($allowed[$name]) || !in_array($value, $allowed[$name], true)) {
    json_error('Preferencia no válida.');
}

repo_set_setting($pdo, $name, $value);
json_ok();
