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

$games = repo_games($pdo);
$plays = repo_plays($pdo);
$wishlist = repo_wishlist($pdo);
$loans = repo_loans($pdo);
$players = repo_players($pdo);
$appUsers = repo_app_users($pdo);
$wantToPlay = repo_want_to_play($pdo);
$identity = ludoteca_identity();

$totalPrecio = array_sum(array_map(fn($g) => (float) $g['precio'], $games));
$numGames = count($games);

$fileVersion = ludoteca_app_version();
$dbVersion = repo_get_setting($pdo, 'app_version') ?? '0.0.0';

json_ok([
    'games' => $games,
    'plays' => $plays,
    'wishlist' => $wishlist,
    'loans' => $loans,
    'players' => $players,
    'tipos' => LUDOTECA_TIPOS,
    'stats' => [
        'juegos' => $numGames,
        'wishlist' => count($wishlist),
        'valor_total' => $totalPrecio,
        'partidas' => count($plays),
        'precio_medio' => $numGames ? $totalPrecio / $numGames : 0,
    ],
    'version' => [
        'current' => $fileVersion,
        'installed' => $dbVersion,
        'updateAvailable' => version_compare($fileVersion, $dbVersion, '>'),
    ],
    'preferences' => [
        'theme' => repo_get_setting($pdo, 'theme') ?? LUDOTECA_DEFAULT_THEME,
        'view_mode' => repo_get_setting($pdo, 'view_mode') ?? LUDOTECA_DEFAULT_VIEW_MODE,
    ],
    'currentUser' => [
        'role' => $identity['role'],
        'userId' => $identity['userId'],
        'nombre' => $identity['nombre'],
        'isCollector' => ludoteca_is_collector(),
        'isAdmin' => ludoteca_is_admin(),
    ],
    // Solo id/nombre/rol: el email no hace falta en el cliente (solo se usa en users.php).
    'appUsers' => array_map(fn ($u) => [
        'id' => (int) $u['id'], 'nombre' => $u['nombre'], 'role' => $u['role'],
    ], $appUsers),
    'wantToPlay' => $wantToPlay,
]);
