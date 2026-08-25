<?php
/**
 * Copia generada automáticamente por install.php como config.php.
 * No la edites a mano salvo que sepas lo que haces: vuelve a ejecutar
 * install.php si necesitas cambiar la conexión o la contraseña de acceso.
 */

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'ludoteca',
        'user' => 'ludoteca',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // Hash generado con password_hash(); se pide en install.php.
    'app_password_hash' => '',
    // Token de aplicación de BoardGameGeek (https://boardgamegeek.com/applications/create).
    // Obligatorio desde 2025 para que las búsquedas de portadas funcionen; se pide en install.php.
    'bgg_token' => '',
];
