<?php
/** Consultas de lectura reutilizadas por los endpoints de la API. */

require_once __DIR__ . '/helpers.php';

function repo_get_setting(PDO $pdo, string $name): ?string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE name = ?');
    $stmt->execute([$name]);
    $value = $stmt->fetchColumn();
    return $value === false ? null : $value;
}

function repo_set_setting(PDO $pdo, string $name, string $value): void
{
    $pdo->prepare(
        'INSERT INTO settings (name, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    )->execute([$name, $value]);
}

/**
 * true si los ficheros desplegados (version.txt) van por delante de lo aplicado en la
 * BBDD (tabla settings). El resto de repo_*() ya asume el esquema de la versión actual
 * de los ficheros, así que hay que comprobar esto ANTES de llamarlas: si la BBDD se
 * quedó atrás, esas consultas pueden referenciar columnas que todavía no existen y
 * romper con un error de MySQL en vez de un aviso claro. Ante cualquier duda (p.ej. la
 * tabla settings tampoco existe todavía) se considera que sí hace falta actualizar.
 */
function ludoteca_needs_update(PDO $pdo): bool
{
    try {
        $dbVersion = repo_get_setting($pdo, 'app_version') ?? '0.0.0';
        return version_compare(ludoteca_app_version(), $dbVersion, '>');
    } catch (Throwable $e) {
        return true;
    }
}

/** Para páginas HTML: manda a update.php si la BBDD no está al día. */
function ludoteca_require_up_to_date_page(PDO $pdo): void
{
    if (ludoteca_needs_update($pdo)) {
        header('Location: update.php');
        exit;
    }
}

/** Para endpoints JSON: responde con un error claro si la BBDD no está al día. */
function ludoteca_require_up_to_date_api(PDO $pdo): void
{
    if (ludoteca_needs_update($pdo)) {
        json_error('La base de datos necesita actualizarse a la versión nueva. Entra en update.php y pulsa "Actualizar ahora".', 409);
    }
}

function repo_games(PDO $pdo): array
{
    return $pdo->query(
        'SELECT g.id, g.nombre, g.editorial, g.tipo, g.puntuacion, g.precio, g.jugadores, g.duracion,
                g.bgg_id, g.imagen_url, g.edad_minima, g.premium, g.es_expansion, g.base_game_id,
                base.nombre AS base_game_nombre
         FROM games g
         LEFT JOIN games base ON base.id = g.base_game_id
         ORDER BY g.nombre'
    )->fetchAll();
}

function repo_players(PDO $pdo): array
{
    return $pdo->query('SELECT id, nombre FROM players ORDER BY nombre')->fetchAll();
}

function repo_plays(PDO $pdo): array
{
    $plays = $pdo->query(
        "SELECT p.id, p.game_id, p.fecha, p.duracion, p.ganador_id, p.resultado, p.empate,
                g.nombre AS juego_nombre, pl.nombre AS ganador_nombre
         FROM plays p
         LEFT JOIN games g ON g.id = p.game_id
         LEFT JOIN players pl ON pl.id = p.ganador_id
         ORDER BY p.fecha DESC, p.id DESC"
    )->fetchAll();

    if (!$plays) {
        return [];
    }

    $ids = array_column($plays, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT pp.play_id, pl.id AS player_id, pl.nombre, pp.es_ganador
         FROM play_players pp JOIN players pl ON pl.id = pp.player_id
         WHERE pp.play_id IN ($placeholders)
         ORDER BY pl.nombre"
    );
    $stmt->execute($ids);
    $byPlay = [];
    $ganadoresByPlay = [];
    foreach ($stmt->fetchAll() as $row) {
        $byPlay[$row['play_id']][] = ['id' => (int) $row['player_id'], 'nombre' => $row['nombre']];
        if ((int) $row['es_ganador'] === 1) {
            $ganadoresByPlay[$row['play_id']][] = $row['nombre'];
        }
    }

    foreach ($plays as &$p) {
        $p['empate'] = (bool) $p['empate'];
        $p['jugadores'] = $byPlay[$p['id']] ?? [];
        $p['ganadores'] = $ganadoresByPlay[$p['id']] ?? [];
    }
    return $plays;
}

function repo_wishlist(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id, nombre, editorial, tipo, puntuacion, precio, jugadores, duracion, prioridad, bgg_id,
                imagen_url, edad_minima, premium
         FROM wishlist ORDER BY FIELD(prioridad, "Alta","Media","Baja"), nombre'
    )->fetchAll();
}

function repo_loans(PDO $pdo): array
{
    return $pdo->query(
        'SELECT l.id, l.game_id, l.persona, l.fecha_prestamo,
                DATEDIFF(CURDATE(), l.fecha_prestamo) AS dias,
                g.nombre AS juego_nombre
         FROM loans l LEFT JOIN games g ON g.id = l.game_id
         ORDER BY l.fecha_prestamo'
    )->fetchAll();
}

/** Busca o crea un jugador por nombre y devuelve su id. */
function repo_find_or_create_player(PDO $pdo, string $nombre): int
{
    $nombre = trim($nombre);
    $stmt = $pdo->prepare('SELECT id FROM players WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare('INSERT INTO players (nombre) VALUES (?)')->execute([$nombre]);
    return (int) $pdo->lastInsertId();
}

function repo_app_users(PDO $pdo): array
{
    return $pdo->query('SELECT id, email, nombre, role, player_id FROM app_users ORDER BY nombre')->fetchAll();
}

function repo_find_app_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, nombre, password_hash, role, player_id FROM app_users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function repo_find_app_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, nombre, role, player_id FROM app_users WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Propuestas "quiero jugar" con sus destinatarios y si el usuario actual ya la descartó. */
function repo_want_to_play(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT w.id, w.game_id, w.requested_by_user_id, w.requested_by_nombre, w.created_at,
                g.nombre AS juego_nombre
         FROM want_to_play w
         LEFT JOIN games g ON g.id = w.game_id
         ORDER BY w.created_at DESC, w.id DESC"
    )->fetchAll();

    if (!$rows) {
        return [];
    }

    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT t.want_to_play_id, t.user_id, t.dismissed_at, t.accepted_at, u.nombre
         FROM want_to_play_targets t JOIN app_users u ON u.id = t.user_id
         WHERE t.want_to_play_id IN ($placeholders)
         ORDER BY u.nombre"
    );
    $stmt->execute($ids);
    $byProposal = [];
    foreach ($stmt->fetchAll() as $row) {
        $byProposal[$row['want_to_play_id']][] = [
            'user_id' => (int) $row['user_id'],
            'nombre' => $row['nombre'],
            'dismissed' => $row['dismissed_at'] !== null,
            'accepted' => $row['accepted_at'] !== null,
        ];
    }

    foreach ($rows as &$r) {
        $r['targets'] = $byProposal[$r['id']] ?? [];
    }
    return $rows;
}
