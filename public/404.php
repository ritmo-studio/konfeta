<?php
/**
 * Страница «не найдено». Код ответа важнее вида: 404 с кодом 200 —
 * soft 404, поиск индексирует ошибку как страницу.
 * Маршрут сюда задаёт nginx: error_page 404 /404.php.
 */
http_response_code(404);

$pageTitle       = 'Страница не найдена — «Конфета»';
$metaDescription = '';
$pageUrl         = 'https://konfeta.group/404';
$bodyClass       = 'page-404';
$noindex         = true;

$root   = dirname($_SERVER['DOCUMENT_ROOT']);
$engine = $root . '/engine';
include $engine . '/blocks/main/head.php';
include $engine . '/blocks/page-404/page-404.php';
include $engine . '/blocks/main/footer.php';
