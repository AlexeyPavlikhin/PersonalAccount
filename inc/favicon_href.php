<?php

/**
 * Префикс пути к корню приложения (пусто = сайт в корне домена, иначе /подкаталог).
 */
function msll_script_directory_prefix(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $script = str_replace('\\', '/', (string) $script);
    $dir = dirname($script);

    if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
        return '';
    }

    return rtrim($dir, '/');
}

/**
 * Абсолютный от корня сайта URL к файлу внутри каталога приложения (например pictures/logo.png).
 */
function msll_site_root_href(string $relative_path): string
{
    global $ASSET_VER;
    $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
    $prefix = msll_script_directory_prefix();
    $path = ($prefix === '' ? '/' : $prefix . '/') . $relative_path;
    $v = isset($ASSET_VER) ? (string) $ASSET_VER : '';
    if ($v !== '') {
        $path .= '?v=' . rawurlencode($v);
    }

    return $path;
}

/**
 * URL favicon.ico (часто лежит битый ico; см. также PNG в разметке).
 */
function msll_favicon_href(): string
{
    return msll_site_root_href('favicon.ico');
}
