<?php
/**
 * Instalador de Ludoteca.
 * Pide los datos de conexión a MySQL, comprueba la conexión, crea el
 * esquema (CREATE TABLE IF NOT EXISTS, así que se puede re-ejecutar sin
 * perder datos) y escribe config.php con la conexión y la contraseña
 * de acceso a la aplicación.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$configPath = __DIR__ . '/config.php';
$alreadyInstalled = is_file($configPath);
// Se carga siempre que ya exista, no solo al reinstalar: sirve tanto para rellenar el
// formulario con los valores actuales como para poder dejar campos en blanco al
// reinstalar y que mantengan lo que ya había.
$existing = $alreadyInstalled ? require $configPath : null;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $port = trim((string) ($_POST['db_port'] ?? '3306'));
    $name = trim((string) ($_POST['db_name'] ?? ''));
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $appPass = (string) ($_POST['app_pass'] ?? '');
    $appPassConfirm = (string) ($_POST['app_pass_confirm'] ?? '');
    $overwrite = isset($_POST['overwrite']);
    $currentAppPass = (string) ($_POST['current_app_pass'] ?? '');
    $bggToken = trim((string) ($_POST['bgg_token'] ?? ''));

    if ($alreadyInstalled && !$overwrite) {
        $errors[] = 'Ya existe una configuración. Marca "Sobrescribir configuración existente" si quieres reinstalar.';
    }
    // Al reinstalar, cualquiera de estos campos se puede dejar en blanco para
    // mantener lo que ya había en config.php — así una reinstalación solo para,
    // por ejemplo, pegar el token de BGG no obliga a re-escribir el resto.
    $appPasswordHash = null;
    if ($alreadyInstalled && $overwrite) {
        if (!password_verify($currentAppPass, $existing['app_password_hash'] ?? '')) {
            $errors[] = 'Para reinstalar, indica la contraseña de acceso actual de la aplicación.';
        }
        if ($pass === '') {
            $pass = $existing['db']['pass'] ?? '';
        }
        if ($bggToken === '' && !empty($existing['bgg_token'])) {
            $bggToken = $existing['bgg_token'];
        }
        if ($appPass === '' && $appPassConfirm === '') {
            $appPasswordHash = $existing['app_password_hash'] ?? null;
        }
    }
    if ($name === '' || $user === '') {
        $errors[] = 'Indica al menos el nombre de la base de datos y el usuario.';
    }
    if ($appPasswordHash === null) {
        if ($appPass === '' || strlen($appPass) < 6) {
            $errors[] = 'La contraseña de acceso a la aplicación debe tener al menos 6 caracteres.';
        }
        if ($appPass !== $appPassConfirm) {
            $errors[] = 'Las dos contraseñas de acceso no coinciden.';
        }
    }

    $pdo = null;
    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port ?: '3306');
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbNameEscaped = str_replace('`', '``', $name);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameEscaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbNameEscaped}`");
        } catch (Throwable $e) {
            $errors[] = 'No se pudo conectar o crear la base de datos: ' . $e->getMessage();
        }
    }

    if (!$errors && $pdo !== null) {
        try {
            foreach (ludoteca_schema_statements() as $sql) {
                $pdo->exec($sql);
            }
            ludoteca_run_migrations($pdo);
            repo_set_setting($pdo, 'app_version', ludoteca_app_version());
        } catch (Throwable $e) {
            $errors[] = 'Conexión correcta, pero falló la creación del esquema: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        $config = [
            'db' => [
                'host' => $host,
                'port' => $port ?: '3306',
                'name' => $name,
                'user' => $user,
                'pass' => $pass,
                'charset' => 'utf8mb4',
            ],
            'app_password_hash' => $appPasswordHash ?? password_hash($appPass, PASSWORD_DEFAULT),
            'bgg_token' => $bggToken,
        ];

        $php = "<?php\n\n/** Generado por install.php el " . date('Y-m-d H:i:s') . ". */\n\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($configPath, $php) === false) {
            $errors[] = 'No se pudo escribir config.php. Comprueba los permisos de escritura de la carpeta.';
        } else {
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar Ludoteca</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/nocturne.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<link rel="stylesheet" href="assets/css/app.css?v=<?= urlencode(ludoteca_app_version()) ?>">
</head>
<body>
<div class="page">
  <div class="nav">
    <div class="nav-brand">
      <span class="brand-name">Ludoteca</span>
      <span class="brand-sub">instalación</span>
    </div>
  </div>

  <div class="content" style="max-width:640px">
    <h3 class="section-title" style="margin-top:22.4px">Configurar Ludoteca</h3>

    <?php if ($success): ?>
      <div class="card" style="border-left:2px solid var(--color-accent-700)">
        <p style="margin:0 0 11.2px">Instalación completada. La base de datos y las tablas están listas y las
        credenciales se han guardado en <code>config.php</code>.</p>
        <a class="btn btn-primary" href="index.php">Ir a Ludoteca</a>
      </div>
    <?php else: ?>

      <?php if ($alreadyInstalled): ?>
        <div class="card" style="border-left:2px solid var(--color-accent-700); margin-bottom:16.8px">
          <p style="margin:0">Ya existe un <code>config.php</code>. Para reinstalar, marca la casilla
          "Sobrescribir configuración existente" al final del formulario.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($errors as $err): ?>
        <div class="form-error" style="margin-bottom:11.2px"><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <form method="post" class="card" style="gap:16.8px">
        <div>
          <h6 style="color:var(--color-neutral-500)">Base de datos MySQL</h6>
          <div class="dialog-grid">
            <div class="field">
              <label for="db_host">Host</label>
              <input class="input" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $existing['db']['host'] ?? 'localhost') ?>">
            </div>
            <div class="field">
              <label for="db_port">Puerto</label>
              <input class="input" id="db_port" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? $existing['db']['port'] ?? '3306') ?>">
            </div>
            <div class="field" style="grid-column:1/-1">
              <label for="db_name">Nombre de la base de datos</label>
              <input class="input" id="db_name" name="db_name" placeholder="ludoteca" value="<?= htmlspecialchars($_POST['db_name'] ?? $existing['db']['name'] ?? '') ?>" required>
            </div>
            <div class="field">
              <label for="db_user">Usuario</label>
              <input class="input" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? $existing['db']['user'] ?? '') ?>" required>
            </div>
            <div class="field">
              <label for="db_pass"><?= $alreadyInstalled ? 'Contraseña (déjala en blanco para no cambiarla)' : 'Contraseña' ?></label>
              <input class="input" id="db_pass" type="password" name="db_pass" value="">
            </div>
          </div>
          <p class="text-muted" style="font-size:12px">En Hestia, crea antes la base de datos y su usuario desde
          <em>BBDD</em> y usa aquí esas mismas credenciales (el usuario ya tendrá permisos sobre ella).
          <?= $alreadyInstalled ? ' Al reinstalar, host/puerto/nombre/usuario ya salen rellenos con los que tenías; solo hace falta tocar lo que quieras cambiar.' : '' ?></p>
        </div>

        <div>
          <h6 style="color:var(--color-neutral-500)">Cuenta de administrador</h6>
          <div class="dialog-grid">
            <div class="field">
              <label for="app_pass"><?= $alreadyInstalled ? 'Nueva contraseña de admin (en blanco = no cambiarla)' : 'Contraseña de admin' ?></label>
              <input class="input" id="app_pass" type="password" name="app_pass" <?= $alreadyInstalled ? '' : 'required' ?> minlength="6">
            </div>
            <div class="field">
              <label for="app_pass_confirm">Repite la contraseña</label>
              <input class="input" id="app_pass_confirm" type="password" name="app_pass_confirm" <?= $alreadyInstalled ? '' : 'required' ?> minlength="6">
            </div>
          </div>
          <p class="text-muted" style="font-size:12px">Esta es la contraseña de la cuenta admin: en el login se
          entra con el correo <code>admin</code> y esta contraseña. Desde <em>Usuarios</em> (👤) podrás añadir
          después a más personas, cada una con su propio correo y contraseña.</p>
        </div>

        <div>
          <h6 style="color:var(--color-neutral-500)">Carátulas desde BoardGameGeek</h6>
          <div class="field">
            <label for="bgg_token">Token de API de BoardGameGeek (opcional, pero necesario para las carátulas)</label>
            <input class="input" id="bgg_token" name="bgg_token" placeholder="<?= $alreadyInstalled && !empty($existing['bgg_token']) ? 'Ya tienes uno guardado; déjalo en blanco para mantenerlo' : 'Pega aquí tu token' ?>" value="">
          </div>
          <p class="text-muted" style="font-size:12px">Desde 2025 BGG exige una aplicación registrada para usar su
          API. Inicia sesión en BGG, regístrala en
          <a href="https://boardgamegeek.com/applications/create" target="_blank" rel="noopener">boardgamegeek.com/applications/create</a>
          y pega aquí el token que te den. Sin él, el buscador de juegos seguirá funcionando pero sin
          autocompletar datos ni carátula; puedes dejarlo en blanco ahora y volver a <code>install.php</code>
          más adelante para añadirlo (marcando "Sobrescribir configuración existente").</p>
        </div>

        <?php if ($alreadyInstalled): ?>
          <div>
            <label class="radio" style="font-size:13px">
              <input type="checkbox" name="overwrite" value="1">
              <span class="dot"></span>
              Sobrescribir configuración existente
            </label>
            <div class="field" style="margin-top:8.4px">
              <label for="current_app_pass">Contraseña de acceso actual (para confirmar la reinstalación)</label>
              <input class="input" id="current_app_pass" type="password" name="current_app_pass">
            </div>
          </div>
        <?php endif; ?>

        <div class="dialog-actions" style="justify-content:flex-start">
          <button class="btn btn-primary" type="submit">Instalar</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
