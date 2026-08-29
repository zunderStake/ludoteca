<?php
/** Esquema SQL de Ludoteca. Usado por install.php; idempotente (CREATE TABLE IF NOT EXISTS). */

function ludoteca_schema_statements(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS games (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(190) NOT NULL,
            editorial VARCHAR(120) NOT NULL DEFAULT 'Sin editorial',
            tipo VARCHAR(60) NOT NULL DEFAULT 'Eurogame',
            puntuacion DECIMAL(3,1) NOT NULL DEFAULT 0,
            precio DECIMAL(8,2) NOT NULL DEFAULT 0,
            jugadores VARCHAR(20) NOT NULL DEFAULT '2-4',
            duracion VARCHAR(20) NOT NULL DEFAULT '60 min',
            bgg_id INT UNSIGNED NULL,
            imagen_url VARCHAR(500) NULL,
            edad_minima TINYINT UNSIGNED NOT NULL DEFAULT 0,
            premium TINYINT(1) NOT NULL DEFAULT 0,
            es_expansion TINYINT(1) NOT NULL DEFAULT 0,
            base_game_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_nombre (nombre),
            CONSTRAINT fk_games_base FOREIGN KEY (base_game_id) REFERENCES games(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS players (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(80) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS plays (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            game_id INT UNSIGNED NOT NULL,
            fecha DATE NOT NULL,
            ganador_id INT UNSIGNED NULL,
            resultado ENUM('Victoria','Derrota') NULL DEFAULT NULL,
            empate TINYINT(1) NOT NULL DEFAULT 0,
            duracion INT UNSIGNED NOT NULL DEFAULT 60,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_fecha (fecha),
            CONSTRAINT fk_plays_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            CONSTRAINT fk_plays_ganador FOREIGN KEY (ganador_id) REFERENCES players(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS play_players (
            play_id INT UNSIGNED NOT NULL,
            player_id INT UNSIGNED NOT NULL,
            es_ganador TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (play_id, player_id),
            CONSTRAINT fk_pp_play FOREIGN KEY (play_id) REFERENCES plays(id) ON DELETE CASCADE,
            CONSTRAINT fk_pp_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS wishlist (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(190) NOT NULL,
            editorial VARCHAR(120) NOT NULL DEFAULT 'Sin editorial',
            tipo VARCHAR(60) NOT NULL DEFAULT 'Eurogame',
            puntuacion DECIMAL(3,1) NOT NULL DEFAULT 0,
            precio DECIMAL(8,2) NOT NULL DEFAULT 0,
            jugadores VARCHAR(20) NOT NULL DEFAULT '2-4',
            duracion VARCHAR(20) NOT NULL DEFAULT '60 min',
            prioridad ENUM('Alta','Media','Baja') NOT NULL DEFAULT 'Media',
            bgg_id INT UNSIGNED NULL,
            imagen_url VARCHAR(500) NULL,
            edad_minima TINYINT UNSIGNED NOT NULL DEFAULT 0,
            premium TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS loans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            game_id INT UNSIGNED NOT NULL,
            persona VARCHAR(80) NOT NULL,
            fecha_prestamo DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_loans_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(60) PRIMARY KEY,
            value VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Usuarios permitidos además del admin local (que sigue siendo la contraseña de
        // config.php, con "admin" como su "correo" especial). Cada uno entra con su
        // propio correo + contraseña en login.php: es autenticación real por persona,
        // no solo identificación.
        "CREATE TABLE IF NOT EXISTS app_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            nombre VARCHAR(80) NOT NULL,
            password_hash VARCHAR(255) NOT NULL DEFAULT '',
            role ENUM('coleccionista','jugador') NOT NULL DEFAULT 'jugador',
            player_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_email (email),
            CONSTRAINT fk_app_users_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS want_to_play (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            game_id INT UNSIGNED NOT NULL,
            requested_by_user_id INT UNSIGNED NULL,
            requested_by_nombre VARCHAR(80) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_wtp_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            CONSTRAINT fk_wtp_requester FOREIGN KEY (requested_by_user_id) REFERENCES app_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS want_to_play_targets (
            want_to_play_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            dismissed_at DATETIME NULL,
            accepted_at DATETIME NULL,
            PRIMARY KEY (want_to_play_id, user_id),
            CONSTRAINT fk_wtpt_wtp FOREIGN KEY (want_to_play_id) REFERENCES want_to_play(id) ON DELETE CASCADE,
            CONSTRAINT fk_wtpt_user FOREIGN KEY (user_id) REFERENCES app_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
}

/**
 * Cambios de esquema posteriores a la primera versión. CREATE TABLE IF NOT EXISTS no
 * añade columnas a una tabla que ya existía sin ellas, así que en cada instalación
 * comprobamos columna a columna y añadimos las que falten. Seguro de re-ejecutar.
 */
function ludoteca_run_migrations(PDO $pdo): void
{
    $columnExists = function (PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    };

    $migrations = [
        ['wishlist', 'tipo', "ALTER TABLE wishlist ADD COLUMN tipo VARCHAR(60) NOT NULL DEFAULT 'Eurogame' AFTER editorial"],
        ['wishlist', 'puntuacion', "ALTER TABLE wishlist ADD COLUMN puntuacion DECIMAL(3,1) NOT NULL DEFAULT 0 AFTER tipo"],
        ['wishlist', 'jugadores', "ALTER TABLE wishlist ADD COLUMN jugadores VARCHAR(20) NOT NULL DEFAULT '2-4' AFTER precio"],
        ['wishlist', 'duracion', "ALTER TABLE wishlist ADD COLUMN duracion VARCHAR(20) NOT NULL DEFAULT '60 min' AFTER jugadores"],
        ['games', 'edad_minima', "ALTER TABLE games ADD COLUMN edad_minima TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER imagen_url"],
        ['games', 'premium', "ALTER TABLE games ADD COLUMN premium TINYINT(1) NOT NULL DEFAULT 0 AFTER edad_minima"],
        ['games', 'es_expansion', "ALTER TABLE games ADD COLUMN es_expansion TINYINT(1) NOT NULL DEFAULT 0 AFTER premium"],
        ['games', 'base_game_id', "ALTER TABLE games ADD COLUMN base_game_id INT UNSIGNED NULL AFTER es_expansion"],
        ['wishlist', 'edad_minima', "ALTER TABLE wishlist ADD COLUMN edad_minima TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER imagen_url"],
        ['wishlist', 'premium', "ALTER TABLE wishlist ADD COLUMN premium TINYINT(1) NOT NULL DEFAULT 0 AFTER edad_minima"],
        ['plays', 'resultado', "ALTER TABLE plays ADD COLUMN resultado ENUM('Victoria','Derrota') NULL DEFAULT NULL AFTER ganador_id"],
        ['plays', 'empate', "ALTER TABLE plays ADD COLUMN empate TINYINT(1) NOT NULL DEFAULT 0 AFTER resultado"],
        ['play_players', 'es_ganador', "ALTER TABLE play_players ADD COLUMN es_ganador TINYINT(1) NOT NULL DEFAULT 0"],
        ['app_users', 'password_hash', "ALTER TABLE app_users ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER nombre"],
        ['want_to_play_targets', 'accepted_at', "ALTER TABLE want_to_play_targets ADD COLUMN accepted_at DATETIME NULL AFTER dismissed_at"],
    ];

    foreach ($migrations as [$table, $column, $sql]) {
        if (!$columnExists($pdo, $table, $column)) {
            $pdo->exec($sql);
        }
    }

    $fkExists = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = 'games' AND constraint_name = 'fk_games_base'"
    )->fetchColumn();
    if ((int) $fkExists === 0) {
        $pdo->exec('ALTER TABLE games ADD CONSTRAINT fk_games_base FOREIGN KEY (base_game_id) REFERENCES games(id) ON DELETE SET NULL');
    }

    // Desde 1.7.1, crear un usuario crea (o vincula) su jugador automáticamente
    // (ver users.php). Esto repara a los usuarios que ya existían de antes: cualquier
    // app_user sin player_id recibe un jugador con su mismo nombre, igual que si se
    // acabara de crear. Idempotente: no toca a quien ya tenga jugador vinculado.
    $orphanUsers = $pdo->query('SELECT id, nombre FROM app_users WHERE player_id IS NULL')->fetchAll();
    foreach ($orphanUsers as $orphan) {
        $playerId = repo_find_or_create_player($pdo, $orphan['nombre']);
        $pdo->prepare('UPDATE app_users SET player_id = ? WHERE id = ?')->execute([$playerId, $orphan['id']]);
    }
}
