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
 *
 * Сайтовое — домен, счётчик, og:site_name, theme-color и значения по
 * умолчанию для шеринга — живёт в content/site.php (образец:
 * engine/site.example.php). Файлы движка про сайт не знают и между
 * сайтами не отличаются. Без content/site.php хост считается небоевым
 * (noindex везде) и счётчик не ставится: ошибка видна, а не молчит.
 * Сборка (build.py) проверяет, что файл есть и prodHost задан.
 */

/**
 * ЗАЩИТА ОТ МЯГКОГО 404. Панели хостинга настраивают PHP-сайт как
 * одностраничное приложение: location / { try_files $uri $uri/ /index.php?$args; }
 * Нашему движку это противопоказано — роутера нет, страница это настоящая
 * папка с index.php, и правило «не нашёл файл → отдай /index.php» означает,
 * что ЛЮБОЙ несуществующий адрес отдаёт главную с кодом 200. Для поиска это
 * бесконечный набор дублей главной, и по виду сайта заметить это нельзя.
 *
 * Чинить это только конфигом не выходит: правку затирает первое же сохранение
 * сайта в панели (FastPanel перегенерирует конфиг целиком), а переопределить
 * location / из подключаемого файла панели нельзя — два location / в одном
 * server nginx не примет. Поэтому проверку делает страница: сравниваем адрес
 * запроса с тем скриптом, который на самом деле исполняется. Разошлись —
 * значит мы здесь по чужому адресу, и это 404.
 *
 * Правильный nginx (engine/tools/nginx.conf.example) всё равно нужен: он
 * отвечает 404 не запуская PHP. Эта проверка — страховка под него.
 *
 * CLI пропускаем: render.php задаёт только DOCUMENT_ROOT, адреса запроса там нет.
 */
if (PHP_SAPI !== 'cli' && !defined('RS_ROUTE_CHECKED')) {
    define('RS_ROUTE_CHECKED', true);
    $rsPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $rsScript = $_SERVER['SCRIPT_NAME'] ?? '';
    // Адрес со слэшом на конце обслуживает index.php в своей папке; адрес,
    // кончающийся на .php, — сам этот файл (form.php, 404.php).
    $rsWanted = substr($rsPath, -4) === '.php' ? $rsPath : rtrim($rsPath, '/') . '/index.php';
    // Саму страницу 404 не проверяем: nginx приводит сюда по error_page, и её
    // адрес запроса намеренно не совпадает со скриптом.
    if ($rsScript !== $rsWanted && substr($rsScript, -8) !== '/404.php') {
        // Значения, заданные страницей, на 404 не поедут: разметка Organization
        // или canonical на главную — это уже дезинформация поиска, а не мелочь.
        unset($jsonLd, $headExtra, $canonical);
        $rs404 = $_SERVER['DOCUMENT_ROOT'] . '/404.php';
        if (is_file($rs404)) { include $rs404; exit; }
        http_response_code(404);
        exit;
    }
}

$site = dirname(__DIR__, 3) . '/content/site.php';
$site = is_file($site) ? (array)(require $site) : [];
$site += ['prodHost' => '', 'name' => '', 'url' => '/', 'title' => '', 'metaImage' => '',
          'metaImageW' => 0, 'metaImageH' => 0, 'themeColor' => '', 'preloadFonts' => [],
          'metrikaId' => null];

$pageTitle       = $pageTitle       ?? $site['title'];
$metaDescription = $metaDescription ?? '';
$pageUrl         = $pageUrl         ?? $site['url'];
$canonical       = $canonical       ?? $pageUrl;
$metaImage       = $metaImage       ?? $site['metaImage'];
$metaImageW      = $metaImageW      ?? $site['metaImageW'];
$metaImageH      = $metaImageH      ?? $site['metaImageH'];
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
$prodHost = strtolower((string)$site['prodHost']);
$host     = strtolower(preg_replace('/:\d+$/', '', $_SERVER['SERVER_NAME'] ?? ''));
$isProd   = ($prodHost !== '' && ($host === $prodHost || $host === 'www.' . $prodHost));
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
$preloadFonts = $preloadFonts ?? $site['preloadFonts'];

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
<meta property="og:site_name" content="<?= $e($site['name']) ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:url" content="<?= $e($pageUrl) ?>">
<meta property="og:title" content="<?= $e($pageTitle) ?>">
<meta property="og:description" content="<?= $e($metaDescription) ?>">
<?php if ($metaImage): ?>
<meta property="og:image" content="<?= $e($metaImage) ?>">
<meta property="og:image:width" content="<?= $e($metaImageW) ?>">
<meta property="og:image:height" content="<?= $e($metaImageH) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="/assets/img/icons/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/img/icons/apple-touch-icon.png">
<?php if ($site['themeColor']): ?>
<meta name="theme-color" content="<?= $e($site['themeColor']) ?>">
<?php endif; ?>

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
