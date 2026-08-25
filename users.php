<?php
/**
 * Gestión de usuarios permitidos (solo admin): añadir personas con su correo,
 * contraseña y rol (coleccionista/jugador), cambiar su rol, restablecer su
 * contraseña o quitarles el acceso. Cada persona entra luego con ese correo y esa
 * contraseña en login.php — ver includes/auth.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/includes/helpers.php';

ludoteca_require_login_page();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/theme.php';

$pdo = ludoteca_db();
ludoteca_require_up_to_date_page($pdo);
ludoteca_require_admin_page();

$currentTheme = repo_get_setting($pdo, 'theme') ?? LUDOTECA_DEFAULT_THEME;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = str_or($_POST['action'] ?? '', 'create');

    if ($action === 'create') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $role = str_or($_POST['role'] ?? '', 'jugador');
        if (!in_array($role, ['coleccionista', 'jugador'], true)) {
            $role = 'jugador';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Pon un email válido: será el que use para entrar.';
        }
        if (strcasecmp($email, 'admin') === 0) {
            $errors[] = '"admin" es el correo reservado para el administrador.';
        }
        if ($nombre === '') {
            $errors[] = 'Pon un nombre para mostrar.';
        }
        if (mb_strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Las dos contraseñas no coinciden.';
        }

        if (!$errors) {
            try {
                $playerId = repo_find_or_create_player($pdo, $nombre);
                $pdo->prepare('INSERT INTO app_users (email, nombre, password_hash, role, player_id) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$email, $nombre, password_hash($password, PASSWORD_DEFAULT), $role, $playerId]);
                $success = 'Añadido.';
            } catch (Throwable $e) {
                $errors[] = 'No se pudo añadir (¿ese email ya está en la lista?): ' . $e->getMessage();
            }
        }
    } elseif ($action === 'role') {
        $id = (int) ($_POST['id'] ?? 0);
        $role = str_or($_POST['role'] ?? '', 'jugador');
        if ($id && in_array($role, ['coleccionista', 'jugador'], true)) {
            $pdo->prepare('UPDATE app_users SET role = ? WHERE id = ?')->execute([$role, $id]);
            $success = 'Actualizado.';
        }
    } elseif ($action === 'reset_password') {
        $id = (int) ($_POST['id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        if (mb_strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Las dos contraseñas no coinciden.';
        }
        if ($id && !$errors) {
            $pdo->prepare('UPDATE app_users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            $success = 'Contraseña actualizada.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM app_users WHERE id = ?')->execute([$id]);
            $success = 'Eliminado.';
        }
    }
}

$users = repo_app_users($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ludoteca — usuarios</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/nocturne.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<link rel="stylesheet" href="assets/css/app.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<?= ludoteca_theme_style_block($currentTheme) ?>
</head>
<body>
<div class="page">
  <div class="nav">
    <div class="nav-brand">
      <span class="brand-name">Ludoteca</span>
      <span class="brand-sub">usuarios</span>
    </div>
    <a class="btn btn-secondary" href="index.php" style="margin-left:auto">Volver a Ludoteca</a>
  </div>

  <div class="content" style="max-width:720px">
    <h3 class="section-title" style="margin-top:22.4px">Usuarios permitidos</h3>
    <p class="text-muted" style="font-size:13px">Coleccionista puede crear, editar y eliminar juegos (y todo lo
    demás salvo esta pantalla). Jugador solo puede registrar partidas y usar "Quiero jugar"; el resto lo ve,
    pero no puede tocarlo. El admin entra con el correo "admin" y su propia contraseña (la de <code>install.php</code>);
    cada persona de la lista entra con su propio correo y contraseña.</p>

    <?php foreach ($errors as $err): ?>
      <div class="form-error" style="margin-bottom:8.4px"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <?php if ($success && !$errors): ?>
      <div class="card" style="border-left:2px solid var(--color-accent-2-500); margin-bottom:16.8px; padding:8.4px 16.8px">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <div class="row-list" style="margin-bottom:22.4px">
      <?php foreach ($users as $u): ?>
        <div class="card row-card" style="flex-wrap:wrap">
          <div class="row-main">
            <div class="card-title row-title"><?= htmlspecialchars($u['nombre']) ?></div>
            <div class="row-sub"><?= htmlspecialchars($u['email']) ?></div>
          </div>
          <form method="post" style="display:flex; flex-wrap:wrap; gap:5.6px; align-items:center">
            <input type="hidden" name="action" value="role">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <select name="role" class="input" style="min-width:130px">
              <option value="coleccionista" <?= $u['role'] === 'coleccionista' ? 'selected' : '' ?>>Coleccionista</option>
              <option value="jugador" <?= $u['role'] === 'jugador' ? 'selected' : '' ?>>Jugador</option>
            </select>
            <button class="btn btn-secondary" type="submit">Guardar</button>
          </form>
          <form method="post" style="display:flex; flex-wrap:wrap; gap:5.6px; align-items:center">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <input class="input" type="password" name="password" placeholder="Nueva contraseña" style="min-width:0; width:130px">
            <input class="input" type="password" name="password_confirm" placeholder="Repite" style="min-width:0; width:90px">
            <button class="btn btn-secondary" type="submit">Cambiar contraseña</button>
          </form>
          <form method="post" onsubmit="return confirm('¿Quitar el acceso a este usuario?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <button class="btn btn-icon btn-ghost" type="submit" aria-label="Eliminar">✕</button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (!$users): ?>
        <p class="text-muted empty-state">Todavía no has añadido a nadie. Mientras la lista esté vacía, solo
        existe el admin.</p>
      <?php endif; ?>
    </div>

    <h6 style="color:var(--color-neutral-500)">Añadir usuario</h6>
    <form method="post" class="card" style="gap:11.2px">
      <input type="hidden" name="action" value="create">
      <div class="dialog-grid">
        <div class="field">
          <label for="nombre">Nombre</label>
          <input class="input" id="nombre" name="nombre" placeholder="Ana" required>
        </div>
        <div class="field">
          <label for="email">Correo</label>
          <input class="input" id="email" name="email" type="email" placeholder="ana@gmail.com" required>
        </div>
        <div class="field">
          <label for="role">Permisos</label>
          <select class="input" id="role" name="role">
            <option value="coleccionista">Coleccionista</option>
            <option value="jugador" selected>Jugador</option>
          </select>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <input class="input" id="password" name="password" type="password" minlength="6" required>
        </div>
        <div class="field">
          <label for="password_confirm">Repite la contraseña</label>
          <input class="input" id="password_confirm" name="password_confirm" type="password" minlength="6" required>
        </div>
      </div>
      <div class="dialog-actions" style="justify-content:flex-start">
        <button class="btn btn-primary" type="submit">Añadir</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
