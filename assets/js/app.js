(() => {
  'use strict';

  const viewRoot = document.getElementById('view-root');
  const dialogRoot = document.getElementById('dialog-root');
  const navTabs = document.getElementById('nav-tabs');
  const navVersion = document.getElementById('nav-version');
  const navTheme = document.getElementById('nav-theme');
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const mobileNavToggle = document.getElementById('mobile-nav-toggle');
  const sidebarBackdrop = document.getElementById('sidebar-backdrop');
  const initialPrefs = window.__LUDOTECA_PREFS__ || {};

  // ---------- menú lateral: colapsable en escritorio, cajón deslizante en móvil ----------
  // Ninguno de los dos estados es una preferencia de la cuenta (a diferencia de
  // tema/vista): son densidad visual del dispositivo actual, así que solo el
  // colapsado se recuerda en localStorage; el cajón móvil siempre arranca cerrado.
  const isMobileNav = () => window.matchMedia('(max-width: 760px)').matches;
  function setSidebarOpen(open) {
    document.documentElement.classList.toggle('nav-mobile-open', open);
  }
  if (sidebarToggle) {
    const syncToggleLabel = () => {
      const collapsed = document.documentElement.classList.contains('nav-collapsed');
      sidebarToggle.textContent = collapsed ? '»' : '«';
      sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
      sidebarToggle.title = collapsed ? 'Expandir menú' : 'Colapsar menú';
      sidebarToggle.setAttribute('aria-label', sidebarToggle.title);
    };
    syncToggleLabel();
    sidebarToggle.addEventListener('click', () => {
      if (isMobileNav()) { setSidebarOpen(false); return; }
      const collapsed = document.documentElement.classList.toggle('nav-collapsed');
      try { localStorage.setItem('ludoteca_nav_collapsed', collapsed ? '1' : '0'); } catch (e) {}
      syncToggleLabel();
    });
  }
  mobileNavToggle?.addEventListener('click', () => setSidebarOpen(true));
  sidebarBackdrop?.addEventListener('click', () => setSidebarOpen(false));

  // ---------- themes ----------
  // Cuatro paletas de 5 colores; las rampas 100-900 se derivan por interpolación.
  // Misma matemática que includes/theme.php (ludoteca_theme_vars) — el PHP resuelve el
  // tema guardado directamente en el <head> para que no haya parpadeo al cargar, y este
  // JS reaplica lo mismo en caliente cuando el usuario cambia de tema.
  const THEMES = [
    { id: 'crema', label: 'Crema', bg: '#f3ecdd', surface: '#faf6ec', ink: '#262231', accent: '#6d5aa6', gold: '#b8892f' },
    { id: 'pergamino', label: 'Pergamino', bg: '#ece2cd', surface: '#f7f1e0', ink: '#2b2418', accent: '#a8792a', gold: '#6d5aa6' },
    { id: 'tinta', label: 'Tinta', bg: '#1c1726', surface: '#251e33', ink: '#ece7f5', accent: '#a08ce0', gold: '#d1a94e' },
    { id: 'nocturno', label: 'Nocturno', bg: '#161826', surface: '#1e2032', ink: '#e9e9ed', accent: '#9184d9', gold: '#c2b273' },
  ];

  function mixColor(a, b, t) {
    const h = (c) => [1, 3, 5].map((i) => parseInt(c.slice(i, i + 2), 16));
    const [r1, g1, b1] = h(a), [r2, g2, b2] = h(b);
    const p = (n) => Math.round(n).toString(16).padStart(2, '0');
    return '#' + p(r1 + (r2 - r1) * t) + p(g1 + (g2 - g1) * t) + p(b1 + (b2 - b1) * t);
  }
  function colorRamp(bg, base, ink, prefix) {
    const near = [0.10, 0.26, 0.48, 0.72];
    const far = [0.22, 0.42, 0.60, 0.78];
    const out = { [prefix]: base, [prefix + '-500']: base };
    [900, 800, 700, 600].forEach((step, i) => { out[prefix + '-' + step] = mixColor(bg, base, near[i]); });
    [400, 300, 200, 100].forEach((step, i) => { out[prefix + '-' + step] = mixColor(base, ink, far[i]); });
    return out;
  }
  function themeVars(t) {
    const v = {
      '--color-bg': t.bg, '--color-surface': t.surface, '--color-text': t.ink,
      '--color-accent': t.accent, '--color-accent-2': t.gold,
      '--color-divider': mixColor(t.bg, t.ink, 0.16),
    };
    Object.assign(v, colorRamp(t.bg, mixColor(t.bg, t.ink, 0.62), t.ink, '--color-neutral'));
    Object.assign(v, colorRamp(t.bg, t.accent, t.ink, '--color-accent'));
    Object.assign(v, colorRamp(t.bg, t.gold, t.ink, '--color-accent-2'));
    v['--shadow-sm'] = '0 0 0 1px ' + mixColor(t.bg, t.ink, 0.16);
    v['--shadow-md'] = '0 0 0 1px ' + mixColor(t.bg, t.ink, 0.26) + ', 0 4px 14px rgba(0,0,0,0.14)';
    v['--shadow-lg'] = '0 0 0 1px ' + mixColor(t.bg, t.ink, 0.38) + ', 0 14px 36px rgba(0,0,0,0.22)';
    return v;
  }
  function applyTheme(themeId) {
    const t = THEMES.find((x) => x.id === themeId) || THEMES[0];
    const vars = themeVars(t);
    Object.entries(vars).forEach(([k, val]) => document.documentElement.style.setProperty(k, val));
    state.theme = t.id;
  }

  const TABS = [
    ['coleccion', 'Colección', '🎲'],
    ['partidas', 'Partidas', '🕹️'],
    ['jugadores', 'Jugadores', '🧑‍🤝‍🧑'],
    ['ranking', 'Ranking', '🏆'],
    ['deseos', 'Deseos', '💜'],
    ['quierojugar', 'Quiero jugar', '🙋'],
    ['prestamos', 'Préstamos', '📦'],
  ];

  function emptyGameForm() {
    return {
      nombre: '', editorial: '', tipo: 'Eurogame', puntuacion: '8', precio: '', jugadores: '', duracion: '',
      edad_minima: '', premium: false, es_expansion: false, base_game_id: '',
      imagen_url: null, bgg_id: null,
    };
  }
  function emptyWishForm() {
    return {
      nombre: '', editorial: '', tipo: 'Eurogame', puntuacion: '', precio: '', jugadores: '', duracion: '',
      prioridad: 'Media', edad_minima: '', premium: false,
    };
  }
  function emptyBgg() {
    return { query: '', results: [], searching: false, picked: null, error: '' };
  }
  function emptyPlayForm() {
    return { gameId: '', jugadores: [], ganador: '', resultado: 'Victoria', duracion: '60', fecha: '' };
  }
  function emptyWantPlayForm() {
    return { gameId: '', targetIds: [] };
  }
  function emptyRandomForm() {
    return { tipo: 'Todos' };
  }

  // ---------- juego aleatorio (carrusel) ----------
  // Ancho de cada carátula del carrusel + separación: tiene que coincidir con
  // .reel-item/.reel-track en app.css para que el cálculo de dónde frenar sea exacto.
  const REEL_ITEM_W = 132;
  const REEL_GAP = 11.2;
  const REEL_STEP = REEL_ITEM_W + REEL_GAP;
  const REEL_FILLER_COUNT = 36;
  // Carátulas de más después del resultado: si no, el ganador queda como el último
  // elemento de la cinta y, al centrarlo, a su derecha no hay nada — parece que se ha
  // acabado la lista en vez de que el carrusel haya parado en medio de una tira larga.
  const REEL_TRAILING_COUNT = 10;
  const REEL_SPIN_MS = 3600;

  const AGE_BUCKETS = [
    { id: '0-5', label: '0-5 años', test: (e) => e <= 5 },
    { id: '6-10', label: '6-10 años', test: (e) => e >= 6 && e <= 10 },
    { id: '11-15', label: '11-15 años', test: (e) => e >= 11 && e <= 15 },
    { id: '16+', label: '16+ años', test: (e) => e >= 16 },
  ];
  function ageBucketLabel(edad) {
    const b = AGE_BUCKETS.find((x) => x.test(Number(edad) || 0));
    return b ? b.label : AGE_BUCKETS[0].label;
  }

  // Búsqueda de complementos imprimibles en 3D para el juego (organizadores, insertos,
  // bandejas...). No hay API pública de MakerWorld: es un enlace de búsqueda normal.
  function makerworldUrl(nombre) {
    return 'https://makerworld.com/es/search/models?keyword=' + encodeURIComponent(nombre);
  }

  const state = {
    loaded: false,
    view: 'coleccion',
    theme: initialPrefs.theme || 'crema',
    viewMode: initialPrefs.view_mode || 'caratula',
    games: [], plays: [], wishlist: [], loans: [], players: [], tipos: [], stats: {}, version: null,
    appUsers: [], wantToPlay: [],
    currentUser: {
      role: initialPrefs.role || 'admin',
      userId: initialPrefs.userId ?? null,
      nombre: initialPrefs.nombre ?? null,
      isCollector: (initialPrefs.role || 'admin') !== 'jugador',
      isAdmin: (initialPrefs.role || 'admin') === 'admin',
    },
    query: '', tipoFilter: 'Todos', edadFilter: 'Todas', sort: 'nombre',
    dialog: null, // null | 'add' | 'play' | 'detail' | 'wish' | 'loan' | 'wantplay'
    detailId: null,
    editingGameId: null,
    editingPlayId: null,
    formError: '',
    addForm: emptyGameForm(),
    wishForm: emptyWishForm(),
    bgg: emptyBgg(),
    bggTarget: 'game', // 'game' | 'wish' — which open dialog the BGG picker fills in
    playForm: emptyPlayForm(),
    playError: '',
    newPlayerName: '',
    wantPlayForm: emptyWantPlayForm(),
    wantPlayError: '',
    randomForm: emptyRandomForm(),
    randomSpin: null,
    randomError: '',
  };

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  function money(n) {
    const v = Math.round(Number(n) || 0);
    return v.toLocaleString('es-ES') + ' €';
  }

  function moneyPrecise(n) {
    const v = Number(n) || 0;
    return v.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }

  function fechaEs(iso) {
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }).replace('.', '');
  }

  async function api(url, options = {}) {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...options,
    });
    if (res.status === 401) {
      window.location.href = 'login.php';
      throw new Error('No autenticado');
    }
    const data = await res.json().catch(() => ({ ok: false, error: 'Respuesta inválida del servidor.' }));
    if (!res.ok || !data.ok) {
      throw new Error(data.error || 'Error de red.');
    }
    return data;
  }

  async function loadState() {
    const data = await api('api/state.php');
    state.games = data.games;
    state.plays = data.plays;
    state.wishlist = data.wishlist;
    state.loans = data.loans;
    state.players = data.players;
    state.tipos = data.tipos;
    state.stats = data.stats;
    state.version = data.version || null;
    state.appUsers = data.appUsers || [];
    state.wantToPlay = data.wantToPlay || [];
    if (data.currentUser) state.currentUser = data.currentUser;
    if (data.preferences) {
      state.viewMode = data.preferences.view_mode || state.viewMode;
      // El <head> ya aplicó el tema guardado al cargar la página (ver includes/theme.php);
      // solo reaplicamos aquí si otra pestaña lo cambió mientras tanto.
      if (data.preferences.theme && data.preferences.theme !== state.theme) {
        applyTheme(data.preferences.theme);
      }
    }
    state.loaded = true;
  }

  async function refresh() {
    await loadState();
    render();
  }

  // Cambiar de tema o de vista ya se nota al instante en la propia pantalla; el guardado
  // en BBDD va detrás sin bloquear nada, para que vuelva a cargar igual la próxima vez
  // que se inicie sesión (aunque sea en otro dispositivo).
  async function savePreference(name, value) {
    try {
      await api('api/preferences.php', { method: 'POST', body: JSON.stringify({ name, value }) });
    } catch (e) {
      console.warn('No se pudo guardar la preferencia', name, e.message);
    }
  }

  // ---------- derived data ----------

  function playCount(gameId) {
    return state.plays.filter((p) => p.game_id === gameId).length;
  }

  // Expansiones de un juego base (relación inversa a es_expansion/base_game_id).
  function expansionsOf(gameId) {
    return state.games.filter((g) => g.es_expansion && Number(g.base_game_id) === Number(gameId));
  }

  function expansionLinksMarkup(list) {
    if (!list.length) return '';
    return `
      <div class="expansions-block">
        <div class="expansions-label">Expansiones:</div>
        <div class="expansions-links">
          ${list.map((g) => `<a href="#" class="expansion-link" data-action="open-detail" data-id="${g.id}">${esc(g.nombre)}</a>`).join('')}
        </div>
      </div>`;
  }

  function baseGameNoticeMarkup(g) {
    if (!g.es_expansion) return '';
    const base = state.games.find((x) => x.id === Number(g.base_game_id));
    return `
      <div class="basegame-notice">
        <div class="basegame-label">Se necesita el juego base:</div>
        ${base
          ? `<a href="#" class="basegame-link" data-action="open-detail" data-id="${base.id}">${esc(base.nombre)}</a>`
          : `<span class="basegame-link basegame-link-missing">${esc(g.base_game_nombre || 'Sin especificar')}</span>`}
      </div>`;
  }

  // Propuestas "quiero jugar" pendientes. Admin y coleccionista ven el total de
  // propuestas con algún destinatario sin descartar (pueden ver y gestionar toda la
  // colección, así que también toda la actividad de "quiero jugar"); jugador solo
  // ve las que le proponen a él.
  function pendingWantToPlay() {
    const uid = state.currentUser.userId;
    if (!state.currentUser.isCollector && uid) {
      return state.wantToPlay.filter((p) => p.targets.some((t) => t.user_id === uid && !t.dismissed));
    }
    return state.wantToPlay.filter((p) => p.targets.some((t) => !t.dismissed));
  }

  function visibleGames() {
    const q = state.query.trim().toLowerCase();
    let list = state.games.filter((g) =>
      (state.tipoFilter === 'Todos' || g.tipo === state.tipoFilter) &&
      (state.edadFilter === 'Todas' || ageBucketLabel(g.edad_minima) === state.edadFilter) &&
      (!q || g.nombre.toLowerCase().includes(q) || g.editorial.toLowerCase().includes(q))
    );
    const sorters = {
      nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es'),
      puntuacion: (a, b) => b.puntuacion - a.puntuacion,
      precio: (a, b) => b.precio - a.precio,
      partidas: (a, b) => playCount(b.id) - playCount(a.id),
    };
    return [...list].sort(sorters[state.sort] || sorters.nombre);
  }

  function playersFromPlays() {
    const names = new Set();
    state.plays.forEach((p) => p.jugadores.forEach((j) => names.add(j.nombre)));
    return [...names];
  }

  // Partidas competitivas: gana quien es p.ganador_nombre. Partidas cooperativas (p.resultado
  // presente) no tienen un ganador individual: si el resultado es Victoria, cuenta como
  // victoria para todos los que participaron.
  function playerWonPlay(p, nombre) {
    if (p.resultado) {
      return p.resultado === 'Victoria' && p.jugadores.some((j) => j.nombre === nombre);
    }
    return p.ganador_nombre === nombre;
  }

  function playerStatsRows() {
    return playersFromPlays().map((nombre) => {
      const mine = state.plays.filter((p) => p.jugadores.some((j) => j.nombre === nombre));
      const wins = mine.filter((p) => playerWonPlay(p, nombre)).length;
      const favCount = {};
      mine.forEach((p) => { favCount[p.game_id] = (favCount[p.game_id] || 0) + 1; });
      const favId = Object.keys(favCount).sort((a, b) => favCount[b] - favCount[a])[0];
      const favGame = state.games.find((g) => String(g.id) === String(favId));
      return {
        nombre,
        initial: nombre[0].toUpperCase(),
        played: mine.length,
        wins,
        winPct: mine.length ? Math.round((wins / mine.length) * 100) : 0,
        favorito: favGame ? favGame.nombre : '—',
      };
    }).sort((a, b) => b.played - a.played);
  }

  function computeRanking() {
    const games = state.games;
    const plays = state.plays;

    let mostPlayed = null;
    let mostPlayedCount = 0;
    games.forEach((g) => {
      const c = playCount(g.id);
      if (c > mostPlayedCount) { mostPlayedCount = c; mostPlayed = g; }
    });

    const types = [...new Set(games.map((g) => g.tipo))];
    const championByType = types.map((tipo) => {
      const gameIdsOfType = new Set(games.filter((g) => g.tipo === tipo).map((g) => g.id));
      const winsByPlayer = {};
      plays.filter((p) => gameIdsOfType.has(p.game_id)).forEach((p) => {
        const candidates = p.resultado ? p.jugadores.map((j) => j.nombre) : [p.ganador_nombre].filter(Boolean);
        candidates.forEach((nombre) => {
          if (playerWonPlay(p, nombre)) winsByPlayer[nombre] = (winsByPlayer[nombre] || 0) + 1;
        });
      });
      const entries = Object.entries(winsByPlayer).sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0], 'es'));
      return entries.length ? { tipo, jugador: entries[0][0], victorias: entries[0][1] } : null;
    }).filter(Boolean);

    const bestGameByPlayer = playersFromPlays().map((nombre) => {
      const winsByGame = {};
      plays.forEach((p) => {
        if (playerWonPlay(p, nombre)) winsByGame[p.game_id] = (winsByGame[p.game_id] || 0) + 1;
      });
      const entries = Object.entries(winsByGame).sort((a, b) => b[1] - a[1] || Number(a[0]) - Number(b[0]));
      if (!entries.length) return { nombre, juego: '—', victorias: 0 };
      const game = games.find((g) => String(g.id) === entries[0][0]);
      return { nombre, juego: game ? game.nombre : '—', victorias: entries[0][1] };
    }).sort((a, b) => b.victorias - a.victorias);

    const profitability = games.map((g) => {
      const c = playCount(g.id);
      return { nombre: g.nombre, precio: Number(g.precio), partidas: c, perPlay: c > 0 ? Number(g.precio) / c : null };
    }).sort((a, b) => {
      if (a.perPlay === null && b.perPlay === null) return a.nombre.localeCompare(b.nombre, 'es');
      if (a.perPlay === null) return 1;
      if (b.perPlay === null) return -1;
      return a.perPlay - b.perPlay;
    });

    return { mostPlayed, mostPlayedCount, championByType, bestGameByPlayer, profitability };
  }

  // ---------- rendering ----------

  function render() {
    renderTabs();
    renderVersionBadge();
    renderThemeSwatches();
    if (!state.loaded) {
      viewRoot.innerHTML = '<p class="text-muted loading-note">Cargando colección…</p>';
      return;
    }
    const renderers = {
      coleccion: renderColeccion, partidas: renderPartidas, jugadores: renderJugadores,
      ranking: renderRanking, deseos: renderDeseos, quierojugar: renderQuieroJugar, prestamos: renderPrestamos,
    };
    const pending = pendingWantToPlay();
    // Para jugador son invitaciones suyas ("esperándote"); para admin/coleccionista es
    // el total de la colección, así que el aviso se redacta como actividad, no como
    // algo dirigido a él en persona.
    const pendingLabel = state.currentUser.isCollector
      ? (pending.length === 1 ? '1 propuesta' : pending.length + ' propuestas') + ' de "Quiero jugar" sin resolver.'
      : (pending.length === 1 ? '1 propuesta' : pending.length + ' propuestas') + ' de "Quiero jugar" esperándote.';
    const banner = pending.length && state.view !== 'quierojugar' ? `
      <div class="wtp-banner">
        <span>${pendingLabel}</span>
        <button type="button" class="btn btn-secondary" data-action="go-view" data-view="quierojugar">Ver</button>
      </div>` : '';
    viewRoot.innerHTML = banner + (renderers[state.view] || renderColeccion)();
    renderDialog();
  }

  function renderThemeSwatches() {
    if (!navTheme) return;
    navTheme.innerHTML = `
      <span class="theme-label">Tema</span>
      <div class="theme-swatches">
        ${THEMES.map((t) => `
          <button type="button" class="theme-swatch ${t.id === state.theme ? 'active' : ''}" data-action="set-theme" data-theme="${t.id}"
            title="${esc(t.label)}" aria-label="${esc(t.label)}" aria-pressed="${t.id === state.theme}">
            <span style="background:${t.bg}"></span><span style="background:${t.accent}"></span>
            <span style="background:${t.gold}"></span><span style="background:${t.ink}"></span>
          </button>`).join('')}
      </div>`;
  }

  function renderTabs() {
    const counts = {
      coleccion: state.games.length, partidas: state.plays.length,
      jugadores: playersFromPlays().length, deseos: state.wishlist.length, prestamos: state.loans.length,
      quierojugar: pendingWantToPlay().length,
    };
    navTabs.innerHTML = TABS.map(([id, label, icon]) => `
      <button class="btn ${id === 'quierojugar' && counts[id] ? 'has-alert' : ''}" data-action="go-view" data-view="${id}" title="${esc(label)}" ${state.view === id ? 'aria-current="page"' : ''}>
        <span class="nav-icon">${icon}</span><span class="nav-label">${esc(label)}</span>${counts[id] !== undefined ? `<span class="count">${counts[id]}</span>` : ''}
      </button>`).join('');
  }

  function renderVersionBadge() {
    if (!navVersion) return;
    const v = state.version;
    if (!v) { navVersion.innerHTML = ''; return; }
    if (v.updateAvailable) {
      navVersion.innerHTML = `<a class="btn btn-secondary" href="update.php" style="border-color:var(--color-accent); color:var(--color-accent)">Actualizar a la versión ${esc(v.current)}</a>`;
      return;
    }
    // Enlace "oculto": mismo aspecto que el texto plano de siempre, pero descarga un
    // backup completo (ver backup.php) — a propósito sin botón ni icono aparte. Solo
    // para quien puede gestionar la colección; para jugador es texto plano sin enlace.
    navVersion.innerHTML = state.currentUser.isCollector
      ? `<a class="version-tag" href="backup.php" title="Descargar copia de seguridad">v${esc(v.current)}</a>`
      : `<span class="version-tag">v${esc(v.current)}</span>`;
  }

  function coverMarkup(imagenUrl, alt, sizeClass) {
    if (imagenUrl) {
      return `<img src="${esc(imagenUrl)}" alt="${esc(alt)}" loading="lazy">`;
    }
    return `<span class="placeholder">Sin carátula</span>`;
  }

  function renderColeccion() {
    const s = state.stats;
    const list = visibleGames();
    const tipoOptions = ['Todos', ...new Set(state.games.map((g) => g.tipo))];

    const kpis = [
      { label: 'Juegos', value: String(s.juegos ?? 0), hint: `${s.wishlist ?? 0} en la lista de deseos` },
      { label: 'Valor total', value: money(s.valor_total), hint: 'Precio pagado, sin envíos' },
      { label: 'Partidas', value: String(s.partidas ?? 0), hint: 'Registradas este año' },
      { label: 'Precio medio', value: money(s.precio_medio), hint: 'Por juego de la colección' },
    ];

    return `
    <section style="animation: riseIn 0.25s ease both">
      <div class="kpis">
        ${kpis.map((k) => `
          <div class="kpi">
            <span class="kpi-label">${esc(k.label)}</span>
            <span class="kpi-value">${esc(k.value)}</span>
            <span class="kpi-hint">${esc(k.hint)}</span>
          </div>`).join('')}
      </div>

      <div class="filterbar">
        <div class="field">
          <label for="q">Buscar</label>
          <input id="q" class="input" type="search" placeholder="Nombre o editorial" value="${esc(state.query)}" data-action="query">
        </div>
        <div class="field select">
          <label for="tipo">Tipo</label>
          <select id="tipo" class="input" data-action="tipo-filter">
            ${tipoOptions.map((t) => `<option value="${esc(t)}" ${t === state.tipoFilter ? 'selected' : ''}>${esc(t)}</option>`).join('')}
          </select>
        </div>
        <div class="field select">
          <label for="edad">Edad</label>
          <select id="edad" class="input" data-action="edad-filter">
            ${['Todas', ...AGE_BUCKETS.map((b) => b.label)].map((l) => `<option value="${esc(l)}" ${l === state.edadFilter ? 'selected' : ''}>${esc(l)}</option>`).join('')}
          </select>
        </div>
        <div class="field select">
          <label for="orden">Orden</label>
          <select id="orden" class="input" data-action="sort">
            ${[['nombre', 'Nombre'], ['puntuacion', 'Puntuación'], ['precio', 'Precio'], ['partidas', 'Más jugados']]
              .map(([v, l]) => `<option value="${v}" ${v === state.sort ? 'selected' : ''}>${l}</option>`).join('')}
          </select>
        </div>
        <div class="field select">
          <label>Vista</label>
          <div class="seg">
            ${[['caratula', 'Carátula'], ['ficha', 'Ficha']].map(([id, label]) => `
              <button type="button" class="btn seg-opt ${state.viewMode === id ? 'active' : ''}" data-action="set-view-mode" data-mode="${id}">${label}</button>`).join('')}
          </div>
        </div>
        <div class="field select">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-secondary" data-action="open-random">🎲 Juego aleatorio</button>
        </div>
      </div>

      ${list.length
        ? (state.viewMode === 'caratula' ? renderPosterGrid(list) : renderFichaGrid(list))
        : `<p class="text-muted empty-state">Ningún juego coincide con la búsqueda.</p>`}
    </section>`;
  }

  function renderFichaGrid(list) {
    return `<div class="game-grid">
      ${list.map((g) => {
        const pc = playCount(g.id);
        return `
        <div class="card game-card" role="button" tabindex="0" data-action="open-detail" data-id="${g.id}">
          <div class="game-cover">${coverMarkup(g.imagen_url, g.nombre)}</div>
          <div class="game-head">
            <div style="flex:1; min-width:0">
              <div class="game-name">${esc(g.nombre)}</div>
              <div class="game-editorial">${esc(g.editorial)}</div>
              ${g.premium ? '<span class="tag tag-premium">Premium</span>' : ''}
            </div>
            <div class="game-score">
              <div class="val">${Number(g.puntuacion).toFixed(1)}</div>
              <div class="max">/10</div>
            </div>
          </div>
          <div class="game-tags">
            <span class="tag tag-outline tag-tipo">${esc(g.tipo)}</span>
            <span class="tag tag-outline tag-dim">${esc(g.jugadores)}</span>
            <span class="tag tag-outline tag-dim">${esc(g.duracion)}</span>
            <span class="tag tag-outline tag-dim">${esc(ageBucketLabel(g.edad_minima))}</span>
          </div>
          ${g.es_expansion ? baseGameNoticeMarkup(g) : expansionLinksMarkup(expansionsOf(g.id))}
          <div class="game-foot">
            <span>${pc === 1 ? '1 partida' : pc + ' partidas'}</span>
            <span>${money(g.precio)}</span>
          </div>
        </div>`;
      }).join('')}
    </div>`;
  }

  function renderPosterGrid(list) {
    return `<div class="poster-grid">
      ${list.map((g, idx) => {
        const pc = playCount(g.id);
        const facts = [
          ['Tipo', g.tipo], ['Jugadores', g.jugadores], ['Duración', g.duracion],
          ['Edad', ageBucketLabel(g.edad_minima)], ['Partidas', String(pc)],
        ];
        return `
        <div class="poster-tile" role="button" tabindex="0" data-action="open-detail" data-id="${g.id}" style="--i:${idx % 24}">
          ${g.imagen_url
            ? `<img class="poster-cover" src="${esc(g.imagen_url)}" alt="${esc(g.nombre)}" loading="lazy">`
            : '<span class="poster-noimg">Sin carátula</span>'}
          ${g.premium ? '<span class="poster-premium">Premium</span>' : ''}
          <div class="poster-panel">
            <div class="poster-panel-head">
              <div style="flex:1; min-width:0">
                <div class="poster-name">${esc(g.nombre)}</div>
                <div class="poster-editorial">${esc(g.editorial)}</div>
              </div>
              <div class="poster-score">${Number(g.puntuacion).toFixed(1)}</div>
            </div>
            <div class="poster-facts">
              ${facts.map(([label, value]) => `
                <div>
                  <div class="poster-fact-label">${esc(label)}</div>
                  <div class="poster-fact-value">${esc(value)}</div>
                </div>`).join('')}
            </div>
            ${g.es_expansion ? baseGameNoticeMarkup(g) : expansionLinksMarkup(expansionsOf(g.id))}
          </div>
        </div>`;
      }).join('')}
    </div>`;
  }

  function renderPartidas() {
    const rows = [...state.plays];
    return `
    <section style="animation: riseIn 0.25s ease both">
      <div class="section-header">
        <h3>Partidas</h3>
        <button class="btn btn-primary" data-action="open-play">Registrar partida</button>
      </div>
      <div class="table-scroll"><table class="table">
        <thead>
          <tr><th>Fecha</th><th>Juego</th><th>Jugadores</th><th>Ganador</th><th class="col-duracion">Duración</th><th></th></tr>
        </thead>
        <tbody>
          ${rows.map((p) => `
            <tr>
              <td class="col-fecha">${esc(fechaEs(p.fecha))}</td>
              <td class="col-juego">${esc(p.juego_nombre || '—')}</td>
              <td class="col-jugadores">${esc(p.jugadores.map((j) => j.nombre).join(', '))}</td>
              <td>${p.resultado
                ? `<span class="tag ${p.resultado === 'Victoria' ? 'tag-accent' : 'tag-neutral'}">${esc(p.resultado)}</span>`
                : `<span class="tag tag-outline tag-tipo">${esc(p.ganador_nombre || '—')}</span>`}</td>
              <td class="col-duracion">${esc(p.duracion)} min</td>
              <td><button class="btn btn-icon btn-ghost" data-action="edit-play" data-id="${p.id}" aria-label="Editar partida" title="Editar">✎</button></td>
            </tr>`).join('') || `<tr><td colspan="6" class="text-muted" style="padding:22.4px 0">Todavía no hay partidas registradas.</td></tr>`}
        </tbody>
      </table></div>
    </section>`;
  }

  function renderJugadores() {
    const rows = playerStatsRows();
    return `
    <section style="animation: riseIn 0.25s ease both">
      <h3 class="section-title" style="margin:22.4px 0 16.8px">Jugadores</h3>
      ${rows.length ? `<div class="player-grid">
        ${rows.map((pl) => `
          <div class="card player-card">
            <div class="player-top">
              <div class="player-avatar">${esc(pl.initial)}</div>
              <div>
                <div class="card-title player-name">${esc(pl.nombre)}</div>
                <div class="player-count">${pl.played} PARTIDAS</div>
              </div>
            </div>
            <div>
              <div class="player-win-row">
                <span class="label">Victorias</span>
                <span>${pl.wins} de ${pl.played}</span>
              </div>
              <div class="win-bar"><div class="fill" style="width:${pl.winPct}%"></div></div>
            </div>
            <div class="player-fav">FAVORITO: ${esc(pl.favorito)}</div>
          </div>`).join('')}
      </div>` : `<p class="text-muted empty-state">Registra una partida para ver estadísticas de jugadores.</p>`}
    </section>`;
  }

  function renderRanking() {
    const r = computeRanking();
    return `
    <section style="animation: riseIn 0.25s ease both">
      <h3 class="section-title" style="margin:22.4px 0 16.8px">Ranking</h3>

      <div class="kpis">
        <div class="kpi">
          <span class="kpi-label">Más jugado</span>
          <span class="kpi-value" style="font-size:20px; line-height:1.25">${r.mostPlayed ? esc(r.mostPlayed.nombre) : '—'}</span>
          <span class="kpi-hint">${r.mostPlayed ? (r.mostPlayedCount === 1 ? '1 partida' : r.mostPlayedCount + ' partidas') : 'Registra partidas para ver esta estadística'}</span>
        </div>
      </div>

      <h6 class="rank-subhead">Campeón por tipo de juego</h6>
      ${r.championByType.length ? `<div class="table-scroll"><table class="table">
        <thead><tr><th>Tipo</th><th>Jugador</th><th class="col-duracion">Victorias</th></tr></thead>
        <tbody>
          ${r.championByType.map((c) => `
            <tr>
              <td class="col-juego">${esc(c.tipo)}</td>
              <td>${esc(c.jugador)}</td>
              <td class="col-duracion">${c.victorias}</td>
            </tr>`).join('')}
        </tbody>
      </table></div>` : `<p class="text-muted empty-state">Registra partidas con ganador para ver esta tabla.</p>`}

      <h6 class="rank-subhead">Mejor juego de cada jugador</h6>
      ${r.bestGameByPlayer.length ? `<div class="table-scroll"><table class="table">
        <thead><tr><th>Jugador</th><th>Juego con más victorias</th><th class="col-duracion">Victorias</th></tr></thead>
        <tbody>
          ${r.bestGameByPlayer.map((b) => `
            <tr>
              <td class="col-juego">${esc(b.nombre)}</td>
              <td>${esc(b.juego)}</td>
              <td class="col-duracion">${b.victorias}</td>
            </tr>`).join('')}
        </tbody>
      </table></div>` : `<p class="text-muted empty-state">Registra partidas para ver esta tabla.</p>`}

      <h6 class="rank-subhead">Rentabilidad — precio ÷ partidas jugadas</h6>
      ${r.profitability.length ? `<div class="table-scroll"><table class="table">
        <thead><tr><th>Juego</th><th class="col-duracion">Precio</th><th class="col-duracion">Partidas</th><th class="col-duracion">€ / partida</th></tr></thead>
        <tbody>
          ${r.profitability.map((p) => `
            <tr>
              <td class="col-juego">${esc(p.nombre)}</td>
              <td class="col-duracion">${money(p.precio)}</td>
              <td class="col-duracion">${p.partidas}</td>
              <td class="col-duracion">${p.perPlay === null ? 'Sin partidas' : moneyPrecise(p.perPlay)}</td>
            </tr>`).join('')}
        </tbody>
      </table></div>` : `<p class="text-muted empty-state">Añade juegos a la colección para ver esta tabla.</p>`}
    </section>`;
  }

  function renderDeseos() {
    const total = state.wishlist.reduce((s, w) => s + Number(w.precio), 0);
    const canEdit = state.currentUser.isCollector;
    return `
    <section style="animation: riseIn 0.25s ease both">
      <div class="section-header">
        <h3>Lista de deseos</h3>
        ${canEdit ? '<button class="btn btn-primary" data-action="open-wish">Añadir a la lista</button>' : ''}
        <span class="row-sub" style="margin-left:8px">${money(total)} pendiente</span>
      </div>
      <div class="row-list">
        ${state.wishlist.map((w) => `
          <div class="card row-card">
            <div class="row-main">
              <div class="card-title row-title">${esc(w.nombre)}</div>
              <div class="row-sub">${esc(w.editorial)}</div>
            </div>
            <span class="tag tag-outline">${esc(w.prioridad)}</span>
            <span class="row-price">${money(w.precio)}</span>
            ${canEdit ? `
              <button class="btn btn-secondary" data-action="wish-buy" data-id="${w.id}">Ya lo tengo</button>
              <button class="btn btn-icon btn-ghost" data-action="wish-remove" data-id="${w.id}" aria-label="Quitar">✕</button>
            ` : ''}
          </div>`).join('') || `<p class="text-muted empty-state">Tu lista de deseos está vacía.</p>`}
      </div>
    </section>`;
  }

  function renderPrestamos() {
    const canEdit = state.currentUser.isCollector;
    return `
    <section style="animation: riseIn 0.25s ease both">
      <div class="section-header">
        <h3>Préstamos</h3>
        ${canEdit ? '<button class="btn btn-primary" data-action="open-loan">Registrar préstamo</button>' : ''}
      </div>
      <div class="row-list">
        ${state.loans.map((l) => `
          <div class="card row-card">
            <div class="row-main">
              <div class="card-title row-title">${esc(l.juego_nombre || 'Juego retirado')}</div>
              <div class="row-sub">En casa de ${esc(l.persona)}</div>
            </div>
            <span class="tag ${l.dias > 30 ? 'tag-accent' : 'tag-neutral'}">${l.dias} días fuera</span>
            ${canEdit ? `<button class="btn btn-secondary" data-action="loan-return" data-id="${l.id}">Devuelto</button>` : ''}
          </div>`).join('') || `<p class="text-muted">Ahora mismo no has prestado nada.</p>`}
      </div>
    </section>`;
  }

  function renderQuieroJugar() {
    const uid = state.currentUser.userId;
    return `
    <section style="animation: riseIn 0.25s ease both">
      <div class="section-header">
        <h3>Quiero jugar</h3>
        <button class="btn btn-primary" data-action="open-wantplay">Proponer</button>
      </div>
      <div class="row-list">
        ${state.wantToPlay.map((p) => {
          const myTarget = uid ? p.targets.find((t) => t.user_id === uid) : null;
          const isRequester = uid !== null && p.requested_by_user_id === uid;
          const canCancel = state.currentUser.isAdmin || isRequester;
          // Mientras no haya respondido (ni aceptado ni descartado) puede elegir; una
          // vez responde, la tarjeta solo muestra su estado.
          const canRespond = myTarget && !myTarget.dismissed && !myTarget.accepted;
          const iAccepted = myTarget && myTarget.accepted;
          const hasAccepted = p.targets.some((t) => t.accepted);
          return `
          <div class="card row-card">
            <div class="row-main">
              <div class="card-title row-title">${esc(p.juego_nombre || 'Juego retirado')}</div>
              <div class="row-sub">Propone: ${esc(p.requested_by_nombre)}</div>
              <div class="wtp-targets">
                ${p.targets.map((t) => {
                  const cls = t.accepted ? 'tag-accent' : t.dismissed ? 'tag-neutral' : 'tag-outline';
                  const mark = t.accepted ? ' ✓' : t.dismissed ? ' ✕' : '';
                  return `<span class="tag ${cls}">${esc(t.nombre)}${mark}</span>`;
                }).join('')}
              </div>
              ${iAccepted ? '<div class="wtp-status">Aceptada — pendiente de registrar la partida</div>' : ''}
            </div>
            ${canRespond ? `
              <button class="btn btn-primary" data-action="wtp-accept" data-id="${p.id}">Aceptar</button>
              <button class="btn btn-secondary" data-action="wtp-dismiss" data-id="${p.id}">Descartar</button>
            ` : ''}
            ${hasAccepted ? `<button class="btn btn-primary" data-action="play-from-wtp" data-id="${p.id}">Registrar</button>` : ''}
            ${canCancel ? `<button class="btn btn-icon btn-ghost" data-action="wtp-delete" data-id="${p.id}" aria-label="Cancelar">✕</button>` : ''}
          </div>`;
        }).join('') || `<p class="text-muted empty-state">Nadie ha propuesto jugar a nada todavía.</p>`}
      </div>
    </section>`;
  }

  // ---------- dialogs ----------

  function renderDialog() {
    if (state.dialog === 'add') return renderAddDialog();
    if (state.dialog === 'play') return renderPlayDialog();
    if (state.dialog === 'detail') return renderDetailDialog();
    if (state.dialog === 'wish') return renderWishDialog();
    if (state.dialog === 'loan') return renderLoanDialog();
    if (state.dialog === 'wantplay') return renderWantPlayDialog();
    if (state.dialog === 'random') return renderRandomDialog();
    dialogRoot.innerHTML = '';
  }

  function reelItemHtml(g) {
    return `
      <div class="reel-item">
        <div class="reel-cover">${g.imagen_url ? `<img src="${esc(g.imagen_url)}" alt="${esc(g.nombre)}" loading="eager">` : '<span class="placeholder">Sin carátula</span>'}</div>
        <div class="reel-name">${esc(g.nombre)}</div>
      </div>`;
  }

  function renderRandomDialog() {
    const rf = state.randomForm;
    const spin = state.randomSpin;
    const tipoOptions = ['Todos', ...state.tipos];

    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" style="width:min(680px, 100%)" data-stop>
        <div class="dialog-title">Juego aleatorio</div>

        ${!spin ? `
          <div class="field">
            <label for="rnd-tipo">¿De qué tipo quieres el juego?</label>
            <select id="rnd-tipo" class="input" data-action="random-tipo">
              ${tipoOptions.map((t) => `<option value="${esc(t)}" ${t === rf.tipo ? 'selected' : ''}>${esc(t)}</option>`).join('')}
            </select>
          </div>
          ${state.randomError ? `<div class="form-error">${esc(state.randomError)}</div>` : ''}
          <div class="dialog-actions">
            <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
            <button class="btn btn-primary" data-action="random-spin">¡Girar!</button>
          </div>
        ` : `
          <div class="reel-viewport" id="reel-viewport">
            <div class="reel-marker"></div>
            <div class="reel-track" id="reel-track" style="${spin.phase === 'done' && spin.translateX != null ? `transform:translateX(-${spin.translateX}px)` : ''}">
              ${spin.reel.map((g) => reelItemHtml(g)).join('')}
            </div>
          </div>
          ${spin.phase === 'done' ? `
            <div class="dialog-actions" style="margin-top:16.8px">
              <button class="btn btn-secondary" data-action="close-dialog">Cerrar</button>
              <button class="btn btn-secondary" data-action="random-again">Girar otra vez</button>
              <button class="btn btn-primary" data-action="random-view" data-id="${spin.resultGame.id}">Ver ficha</button>
            </div>
          ` : `<p class="text-muted" style="text-align:center; margin:11.2px 0 0">Girando…</p>`}
        `}
      </div>
    </div>`;

    if (spin && spin.phase === 'spinning' && !spin.animationStarted) {
      spin.animationStarted = true;
      startReelAnimation();
    }
  }

  function startReelAnimation() {
    const track = document.getElementById('reel-track');
    const viewport = document.getElementById('reel-viewport');
    const spin = state.randomSpin;
    if (!track || !viewport || !spin) return;
    const targetIndex = spin.targetIndex;
    const targetCenter = targetIndex * REEL_STEP + REEL_ITEM_W / 2;
    const translateX = targetCenter - viewport.clientWidth / 2;
    spin.translateX = translateX;

    // Fijar el punto de partida sin transición y forzar un reflow antes de activar
    // la transición: si no, algunos navegadores aplican el transform final de golpe
    // en vez de animarlo (la cinta "salta" en lugar de deslizarse).
    track.style.transition = 'none';
    track.style.transform = 'translateX(0px)';
    track.getBoundingClientRect();

    requestAnimationFrame(() => {
      track.style.transition = `transform ${REEL_SPIN_MS}ms cubic-bezier(0.1, 0.7, 0.25, 1)`;
      track.getBoundingClientRect();
      track.style.transform = `translateX(-${translateX}px)`;

      let finished = false;
      const finish = () => {
        if (finished) return;
        finished = true;
        track.removeEventListener('transitionend', onEnd);
        if (state.randomSpin === spin) { spin.phase = 'done'; render(); }
      };
      const onEnd = (ev) => { if (ev.propertyName === 'transform') finish(); };
      track.addEventListener('transitionend', onEnd);
      // Red de seguridad: si por lo que sea transitionend no llega, no se queda
      // girando para siempre.
      setTimeout(finish, REEL_SPIN_MS + 300);
    });
  }

  function renderWantPlayDialog() {
    const f = state.wantPlayForm;
    const gameOptions = [...state.games].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    if (!f.gameId && gameOptions.length) f.gameId = String(gameOptions[0].id);
    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" data-stop>
        <div class="dialog-title">Proponer partida</div>

        <div class="field">
          <label for="wtp-juego">Juego</label>
          <select id="wtp-juego" class="input" data-action="wtp-game">
            ${gameOptions.map((o) => `<option value="${o.id}" ${String(o.id) === String(f.gameId) ? 'selected' : ''}>${esc(o.nombre)}</option>`).join('')}
          </select>
        </div>

        <div class="field">
          <label>¿Con quién quieres jugar?</label>
          <div class="chip-row">
            ${state.appUsers.filter((u) => u.id !== state.currentUser.userId).map((u) => `
              <button type="button" class="chip ${f.targetIds.includes(u.id) ? 'selected' : ''}" data-action="toggle-wtp-target" data-id="${u.id}">${esc(u.nombre)}</button>`).join('') || '<span class="text-muted" style="font-size:13px">Todavía no hay otros usuarios añadidos (ver Usuarios).</span>'}
          </div>
        </div>

        ${state.wantPlayError ? `<div class="form-error">${esc(state.wantPlayError)}</div>` : ''}

        <div class="dialog-actions">
          <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
          <button class="btn btn-primary" data-action="save-wantplay">Proponer</button>
        </div>
      </div>
    </div>`;
  }

  function renderBggAssist(idPrefix) {
    const bgg = state.bgg;
    return `
        <div class="field">
          <label for="${idPrefix}-bgg-q">Buscar en BoardGameGeek (opcional, rellena datos e imagen)</label>
          <div class="bgg-search-row">
            <input id="${idPrefix}-bgg-q" class="input" placeholder="Ej. Brass Birmingham" value="${esc(bgg.query)}" data-action="bgg-query">
            <button class="btn btn-secondary" data-action="bgg-search" type="button">${bgg.searching ? 'Buscando…' : 'Buscar'}</button>
          </div>
          ${bgg.error ? `<div class="form-error">${esc(bgg.error)}</div>` : ''}
          ${bgg.results.length ? `<div class="bgg-results">
            ${bgg.results.map((r) => `
              <div class="bgg-result" data-action="bgg-pick" data-id="${r.id}">
                <span>${esc(r.nombre)}${r.es_expansion ? ' <span class="year">(expansión)</span>' : ''}</span><span class="year">${esc(r.anio)}</span>
              </div>`).join('')}
          </div>` : ''}
          ${bgg.picked ? `<div class="bgg-picked">
            ${bgg.picked.imagen_url ? `<img src="${esc(bgg.picked.imagen_url)}" alt="">` : ''}
            <span>Imagen y datos de «${esc(bgg.picked.nombre)}» aplicados desde BGG.</span>
          </div>` : ''}
        </div>`;
  }

  function checkboxHtml(id, attr, label, checked) {
    return `
      <label class="checkbox" for="${id}">
        <input type="checkbox" id="${id}" ${attr} ${checked ? 'checked' : ''}>
        <span class="box"></span>
        <span>${esc(label)}</span>
      </label>`;
  }

  function renderAddDialog() {
    const f = state.addForm;
    const editing = !!state.editingGameId;
    const baseOptions = state.games.filter((g) => g.id !== state.editingGameId).sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" style="width:min(560px, 100%)" data-stop>
        <div class="dialog-title">${editing ? 'Editar juego' : 'Añadir juego'}</div>

        ${renderBggAssist('f')}

        <div class="dialog-grid">
          <div class="field span-2">
            <label for="f-nombre">Nombre</label>
            <input id="f-nombre" class="input" data-field="nombre" value="${esc(f.nombre)}" placeholder="Ej. Brass: Birmingham">
          </div>
          <div class="field">
            <label for="f-editorial">Editorial</label>
            <input id="f-editorial" class="input" data-field="editorial" value="${esc(f.editorial)}">
          </div>
          <div class="field">
            <label for="f-tipo">Tipo</label>
            <select id="f-tipo" class="input" data-field="tipo">
              ${state.tipos.map((t) => `<option value="${esc(t)}" ${t === f.tipo ? 'selected' : ''}>${esc(t)}</option>`).join('')}
            </select>
          </div>
          <div class="field">
            <label for="f-punt">Puntuación (0-10)</label>
            <input id="f-punt" class="input" type="number" min="0" max="10" step="0.1" data-field="puntuacion" value="${esc(f.puntuacion)}">
          </div>
          <div class="field">
            <label for="f-precio">Precio pagado</label>
            <input id="f-precio" class="input" type="number" min="0" step="1" data-field="precio" value="${esc(f.precio)}">
          </div>
          <div class="field">
            <label for="f-jug">Jugadores</label>
            <input id="f-jug" class="input" data-field="jugadores" value="${esc(f.jugadores)}" placeholder="2-4">
          </div>
          <div class="field">
            <label for="f-dur">Duración</label>
            <input id="f-dur" class="input" data-field="duracion" value="${esc(f.duracion)}" placeholder="60 min">
          </div>
          <div class="field">
            <label for="f-edad">Edad mínima recomendada</label>
            <input id="f-edad" class="input" type="number" min="0" max="99" step="1" data-field="edad_minima" value="${esc(f.edad_minima)}" placeholder="8">
          </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:16.8px; margin-top:5.6px">
          ${checkboxHtml('f-premium', 'data-field="premium"', 'Versión Premium', f.premium)}
          ${checkboxHtml('f-expansion', 'data-field="es_expansion"', 'Es una expansión', f.es_expansion)}
        </div>

        ${f.es_expansion ? `
        <div class="field">
          <label for="f-base">Juego base</label>
          <select id="f-base" class="input" data-field="base_game_id">
            <option value="">Selecciona un juego…</option>
            ${baseOptions.map((o) => `<option value="${o.id}" ${String(o.id) === String(f.base_game_id) ? 'selected' : ''}>${esc(o.nombre)}</option>`).join('')}
          </select>
        </div>` : ''}

        ${state.formError ? `<div class="form-error">${esc(state.formError)}</div>` : ''}

        <div class="dialog-actions">
          <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
          <button class="btn btn-primary" data-action="save-game">${editing ? 'Guardar cambios' : 'Guardar'}</button>
        </div>
      </div>
    </div>`;
  }

  function renderPlayDialog() {
    const pf = state.playForm;
    const editing = !!state.editingPlayId;
    const gameOptions = [...state.games].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    if (!pf.gameId && gameOptions.length) pf.gameId = String(gameOptions[0].id);
    const selectedGame = state.games.find((g) => String(g.id) === String(pf.gameId));
    const isCoop = !!selectedGame && selectedGame.tipo === 'Cooperativo';

    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" data-stop>
        <div class="dialog-title">${editing ? 'Editar partida' : 'Registrar partida'}</div>

        ${editing ? `
        <div class="field">
          <label for="p-fecha">Fecha</label>
          <input id="p-fecha" class="input" type="date" data-action="play-fecha" value="${esc(pf.fecha)}">
        </div>` : ''}

        <div class="field">
          <label for="p-juego">Juego</label>
          <select id="p-juego" class="input" data-action="play-game">
            ${gameOptions.map((o) => `<option value="${o.id}" ${String(o.id) === String(pf.gameId) ? 'selected' : ''}>${esc(o.nombre)}</option>`).join('')}
          </select>
        </div>

        <div class="field">
          <label>Quién jugó</label>
          <div class="chip-row">
            ${state.players.map((p) => `
              <button type="button" class="chip ${pf.jugadores.includes(p.nombre) ? 'selected' : ''}" data-action="toggle-player" data-name="${esc(p.nombre)}">${esc(p.nombre)}</button>`).join('') || '<span class="text-muted" style="font-size:13px">Añade el primer jugador abajo.</span>'}
          </div>
          <div class="new-player-row">
            <input class="input" placeholder="Nombre de un jugador nuevo" value="${esc(state.newPlayerName)}" data-action="new-player-name">
            <button class="btn btn-secondary" type="button" data-action="add-player">Añadir jugador</button>
          </div>
        </div>

        ${isCoop ? `
        <div class="field">
          <label>Resultado (juego cooperativo, sin ganador individual)</label>
          <div class="seg">
            <label class="seg-opt">
              <input type="radio" name="p-resultado" value="Victoria" data-action="play-resultado" ${pf.resultado === 'Victoria' ? 'checked' : ''}>
              Victoria
            </label>
            <label class="seg-opt">
              <input type="radio" name="p-resultado" value="Derrota" data-action="play-resultado" ${pf.resultado === 'Derrota' ? 'checked' : ''}>
              Derrota
            </label>
          </div>
        </div>` : `
        <div class="field">
          <label for="p-gan">Ganador</label>
          <select id="p-gan" class="input" data-action="play-winner">
            ${pf.jugadores.map((n) => `<option value="${esc(n)}" ${n === pf.ganador ? 'selected' : ''}>${esc(n)}</option>`).join('') || '<option value="">—</option>'}
          </select>
        </div>`}

        <div class="field">
          <label for="p-dur">Duración (min)</label>
          <input id="p-dur" class="input" type="number" min="5" step="5" data-action="play-duracion" value="${esc(pf.duracion)}">
        </div>

        ${state.playError ? `<div class="form-error">${esc(state.playError)}</div>` : ''}

        <div class="dialog-actions">
          <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
          <button class="btn btn-primary" data-action="save-play">${editing ? 'Guardar cambios' : 'Guardar partida'}</button>
        </div>
      </div>
    </div>`;
  }

  function renderDetailDialog() {
    const g = state.games.find((x) => x.id === state.detailId);
    if (!g) { dialogRoot.innerHTML = ''; return; }
    const facts = [
      ['Tipo', g.tipo],
      ['Puntuación', `${Number(g.puntuacion).toFixed(1)} / 10`],
      ['Jugadores', g.jugadores],
      ['Duración', g.duracion],
      ['Edad recomendada', `${g.edad_minima || 0}+`],
      ['Precio', money(g.precio)],
      ['Partidas', String(playCount(g.id))],
      ['Premium', g.premium ? 'Sí' : 'No'],
    ];
    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" style="width:min(480px, 100%)" data-stop>
        <div class="detail-head">
          <div class="detail-cover">${g.imagen_url ? `<img src="${esc(g.imagen_url)}" alt="${esc(g.nombre)}">` : '<span class="placeholder">Carátula</span>'}</div>
          <div style="flex:1">
            <div class="dialog-title">${esc(g.nombre)}</div>
            <div class="row-sub">${esc(g.editorial)}</div>
            <a class="detail-3d-link" href="${esc(makerworldUrl(g.nombre))}" target="_blank" rel="noopener noreferrer">Complementos 3D ↗</a>
          </div>
          <button class="btn btn-icon btn-secondary" data-action="close-dialog" aria-label="Cerrar">✕</button>
        </div>
        <div class="detail-facts">
          ${facts.map(([label, value]) => `
            <div class="detail-fact">
              <div class="label">${esc(label)}</div>
              <div class="value">${esc(value)}</div>
            </div>`).join('')}
        </div>
        ${g.es_expansion ? baseGameNoticeMarkup(g) : expansionLinksMarkup(expansionsOf(g.id))}
        <div class="dialog-actions" style="justify-content:space-between; flex-wrap:wrap">
          <div style="display:flex; flex-wrap:wrap; gap:8.4px">
            ${state.currentUser.isCollector ? `
              <button class="btn btn-ghost" data-action="remove-game" data-id="${g.id}">Quitar de la colección</button>
              <button class="btn btn-secondary" data-action="edit-game" data-id="${g.id}">Editar</button>
            ` : ''}
            ${state.appUsers.length ? `<button class="btn btn-secondary" data-action="wantplay-from-detail" data-id="${g.id}">Proponer partida</button>` : ''}
          </div>
          <button class="btn btn-primary" data-action="play-from-detail" data-id="${g.id}">Registrar partida</button>
        </div>
      </div>
    </div>`;
  }

  function renderWishDialog() {
    const f = state.wishForm;
    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" style="width:min(560px, 100%)" data-stop>
        <div class="dialog-title">Añadir a la lista de deseos</div>

        ${renderBggAssist('w')}

        <div class="dialog-grid">
          <div class="field span-2">
            <label for="w-nombre">Nombre</label>
            <input id="w-nombre" class="input" data-wfield="nombre" value="${esc(f.nombre)}" placeholder="Ej. Brass: Birmingham">
          </div>
          <div class="field">
            <label for="w-editorial">Editorial</label>
            <input id="w-editorial" class="input" data-wfield="editorial" value="${esc(f.editorial)}">
          </div>
          <div class="field">
            <label for="w-tipo">Tipo</label>
            <select id="w-tipo" class="input" data-wfield="tipo">
              ${state.tipos.map((t) => `<option value="${esc(t)}" ${t === f.tipo ? 'selected' : ''}>${esc(t)}</option>`).join('')}
            </select>
          </div>
          <div class="field">
            <label for="w-punt">Puntuación (0-10)</label>
            <input id="w-punt" class="input" type="number" min="0" max="10" step="0.1" data-wfield="puntuacion" value="${esc(f.puntuacion)}">
          </div>
          <div class="field">
            <label for="w-precio">Precio</label>
            <input id="w-precio" class="input" type="number" min="0" step="1" data-wfield="precio" value="${esc(f.precio)}">
          </div>
          <div class="field">
            <label for="w-prioridad">Prioridad</label>
            <select id="w-prioridad" class="input" data-wfield="prioridad">
              ${['Alta', 'Media', 'Baja'].map((p) => `<option value="${p}" ${p === f.prioridad ? 'selected' : ''}>${p}</option>`).join('')}
            </select>
          </div>
          <div class="field">
            <label for="w-jug">Jugadores</label>
            <input id="w-jug" class="input" data-wfield="jugadores" value="${esc(f.jugadores)}" placeholder="2-4">
          </div>
          <div class="field">
            <label for="w-dur">Duración</label>
            <input id="w-dur" class="input" data-wfield="duracion" value="${esc(f.duracion)}" placeholder="60 min">
          </div>
          <div class="field">
            <label for="w-edad">Edad mínima recomendada</label>
            <input id="w-edad" class="input" type="number" min="0" max="99" step="1" data-wfield="edad_minima" value="${esc(f.edad_minima)}" placeholder="8">
          </div>
        </div>

        <div style="margin-top:5.6px">
          ${checkboxHtml('w-premium', 'data-wfield="premium"', 'Versión Premium', f.premium)}
        </div>

        ${state.formError ? `<div class="form-error">${esc(state.formError)}</div>` : ''}
        <div class="dialog-actions">
          <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
          <button class="btn btn-primary" data-action="save-wish">Guardar</button>
        </div>
      </div>
    </div>`;
  }

  function renderLoanDialog() {
    const f = state.loanForm || { gameId: '', persona: '' };
    state.loanForm = f;
    const gameOptions = [...state.games].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    // A <select> without an explicit "selected" option still shows the first option in the
    // browser, but our state stays '' until the user actually touches it — keep them in sync
    // from the first render so submitting without touching the dropdown still saves the right game.
    if (!f.gameId && gameOptions.length) f.gameId = String(gameOptions[0].id);
    dialogRoot.innerHTML = `
    <div class="dialog-backdrop" data-action="backdrop-close">
      <div class="dialog" data-stop>
        <div class="dialog-title">Registrar préstamo</div>
        <div class="field">
          <label for="l-juego">Juego</label>
          <select id="l-juego" class="input" data-lfield="gameId">
            ${gameOptions.map((o) => `<option value="${o.id}" ${String(o.id) === String(f.gameId) ? 'selected' : ''}>${esc(o.nombre)}</option>`).join('')}
          </select>
        </div>
        <div class="field">
          <label for="l-persona">Persona</label>
          <input id="l-persona" class="input" data-lfield="persona" value="${esc(f.persona)}">
        </div>
        ${state.formError ? `<div class="form-error">${esc(state.formError)}</div>` : ''}
        <div class="dialog-actions">
          <button class="btn btn-secondary" data-action="close-dialog">Cancelar</button>
          <button class="btn btn-primary" data-action="save-loan">Guardar</button>
        </div>
      </div>
    </div>`;
  }

  // ---------- actions ----------

  function closeDialog() {
    state.dialog = null;
    state.detailId = null;
    state.editingGameId = null;
    state.editingPlayId = null;
    state.formError = '';
    state.playError = '';
    state.bgg = emptyBgg();
    state.addForm = emptyGameForm();
    state.playForm = emptyPlayForm();
    state.wishForm = emptyWishForm();
    state.wantPlayForm = emptyWantPlayForm();
    state.wantPlayError = '';
    state.randomForm = emptyRandomForm();
    state.randomSpin = null;
    state.randomError = '';
    render();
  }

  async function saveWantPlay() {
    const f = state.wantPlayForm;
    if (!f.targetIds.length) {
      state.wantPlayError = 'Elige al menos una persona.';
      render();
      return;
    }
    try {
      await api('api/want_to_play.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'create', game_id: Number(f.gameId), target_ids: f.targetIds }),
      });
      await refresh();
      closeDialog();
    } catch (e) {
      state.wantPlayError = e.message;
      render();
    }
  }

  async function dismissWantPlay(id) {
    await api('api/want_to_play.php', { method: 'POST', body: JSON.stringify({ action: 'dismiss', id }) });
    await refresh();
  }

  async function acceptWantPlay(id) {
    await api('api/want_to_play.php', { method: 'POST', body: JSON.stringify({ action: 'accept', id }) });
    await refresh();
  }

  async function deleteWantPlay(id) {
    if (!window.confirm('¿Cancelar esta propuesta para todos?')) return;
    await api('api/want_to_play.php', { method: 'POST', body: JSON.stringify({ action: 'delete', id }) });
    await refresh();
  }

  async function saveGame() {
    const f = state.addForm;
    if (!f.nombre.trim()) {
      state.formError = 'Pon al menos un nombre.';
      render();
      return;
    }
    try {
      const body = {
        nombre: f.nombre, editorial: f.editorial, tipo: f.tipo,
        puntuacion: f.puntuacion, precio: f.precio, jugadores: f.jugadores, duracion: f.duracion,
        edad_minima: f.edad_minima, premium: f.premium, es_expansion: f.es_expansion,
        base_game_id: f.es_expansion && f.base_game_id ? Number(f.base_game_id) : null,
        // La carátula/bgg_id viven en el propio formulario (emptyGameForm/edit-game la
        // precargan con la actual; pickBgg la sustituye solo si se elige algo nuevo),
        // así que editar sin tocar el buscador de BGG ya no borra la carátula.
        bgg_id: f.bgg_id, imagen_url: f.imagen_url,
      };
      if (state.editingGameId) {
        await api('api/games.php?id=' + state.editingGameId, { method: 'PUT', body: JSON.stringify(body) });
      } else {
        await api('api/games.php', { method: 'POST', body: JSON.stringify(body) });
      }
      state.dialog = null;
      state.query = '';
      await refresh();
      closeDialog();
    } catch (e) {
      state.formError = e.message;
      render();
    }
  }

  async function searchBgg() {
    const q = state.bgg.query.trim();
    if (q.length < 2) return;
    state.bgg.searching = true;
    state.bgg.error = '';
    render();
    try {
      const data = await api('api/bgg_search.php?q=' + encodeURIComponent(q));
      state.bgg.results = data.results;
      if (!data.results.length) state.bgg.error = 'Sin resultados en BoardGameGeek.';
    } catch (e) {
      state.bgg.error = e.message;
    }
    state.bgg.searching = false;
    render();
  }

  async function pickBgg(id) {
    try {
      const data = await api('api/bgg_thing.php?id=' + encodeURIComponent(id));
      const t = data.thing;
      state.bgg.picked = t;
      state.bgg.results = [];
      const targetForm = state.bggTarget === 'wish' ? state.wishForm : state.addForm;
      // BGG solo tiene un nombre "primary" (normalmente en inglés) y alternativos sin
      // etiqueta de idioma, así que no hay forma fiable de pedirle el nombre en
      // español. En su lugar, si ya habías escrito un nombre (añadiendo o editando),
      // se respeta: BGG solo lo rellena cuando el campo está vacío.
      if (!targetForm.nombre.trim() && t.nombre) targetForm.nombre = t.nombre;
      if (t.editorial) targetForm.editorial = t.editorial;
      if (t.jugadores) targetForm.jugadores = t.jugadores;
      if (t.duracion) targetForm.duracion = t.duracion;
      if (t.edad_minima) targetForm.edad_minima = String(t.edad_minima);
      if (t.puntuacion_bgg) targetForm.puntuacion = String(t.puntuacion_bgg);
      // Elegir un resultado sí sustituye la carátula (es una acción explícita); no
      // tocarla (dejar el buscador en blanco) no debe borrar la que ya hubiera.
      targetForm.imagen_url = t.imagen_url || null;
      targetForm.bgg_id = t.bgg_id || null;
    } catch (e) {
      state.bgg.error = e.message;
    }
    render();
  }

  async function savePlay() {
    const pf = state.playForm;
    if (!pf.jugadores.length) {
      state.playError = 'Selecciona al menos un jugador.';
      render();
      return;
    }
    const game = state.games.find((g) => String(g.id) === String(pf.gameId));
    const isCoop = !!game && game.tipo === 'Cooperativo';
    try {
      const body = { game_id: Number(pf.gameId), jugadores: pf.jugadores, duracion: pf.duracion };
      if (isCoop) {
        body.resultado = pf.resultado || 'Victoria';
      } else {
        body.ganador = pf.ganador || pf.jugadores[0];
      }
      if (state.editingPlayId) {
        body.fecha = pf.fecha;
        await api('api/plays.php?id=' + state.editingPlayId, { method: 'PUT', body: JSON.stringify(body) });
      } else {
        await api('api/plays.php', { method: 'POST', body: JSON.stringify(body) });
      }
      state.view = 'partidas';
      state.playForm = emptyPlayForm();
      state.editingPlayId = null;
      await refresh();
      closeDialog();
    } catch (e) {
      state.playError = e.message;
      render();
    }
  }

  async function addPlayer() {
    const name = state.newPlayerName.trim();
    if (!name) return;
    try {
      await api('api/players.php', { method: 'POST', body: JSON.stringify({ nombre: name }) });
      await loadState();
      state.newPlayerName = '';
      if (!state.playForm.jugadores.includes(name)) {
        state.playForm.jugadores.push(name);
        if (!state.playForm.ganador) state.playForm.ganador = name;
      }
      render();
    } catch (e) {
      state.playError = e.message;
      render();
    }
  }

  async function removeGame(id) {
    if (!window.confirm('¿Quitar este juego de la colección?')) return;
    await api('api/games.php?id=' + id, { method: 'DELETE' });
    closeDialog();
    await refresh();
  }

  async function saveWish() {
    const f = state.wishForm;
    if (!f.nombre.trim()) {
      state.formError = 'Pon al menos un nombre.';
      render();
      return;
    }
    try {
      const bgg = state.bgg.picked;
      await api('api/wishlist.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'create', nombre: f.nombre, editorial: f.editorial, tipo: f.tipo,
          puntuacion: f.puntuacion, precio: f.precio, jugadores: f.jugadores, duracion: f.duracion,
          prioridad: f.prioridad, edad_minima: f.edad_minima, premium: f.premium,
          bgg_id: bgg ? bgg.bgg_id : null, imagen_url: bgg ? bgg.imagen_url : null,
        }),
      });
      await refresh();
      closeDialog();
    } catch (e) {
      state.formError = e.message;
      render();
    }
  }

  async function buyWish(id) {
    await api('api/wishlist.php', { method: 'POST', body: JSON.stringify({ action: 'buy', id }) });
    state.view = 'coleccion';
    await refresh();
  }

  async function removeWish(id) {
    if (!window.confirm('¿Quitar este juego de la lista de deseos?')) return;
    await api('api/wishlist.php?id=' + id, { method: 'DELETE' });
    await refresh();
  }

  async function saveLoan() {
    const f = state.loanForm;
    if (!f.gameId || !f.persona.trim()) {
      state.formError = 'Indica el juego y a quién se lo prestas.';
      render();
      return;
    }
    try {
      await api('api/loans.php', {
        method: 'POST',
        body: JSON.stringify({ game_id: Number(f.gameId), persona: f.persona }),
      });
      state.loanForm = null;
      await refresh();
      closeDialog();
    } catch (e) {
      state.formError = e.message;
      render();
    }
  }

  async function returnLoan(id) {
    await api('api/loans.php?id=' + id, { method: 'DELETE' });
    await refresh();
  }

  // ---------- events ----------

  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-action]');
    if (!t) return;
    const action = t.dataset.action;

    if (t.tagName === 'A' && t.getAttribute('href') === '#') e.preventDefault();

    if (action === 'backdrop-close' && e.target === t) {
      // The create/edit game dialog can hold a lot of typed-in work (BGG picks, fields);
      // a stray click outside must not discard it. Cancelar/Guardar/✕ still close it.
      if (state.dialog !== 'add') closeDialog();
      return;
    }
    if (action === 'go-view') { state.view = t.dataset.view; setSidebarOpen(false); render(); return; }
    if (action === 'set-theme') {
      applyTheme(t.dataset.theme);
      renderThemeSwatches();
      savePreference('theme', t.dataset.theme);
      return;
    }
    if (action === 'set-view-mode') {
      state.viewMode = t.dataset.mode;
      render();
      savePreference('view_mode', t.dataset.mode);
      return;
    }
    if (action === 'open-add') {
      state.dialog = 'add'; state.editingGameId = null; state.formError = '';
      state.addForm = emptyGameForm(); state.bgg = emptyBgg(); state.bggTarget = 'game';
      render();
      return;
    }
    if (action === 'open-play') {
      state.dialog = 'play'; state.playError = ''; state.editingPlayId = null; state.playForm = emptyPlayForm();
      render();
      return;
    }
    if (action === 'edit-play') {
      const p = state.plays.find((x) => x.id === Number(t.dataset.id));
      if (!p) return;
      const game = state.games.find((g) => g.id === p.game_id);
      const isCoop = !!game && game.tipo === 'Cooperativo';
      state.dialog = 'play'; state.playError = ''; state.editingPlayId = p.id;
      state.playForm = {
        gameId: String(p.game_id), jugadores: p.jugadores.map((j) => j.nombre),
        ganador: isCoop ? '' : (p.ganador_nombre || p.jugadores[0]?.nombre || ''),
        resultado: p.resultado || 'Victoria', duracion: String(p.duracion), fecha: p.fecha,
      };
      render();
      return;
    }
    if (action === 'open-wish') {
      state.dialog = 'wish'; state.wishForm = emptyWishForm(); state.formError = '';
      state.bgg = emptyBgg(); state.bggTarget = 'wish';
      render();
      return;
    }
    if (action === 'open-loan') { state.dialog = 'loan'; state.loanForm = null; state.formError = ''; render(); return; }
    if (action === 'open-wantplay') {
      state.dialog = 'wantplay'; state.wantPlayForm = emptyWantPlayForm(); state.wantPlayError = '';
      render();
      return;
    }
    if (action === 'open-random') {
      state.dialog = 'random'; state.randomForm = emptyRandomForm(); state.randomSpin = null; state.randomError = '';
      render();
      return;
    }
    if (action === 'random-spin') {
      const tipo = state.randomForm.tipo;
      const pool = tipo === 'Todos' ? state.games : state.games.filter((g) => g.tipo === tipo);
      if (!pool.length) { state.randomError = 'No hay juegos de ese tipo en la colección.'; render(); return; }
      const resultGame = pool[Math.floor(Math.random() * pool.length)];
      const filler = Array.from({ length: REEL_FILLER_COUNT }, () => pool[Math.floor(Math.random() * pool.length)]);
      const trailing = Array.from({ length: REEL_TRAILING_COUNT }, () => pool[Math.floor(Math.random() * pool.length)]);
      state.randomError = '';
      state.randomSpin = {
        reel: [...filler, resultGame, ...trailing], resultGame, targetIndex: filler.length,
        phase: 'spinning', animationStarted: false,
      };
      render();
      return;
    }
    if (action === 'random-again') {
      state.randomSpin = null;
      render();
      return;
    }
    if (action === 'random-view') {
      state.dialog = 'detail'; state.detailId = Number(t.dataset.id);
      state.randomSpin = null; state.randomForm = emptyRandomForm();
      render();
      return;
    }
    if (action === 'toggle-wtp-target') {
      const id = Number(t.dataset.id);
      const ids = state.wantPlayForm.targetIds;
      const idx = ids.indexOf(id);
      if (idx >= 0) ids.splice(idx, 1); else ids.push(id);
      render();
      return;
    }
    if (action === 'save-wantplay') { saveWantPlay(); return; }
    if (action === 'wtp-dismiss') { dismissWantPlay(Number(t.dataset.id)); return; }
    if (action === 'wtp-accept') { acceptWantPlay(Number(t.dataset.id)); return; }
    if (action === 'wtp-delete') { deleteWantPlay(Number(t.dataset.id)); return; }
    if (action === 'close-dialog') { closeDialog(); return; }
    if (action === 'open-detail') { state.dialog = 'detail'; state.detailId = Number(t.dataset.id); render(); return; }
    if (action === 'edit-game') {
      const g = state.games.find((x) => x.id === Number(t.dataset.id));
      if (!g) return;
      state.dialog = 'add';
      state.editingGameId = g.id;
      state.formError = '';
      state.bgg = emptyBgg();
      state.bggTarget = 'game';
      state.addForm = {
        nombre: g.nombre, editorial: g.editorial, tipo: g.tipo,
        puntuacion: String(g.puntuacion), precio: String(g.precio), jugadores: g.jugadores, duracion: g.duracion,
        edad_minima: String(g.edad_minima || 0), premium: !!g.premium,
        es_expansion: !!g.es_expansion, base_game_id: g.base_game_id ? String(g.base_game_id) : '',
        imagen_url: g.imagen_url || null, bgg_id: g.bgg_id || null,
      };
      render();
      return;
    }
    if (action === 'save-game') { saveGame(); return; }
    if (action === 'bgg-search') { searchBgg(); return; }
    if (action === 'bgg-pick') { pickBgg(t.dataset.id); return; }
    if (action === 'save-play') { savePlay(); return; }
    if (action === 'add-player') { addPlayer(); return; }
    if (action === 'toggle-player') {
      const name = t.dataset.name;
      const pf = state.playForm;
      const idx = pf.jugadores.indexOf(name);
      if (idx >= 0) pf.jugadores.splice(idx, 1); else pf.jugadores.push(name);
      if (!pf.jugadores.includes(pf.ganador)) pf.ganador = pf.jugadores[0] || '';
      render();
      return;
    }
    if (action === 'remove-game') { removeGame(Number(t.dataset.id)); return; }
    if (action === 'play-from-detail') {
      state.dialog = 'play'; state.playError = '';
      state.playForm = { ...emptyPlayForm(), gameId: String(t.dataset.id) };
      render();
      return;
    }
    if (action === 'play-from-wtp') {
      const p = state.wantToPlay.find((x) => x.id === Number(t.dataset.id));
      if (!p) return;
      // Precarga el juego y quién jugó (quien propuso + quienes aceptaron) para que
      // solo falte poner la duración y quién ganó. Si quien propuso fue el admin
      // (sin jugador propio), no se añade "Admin" como si fuera una persona real.
      const proposerNombre = p.requested_by_user_id !== null ? [p.requested_by_nombre] : [];
      const jugadores = [...new Set([...proposerNombre, ...p.targets.filter((x) => x.accepted).map((x) => x.nombre)])];
      state.dialog = 'play'; state.playError = '';
      state.playForm = { ...emptyPlayForm(), gameId: String(p.game_id), jugadores };
      render();
      return;
    }
    if (action === 'wantplay-from-detail') {
      state.dialog = 'wantplay'; state.wantPlayError = '';
      state.wantPlayForm = { ...emptyWantPlayForm(), gameId: String(t.dataset.id) };
      render();
      return;
    }
    if (action === 'wish-buy') { buyWish(Number(t.dataset.id)); return; }
    if (action === 'wish-remove') { removeWish(Number(t.dataset.id)); return; }
    if (action === 'save-wish') { saveWish(); return; }
    if (action === 'save-loan') { saveLoan(); return; }
    if (action === 'loan-return') { returnLoan(Number(t.dataset.id)); return; }
  });

  document.addEventListener('input', (e) => {
    if (e.target.type === 'checkbox') return; // handled in the 'change' listener, via .checked
    if (e.target.id === 'q') { state.query = e.target.value; renderColeccionInPlace(); return; }
    if (e.target.dataset.field) { state.addForm[e.target.dataset.field] = e.target.value; return; }
    if (e.target.dataset.action === 'bgg-query') { state.bgg.query = e.target.value; return; }
    if (e.target.dataset.action === 'new-player-name') { state.newPlayerName = e.target.value; return; }
    if (e.target.dataset.action === 'play-duracion') { state.playForm.duracion = e.target.value; return; }
    if (e.target.dataset.action === 'play-fecha') { state.playForm.fecha = e.target.value; return; }
    if (e.target.dataset.wfield) { state.wishForm[e.target.dataset.wfield] = e.target.value; return; }
    if (e.target.dataset.lfield) { state.loanForm[e.target.dataset.lfield] = e.target.value; return; }
  });

  document.addEventListener('change', (e) => {
    if (e.target.dataset.action === 'tipo-filter') { state.tipoFilter = e.target.value; render(); return; }
    if (e.target.dataset.action === 'edad-filter') { state.edadFilter = e.target.value; render(); return; }
    if (e.target.dataset.action === 'sort') { state.sort = e.target.value; render(); return; }
    if (e.target.dataset.action === 'play-game') {
      state.playForm.gameId = e.target.value;
      render(); // el juego elegido decide si se pide Ganador o Resultado (cooperativo)
      return;
    }
    if (e.target.dataset.action === 'play-winner') { state.playForm.ganador = e.target.value; return; }
    if (e.target.dataset.action === 'play-resultado') { state.playForm.resultado = e.target.value; return; }
    if (e.target.dataset.action === 'wtp-game') { state.wantPlayForm.gameId = e.target.value; return; }
    if (e.target.dataset.action === 'random-tipo') { state.randomForm.tipo = e.target.value; return; }
    if (e.target.type === 'checkbox' && e.target.dataset.field) {
      state.addForm[e.target.dataset.field] = e.target.checked;
      // Toggling "es una expansión" shows/hides the "juego base" select — needs a real re-render.
      if (e.target.dataset.field === 'es_expansion') render();
      return;
    }
    if (e.target.type === 'checkbox' && e.target.dataset.wfield) {
      state.wishForm[e.target.dataset.wfield] = e.target.checked;
      return;
    }
    // <select> reliably fires 'change' but not always 'input' — cover the plain data-field/wfield/lfield
    // selects (tipo, prioridad, juego) here too so picking an option is never lost.
    if (e.target.dataset.field) { state.addForm[e.target.dataset.field] = e.target.value; return; }
    if (e.target.dataset.wfield) { state.wishForm[e.target.dataset.wfield] = e.target.value; return; }
    if (e.target.dataset.lfield) { state.loanForm[e.target.dataset.lfield] = e.target.value; return; }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && document.activeElement && document.activeElement.dataset.action === 'bgg-query') {
      e.preventDefault();
      searchBgg();
    }
    if (e.key === 'Escape' && state.dialog) closeDialog();

    // role="button" cards (game grid) only get a real click from mouse/touch by default.
    if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.action === 'open-detail') {
      e.preventDefault();
      e.target.click();
    }
  });

  // The search field re-renders the whole collection view on every keystroke,
  // which would steal focus; re-render just the section instead.
  function renderColeccionInPlace() {
    const section = viewRoot.querySelector('section');
    if (!section) { render(); return; }
    const activeId = document.activeElement ? document.activeElement.id : null;
    viewRoot.innerHTML = renderColeccion();
    if (activeId) {
      const el = document.getElementById(activeId);
      if (el) { el.focus(); if (el.setSelectionRange && el.value) el.setSelectionRange(el.value.length, el.value.length); }
    }
  }

  // No existe en el DOM para el rol jugador (index.php no lo pinta).
  document.getElementById('btn-add-game')?.addEventListener('click', () => {
    state.dialog = 'add'; state.editingGameId = null; state.formError = '';
    state.addForm = emptyGameForm(); state.bgg = emptyBgg(); state.bggTarget = 'game';
    render();
  });

  render();
  refresh().catch((e) => {
    viewRoot.innerHTML = `<p class="form-error" style="padding:22.4px 0">No se pudo cargar la colección: ${esc(e.message)}</p>`;
  });
})();
