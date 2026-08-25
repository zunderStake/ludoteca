<?php
/**
 * Acceso de Ludoteca: autenticación real por persona con correo + contraseña
 * (login.php). El admin usa el literal "admin" como correo junto con la contraseña
 * de config.php; cada app_user tiene su propio correo y su propio hash de contraseña
 * en la tabla app_users. La identidad (rol, id, nombre) queda fijada en la sesión en
 * el momento del login, no se elige después.
 *
 * Roles: 'admin' (contraseña de config.php; gestiona los usuarios permitidos y tiene
 * acceso completo), 'coleccionista' (todo excepto gestionar usuarios) y 'jugador'
 * (solo puede registrar partidas y usar "Quiero jugar"; el resto lo ve en modo
 * lectura).
 */

function ludoteca_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function ludoteca_is_logged_in(): bool
{
    ludoteca_start_session();
    return !empty($_SESSION['ludoteca_auth']);
}

/** Para páginas HTML: redirige a login.php si no hay sesión. */
function ludoteca_require_login_page(): void
{
    if (!ludoteca_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** Para endpoints JSON: responde 401 si no hay sesión. */
function ludoteca_require_login_api(): void
{
    if (!ludoteca_is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autenticado.']);
        exit;
    }
}

/** @return array{role:string, userId:?int, nombre:?string} */
function ludoteca_identity(): array
{
    ludoteca_start_session();
    return [
        'role' => $_SESSION['ludoteca_role'] ?? 'admin',
        'userId' => $_SESSION['ludoteca_user_id'] ?? null,
        'nombre' => $_SESSION['ludoteca_user_nombre'] ?? null,
    ];
}

function ludoteca_set_identity(?int $userId, string $role, ?string $nombre): void
{
    ludoteca_start_session();
    $_SESSION['ludoteca_user_id'] = $userId;
    $_SESSION['ludoteca_role'] = $role;
    $_SESSION['ludoteca_user_nombre'] = $nombre;
}

function ludoteca_clear_identity(): void
{
    ludoteca_start_session();
    unset($_SESSION['ludoteca_user_id'], $_SESSION['ludoteca_role'], $_SESSION['ludoteca_user_nombre']);
}

function ludoteca_is_admin(): bool
{
    return ludoteca_identity()['role'] === 'admin';
}

/** admin y coleccionista pueden gestionar la colección; jugador no. */
function ludoteca_is_collector(): bool
{
    return in_array(ludoteca_identity()['role'], ['admin', 'coleccionista'], true);
}

/** Para páginas HTML admin-only (p.ej. users.php). */
function ludoteca_require_admin_page(): void
{
    if (!ludoteca_is_admin()) {
        header('Location: index.php');
        exit;
    }
}

/** Para endpoints JSON de gestión de la colección (games/wishlist/loans). */
function ludoteca_require_collector_api(): void
{
    if (!ludoteca_is_collector()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Tu cuenta no tiene permiso para gestionar la colección.']);
        exit;
    }
}

/** Para endpoints JSON admin-only. */
function ludoteca_require_admin_api(): void
{
    if (!ludoteca_is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Solo el administrador puede hacer esto.']);
        exit;
    }
}
