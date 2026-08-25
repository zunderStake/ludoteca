<?php
/** Utilidades compartidas por los endpoints de la API. */

const LUDOTECA_TIPOS = [
    'Eurogame', 'Ameritrash / temático', 'Abstracto', 'Familiar', 'Filler', 'Party',
    'Deducción social', 'Legacy / campaña', 'Construcción de mazos', 'Wargame', 'Cooperativo',
    'Juegos Rol', 'Roll&Write', 'Colocación de losetas',
];

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok($data = []): void
{
    json_out(['ok' => true] + (is_array($data) ? $data : ['data' => $data]));
}

function json_error(string $message, int $status = 400): void
{
    json_out(['ok' => false, 'error' => $message], $status);
}

function json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function str_or($v, string $default = ''): string
{
    $v = trim((string) ($v ?? ''));
    return $v === '' ? $default : $v;
}

function num_or($v, float $default = 0): float
{
    if ($v === null || $v === '') {
        return $default;
    }
    return is_numeric($v) ? (float) $v : $default;
}

/** Versión de los ficheros desplegados (version.txt), no la que hay aplicada en la BBDD. */
function ludoteca_app_version(): string
{
    $path = __DIR__ . '/../version.txt';
    if (!is_file($path)) {
        return '0.0.0';
    }
    return trim((string) file_get_contents($path)) ?: '0.0.0';
}
