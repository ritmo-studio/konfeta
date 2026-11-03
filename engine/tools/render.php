<?php
/**
 * Обёртка для локального рендера страницы.
 * Нужна потому, что страницы подключают инклюды через $_SERVER['DOCUMENT_ROOT'],
 * а в CLI эта переменная не задана.
 *
 *   php engine/tools/render.php index.php
 *   php engine/tools/render.php about/index.php
 */
$root = dirname(__DIR__, 2);          // engine/tools → корень репозитория
$docroot = $root . '/public';         // наружу отдаётся только public/
$_SERVER['DOCUMENT_ROOT'] = $docroot;

$page = $argv[1] ?? 'index.php';
$path = $docroot . '/' . ltrim($page, '/');

if (!is_file($path)) {
    fwrite(STDERR, "Нет такой страницы: {$page}\n");
    exit(1);
}
chdir($docroot);
include $path;
