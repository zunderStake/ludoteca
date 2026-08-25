<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/theme.php';

$config = require_once __DIR__ . '/config.php';

// El tema es global (no depende de quién inicie sesión), así que se aplica también
// aquí para que no haya un salto visual entre login.php e index.php.
try {
    $currentTheme = repo_get_setting(ludoteca_db(), 'theme') ?? LUDOTECA_DEFAULT_THEME;
} catch (Throwable $e) {
    $currentTheme = LUDOTECA_DEFAULT_THEME;
}

ludoteca_start_session();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    $ok = false;
    if (strcasecmp($email, 'admin') === 0) {
        if (password_verify($pass, $config['app_password_hash'] ?? '')) {
            $_SESSION['ludoteca_auth'] = true;
            ludoteca_set_identity(null, 'admin', null);
            $ok = true;
        }
    } elseif ($email !== '') {
        $pdo = ludoteca_db();
        $user = repo_find_app_user_by_email($pdo, $email);
        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['ludoteca_auth'] = true;
            ludoteca_set_identity((int) $user['id'], $user['role'], $user['nombre']);
            $ok = true;
        }
    }

    if ($ok) {
        header('Location: index.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
    ludoteca_clear_identity();
    unset($_SESSION['ludoteca_auth']);
}

if (ludoteca_is_logged_in()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ludoteca — acceso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/nocturne.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<link rel="stylesheet" href="assets/css/app.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<?= ludoteca_theme_style_block($currentTheme) ?>
</head>
<body>
<div class="page" style="display:grid; place-items:center; min-height:100vh; padding-bottom:0">
  <form method="post" class="card" style="width:min(360px, 100%); gap:16.8px">
    <div class="nav-brand" style="margin:0 0 5.6px">
      <span class="brand-name">Ludoteca</span>
    </div>
    <?php if ($error): ?>
      <div class="form-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="field">
      <label for="email">Correo</label>
      <input class="input" id="email" type="text" name="email" placeholder="Correo electrónico" autofocus required>
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input class="input" id="password" type="password" name="password" required>
    </div>
    <button class="btn btn-primary btn-block" type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
