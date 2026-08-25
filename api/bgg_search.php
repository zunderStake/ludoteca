<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/bgg.php';

ludoteca_require_login_api();

$q = str_or($_GET['q'] ?? '');
if ($q === '' || mb_strlen($q) < 2) {
    json_ok(['results' => []]);
}

try {
    json_ok(['results' => bgg_search($q)]);
} catch (BggApiError $e) {
    json_error($e->getMessage(), 502);
}
