<?php
/** Conexión PDO a MySQL a partir de config.php (generado por install.php). */

function ludoteca_config(): array
{
    $path = __DIR__ . '/../config.php';
    if (!is_file($path)) {
        header('Location: install.php');
        exit;
    }
    return require $path;
}

function ludoteca_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = ludoteca_config()['db'];
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'] ?: '3306',
        $cfg['name'],
        $cfg['charset'] ?: 'utf8mb4'
    );

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
