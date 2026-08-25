<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/bgg.php';

ludoteca_require_login_api();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    json_error('Falta el id de BoardGameGeek.');
}

try {
    json_ok(['thing' => bgg_thing($id)]);
} catch (BggApiError $e) {
    json_error($e->getMessage(), 502);
}
