<?php
/**
 * Sistema de temas (4 paletas: crema, pergamino, tinta, nocturno). Cada tema define solo
 * 5 colores base; las rampas 100-900 se derivan por interpolación, igual que en
 * assets/js/app.js (mixColor/ramp/themeVars) — misma matemática en los dos sitios para
 * que el tema aplicado aquí en el <head> (evita parpadeo al cargar) y el que aplica el
 * JS al cambiarlo en caliente sean exactamente el mismo.
 */

const LUDOTECA_THEMES = [
    'crema' => ['label' => 'Crema', 'bg' => '#f3ecdd', 'surface' => '#faf6ec', 'ink' => '#262231', 'accent' => '#6d5aa6', 'gold' => '#b8892f'],
    'pergamino' => ['label' => 'Pergamino', 'bg' => '#ece2cd', 'surface' => '#f7f1e0', 'ink' => '#2b2418', 'accent' => '#a8792a', 'gold' => '#6d5aa6'],
    'tinta' => ['label' => 'Tinta', 'bg' => '#1c1726', 'surface' => '#251e33', 'ink' => '#ece7f5', 'accent' => '#a08ce0', 'gold' => '#d1a94e'],
    'nocturno' => ['label' => 'Nocturno', 'bg' => '#161826', 'surface' => '#1e2032', 'ink' => '#e9e9ed', 'accent' => '#9184d9', 'gold' => '#c2b273'],
];

const LUDOTECA_DEFAULT_THEME = 'crema';
const LUDOTECA_VIEW_MODES = ['caratula', 'ficha'];
const LUDOTECA_DEFAULT_VIEW_MODE = 'caratula';

function ludoteca_theme_mix(string $a, string $b, float $t): string
{
    $hex = fn ($c) => [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];
    [$r1, $g1, $b1] = $hex($a);
    [$r2, $g2, $b2] = $hex($b);
    $p = fn ($n) => str_pad(dechex((int) round($n)), 2, '0', STR_PAD_LEFT);
    return '#' . $p($r1 + ($r2 - $r1) * $t) . $p($g1 + ($g2 - $g1) * $t) . $p($b1 + ($b2 - $b1) * $t);
}

function ludoteca_theme_ramp(string $bg, string $base, string $ink, string $prefix): array
{
    $near = [0.10, 0.26, 0.48, 0.72];
    $far = [0.22, 0.42, 0.60, 0.78];
    $out = [$prefix => $base, "{$prefix}-500" => $base];
    foreach ([900, 800, 700, 600] as $i => $step) {
        $out["{$prefix}-{$step}"] = ludoteca_theme_mix($bg, $base, $near[$i]);
    }
    foreach ([400, 300, 200, 100] as $i => $step) {
        $out["{$prefix}-{$step}"] = ludoteca_theme_mix($base, $ink, $far[$i]);
    }
    return $out;
}

function ludoteca_theme_vars(string $themeId): array
{
    $t = LUDOTECA_THEMES[$themeId] ?? LUDOTECA_THEMES[LUDOTECA_DEFAULT_THEME];
    $v = [
        '--color-bg' => $t['bg'], '--color-surface' => $t['surface'], '--color-text' => $t['ink'],
        '--color-accent' => $t['accent'], '--color-accent-2' => $t['gold'],
        '--color-divider' => ludoteca_theme_mix($t['bg'], $t['ink'], 0.16),
    ];
    $v += ludoteca_theme_ramp($t['bg'], ludoteca_theme_mix($t['bg'], $t['ink'], 0.62), $t['ink'], '--color-neutral');
    $v += ludoteca_theme_ramp($t['bg'], $t['accent'], $t['ink'], '--color-accent');
    $v += ludoteca_theme_ramp($t['bg'], $t['gold'], $t['ink'], '--color-accent-2');
    $v['--shadow-sm'] = '0 0 0 1px ' . ludoteca_theme_mix($t['bg'], $t['ink'], 0.16);
    $v['--shadow-md'] = '0 0 0 1px ' . ludoteca_theme_mix($t['bg'], $t['ink'], 0.26) . ', 0 4px 14px rgba(0,0,0,0.14)';
    $v['--shadow-lg'] = '0 0 0 1px ' . ludoteca_theme_mix($t['bg'], $t['ink'], 0.38) . ', 0 14px 36px rgba(0,0,0,0.22)';
    return $v;
}

/** <style> con las variables del tema ya resueltas, para inyectar en <head> sin parpadeo. */
function ludoteca_theme_style_block(string $themeId): string
{
    $decls = '';
    foreach (ludoteca_theme_vars($themeId) as $k => $val) {
        $decls .= $k . ':' . $val . ';';
    }
    return '<style>:root{' . $decls . '}</style>';
}
