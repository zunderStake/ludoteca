<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (!is_file(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

ludoteca_require_login_page();

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/theme.php';

$pdo = ludoteca_db();

// Si version.txt va por delante de lo aplicado en la BBDD, ni siquiera intentamos
// cargar la SPA: las consultas de la API ya asumen el esquema nuevo y romperían.
ludoteca_require_up_to_date_page($pdo);

$identity = ludoteca_identity();

$currentTheme = repo_get_setting($pdo, 'theme') ?? LUDOTECA_DEFAULT_THEME;
$currentViewMode = repo_get_setting($pdo, 'view_mode') ?? LUDOTECA_DEFAULT_VIEW_MODE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ludoteca</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Narrow:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/nocturne.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<link rel="stylesheet" href="assets/css/app.css?v=<?= urlencode(ludoteca_app_version()) ?>">
<?php // Tema ya resuelto en el propio HTML: sin esto se vería un parpadeo del tema por defecto
      // mientras carga app.js. Va después de app.css a propósito, para ganarle la cascada. ?>
<?= ludoteca_theme_style_block($currentTheme) ?>
<script>
  // Colapsado del menú lateral: es solo densidad visual (no una preferencia de la
  // cuenta), así que se guarda en localStorage, no en BBDD. Se aplica aquí, antes de
  // que se pinte nada, para que no haya un parpadeo del menú abierto cerrándose.
  try {
    if (localStorage.getItem('ludoteca_nav_collapsed') === '1') {
      document.documentElement.classList.add('nav-collapsed');
    }
  } catch (e) {}
</script>
</head>
<body>
<div class="page app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
      <div class="nav-brand">
        <span class="brand-name">Ludoteca</span>
        <span class="brand-sub">inventario · 001</span>
      </div>
      <button type="button" class="btn btn-icon btn-secondary sidebar-toggle" id="sidebar-toggle" title="Colapsar menú" aria-label="Colapsar menú" aria-expanded="true">«</button>
    </div>

    <nav class="nav-tabs" id="nav-tabs"></nav>

    <div class="sidebar-foot">
      <div class="nav-theme" id="nav-theme"></div>
      <div class="nav-user">
        <span class="version-tag nav-user-name"><?= htmlspecialchars($identity['nombre'] ?? 'Admin') ?></span>
        <span id="nav-version"></span>
        <?php if ($identity['role'] === 'admin'): ?>
          <a class="btn btn-icon btn-secondary" href="users.php" title="Usuarios" aria-label="Usuarios">👤</a>
        <?php endif; ?>
        <?php if ($identity['role'] !== 'jugador'): ?>
          <button class="btn btn-primary" id="btn-add-game" title="Añadir juego"><span class="btn-label">Añadir juego</span></button>
        <?php endif; ?>
        <a class="btn btn-icon btn-secondary" href="logout.php" title="Salir" aria-label="Salir">⏻</a>
      </div>
    </div>
  </aside>

  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

  <div class="main">
    <div class="mobile-topbar">
      <button type="button" class="btn btn-icon btn-secondary" id="mobile-nav-toggle" title="Abrir menú" aria-label="Abrir menú">☰</button>
      <span class="brand-name">Ludoteca</span>
    </div>
    <div class="content" id="view-root">
      <p class="text-muted loading-note">Cargando colección…</p>
    </div>
  </div>
</div>

<div id="dialog-root"></div>

<script>window.__LUDOTECA_PREFS__ = <?= json_encode([
    'theme' => $currentTheme,
    'view_mode' => $currentViewMode,
    'role' => $identity['role'],
    'userId' => $identity['userId'],
    'nombre' => $identity['nombre'],
], JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="assets/js/app.js?v=<?= urlencode(ludoteca_app_version()) ?>"></script>
</body>
</html>
