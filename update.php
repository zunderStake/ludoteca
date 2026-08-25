<?php
/**
 * Actualiza el esquema de la base de datos tras subir una nueva versión de los
 * ficheros de Ludoteca, sin tener que volver a pasar por install.php ni
 * reintroducir las credenciales de MySQL. Reutiliza config.php, vuelve a
 * ejecutar los CREATE TABLE IF NOT EXISTS (no tocan las tablas que ya
 * existen) y aplica las migraciones de columnas que falten
 * (ver ludoteca_run_migrations en includes/schema.php).
 *
 * Protegido por el login normal de la aplicación: solo hace falta estar
 * autenticado, no las credenciales de la base de datos.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/repo.php';

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

ludoteca_require_login_page();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/theme.php';

$fileVersion = ludoteca_app_version();
$done = false;
$error = '';

try {
    $currentTheme = repo_get_setting(ludoteca_db(), 'theme') ?? LUDOTECA_DEFAULT_THEME;
} catch (Throwable $e) {
    $currentTheme = LUDOTECA_DEFAULT_THEME;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = ludoteca_db();
        foreach (ludoteca_schema_statements() as $sql) {
            $pdo->exec($sql);
        }
        ludoteca_run_migrations($pdo);
        repo_set_setting($pdo, 'app_version', $fileVersion);
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Actualizar Ludoteca</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/nocturne.css?v=<?= urlencode($fileVersion) ?>">
<link rel="stylesheet" href="assets/css/app.css?v=<?= urlencode($fileVersion) ?>">
<?= ludoteca_theme_style_block($currentTheme) ?>
</head>
<body>
<div class="page">
  <div class="nav">
    <div class="nav-brand">
      <span class="brand-name">Ludoteca</span>
      <span class="brand-sub">actualización</span>
    </div>
  </div>

  <div class="content" style="max-width:560px">
    <h3 class="section-title" style="margin-top:22.4px">Actualizar base de datos</h3>

    <?php if ($done): ?>
      <div class="card" style="border-left:2px solid var(--color-accent-700)">
        <p style="margin:0 0 11.2px">Esquema al día (versión <?= htmlspecialchars($fileVersion) ?>): se han creado
        las tablas o columnas que faltaban (si no faltaba nada, no se ha tocado nada).</p>
        <a class="btn btn-primary" href="index.php">Ir a Ludoteca</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="form-error" style="margin-bottom:11.2px"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <div class="card">
        <p class="text-muted" style="font-size:13px; margin:0 0 11.2px">Usa esto después de subir una versión
        nueva de los ficheros de Ludoteca: vuelve a crear las tablas que falten, añade las columnas nuevas y
        registra la versión <strong><?= htmlspecialchars($fileVersion) ?></strong> como instalada — sin
        pedirte otra vez la conexión a MySQL ni tocar los datos que ya tienes.</p>
        <form method="post">
          <button class="btn btn-primary" type="submit">Actualizar a la versión <?= htmlspecialchars($fileVersion) ?></button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
