<?php
/**
 * Descarga un backup completo (esquema + datos) en un .sql. Enlazado desde el número
 * de versión de la cabecera (ver assets/js/app.js, renderVersionBadge) a propósito
 * como opción "oculta": es un enlace normal, pero no anunciado con un botón aparte.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

ludoteca_require_login_page();

if (!ludoteca_is_collector()) {
    http_response_code(403);
    echo 'No tienes permiso para descargar la copia de seguridad.';
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/backup.php';

$sql = ludoteca_backup_sql(ludoteca_db());
$filename = 'ludoteca-backup-' . date('Ymd-His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($sql));
header('Cache-Control: no-store');
echo $sql;
