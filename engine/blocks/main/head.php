<?php
/**
 * Шапка документа. Подключается первой строкой каждой страницы.
 * Страница задаёт переменные ДО include — здесь только значения по умолчанию.
 *
 * Обязательное:  $pageTitle, $metaDescription, $pageUrl
 * Необязательное: $canonical (по умолчанию = $pageUrl), $metaImage,
 *                 $bodyClass, $noindex (true — закрыть от индексации),
 *                 $jsonLd (массив → отдаётся как JSON-LD), $headExtra,
 *                 $metaImageW / $metaImageH (размеры картинки шеринга)
 *                 $preloadFonts (шрифты первого экрана)
 */

$pageTitle       = $pageTitle       ?? 'Студия «Ритм» — дизайн и брендинг, сайты и продвижение';
$metaDescription = $metaDescription ?? '';
$pageUrl         = $pageUrl         ?? 'https://ritmo.studio/';
$canonical       = $canonical       ?? $pageUrl;
$metaImage       = $metaImage       ?? 'https://ritmo.studio/assets/img/share.jpg';
$metaImageW      = $metaImageW      ?? 2400;
$metaImageH      = $metaImageH      ?? 1260;
$bodyClass       = $bodyClass       ?? '';
$noindex         = $noindex         ?? false;

/**
 * Любой хост, кроме боевого, закрывается от индексации автоматически.
 * Это страховка: не зависит от того, выставили ли $noindex на странице.
 *
 * Заголовок важнее мета-тега — он действует и на файлы, которые
 * разметки не имеют (pdf, изображения, json).
 *
 * Хост берётся из SERVER_NAME, а не из HTTP_HOST, и это не вкусовщина.
 * HTTP_HOST присылает клиент: curl -H 'Host: ritmo.studio' по адресу
 * стейджинга снимал с него noindex и — хуже — включал там Метрику,
 * то есть Вебвизор начинал писать разработку в боевую статистику.
 * SERVER_NAME приходит из server_name в конфиге nginx, подделать его
 * запросом нельзя.
 *
 * Пустое значение намеренно считается небоевым: ошибка в сторону
 * noindex видна в Вебмастере и проверяется одной командой (она в
 * engine.json, шаг 12), а ошибка в обратную сторону молчит.
 *
 * Если перед PHP стоит Apache (в ISPmanager это schema с proxy_pass),
 * ему нужен UseCanonicalName On: при Off он подставляет в SERVER_NAME
 * тот же клиентский Host, и подделка снова работает.
 */
$prodHost = 'konfeta.group';
$host     = strtolower(preg_replace('/:\d+$/', '', $_SERVER['SERVER_NAME'] ?? ''));
$isProd   = ($host === $prodHost || $host === 'www.' . $prodHost);
if (!$isProd) {
    $noindex = true;
}
if ($noindex && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow', true);
}
$jsonLd          = $jsonLd          ?? null;

/**
 * Шрифты первого экрана. Без preload экран успевает отрисоваться
 * системным шрифтом и перерисоваться своим — видимый скачок и вклад
 * в CLS: браузер узнаёт о шрифте только дойдя до текста, который им
 * набран, то есть уже после загрузки и разбора CSS.
 *
 * Сюда идёт только то, что действительно есть выше сгиба. Шрифт,
 * которым ничего не отрисовано, браузер не скачивает вообще —
 * положить такой в preload значит заставить скачать его зря.
 */
$preloadFonts = $preloadFonts ?? [];  // шрифт сайта не выбран, предзагружать нечего

$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Версия для сброса кэша: меняется при правке файла, вручную трогать не нужно.
$assetVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/main.css') ?: time();

/**
 * Минификация HTML. Включена только на боевом хосте: на стейджинге и
 * в CLI-рендере страница должна читаться глазами и grep'ом — на чтении
 * отрендеренного HTML держатся все наши проверки, и терять их ради
 * 130 байт нельзя. Переключатель для проверки руками:
 *
 *     RS_HTML_MIN=1 php engine/tools/render.php geo/index.php
 *
 * Буфер не закрывается явно: PHP прогоняет его через обработчик и на
 * нормальном конце скрипта, и на exit посреди страницы. Так фильтр
 * нельзя забыть выключить в footer.php.
 */
$htmlMinify = $htmlMinify ?? (getenv('RS_HTML_MIN') !== false ? getenv('RS_HTML_MIN') === '1' : $isProd);
if ($htmlMinify) {
    require __DIR__ . '/html-min.php';
    ob_start('rs_html_min');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($pageTitle) ?></title>
<?php if ($metaDescription): ?>
<meta name="description" content="<?= $e($metaDescription) ?>">
<?php endif; ?>
<meta name="robots" content="<?= $noindex ? 'noindex, nofollow' : 'index, follow' ?>">
<link rel="canonical" href="<?= $e($canonical) ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Ритм">
<meta property="og:locale" content="ru_RU">
<meta property="og:url" content="<?= $e($pageUrl) ?>">
<meta property="og:title" content="<?= $e($pageTitle) ?>">
<meta property="og:description" content="<?= $e($metaDescription) ?>">
<meta property="og:image" content="<?= $e($metaImage) ?>">
<meta property="og:image:width" content="<?= $e($metaImageW) ?>">
<meta property="og:image:height" content="<?= $e($metaImageH) ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="/assets/img/icons/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/img/icons/apple-touch-icon.png">
<meta name="theme-color" content="#E5E7DF">

<?php foreach ($preloadFonts as $f): ?>
<link rel="preload" href="<?= $e($f) ?>" as="font" type="font/<?= str_ends_with($f, '.woff2') ? 'woff2' : 'ttf' ?>" crossorigin>
<?php endforeach; ?>
<link rel="stylesheet" href="/assets/css/main.css?v=<?= $assetVer ?>">
<?php if ($jsonLd): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
<?= $headExtra ?? '' ?>

</head>
<body class="<?= $e($bodyClass) ?>">
<a class="rs-sr-only" href="#main">Перейти к содержанию</a>
<?php /* Ни шапки, ни <main> здесь нет намеренно: на этом сайте
         логотип живёт в колонке каркаса, а навигация плавает
         поверх контента. Структуру страницы собирает сама
         страница — так она видна целиком в одном файле. */ ?>
