<?php
/**
 * Точка входа формы. Вся логика — в engine/form: сайт только показывает,
 * куда писать журнал и чем отправлять.
 *
 * Без JS это обычный POST с редиректом обратно (PRG): после отправки
 * F5 не шлёт заявку второй раз. С JS — тот же адрес отвечает JSON.
 */
$root = dirname(__DIR__);
require $root . '/engine/form/form.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST', true, 405);
    exit;
}

$cfg      = require $root . '/config/form.php';
$leadForm = require $root . '/content/lead-form.php';
$result   = rs_form_handle($cfg, $leadForm, $_POST);

$wantsJson = str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
header('Cache-Control: no-store');

if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

rs_form_session();
$_SESSION['rs_form_result'] = $result;
if (!$result['ok']) { $_SESSION['rs_form_old'] = $_POST; }

$back = (string)($_SERVER['HTTP_REFERER'] ?? '/');
// После хоста — конец, путь или запрос: без этой границы проходил бы
// https://САЙТ.ru.evil.com/, а редирект делается и при провале CSRF.
if (!preg_match('~^https?://' . preg_quote((string)($_SERVER['HTTP_HOST'] ?? ''), '~') . '(?:[/?#]|$)~', $back)) { $back = '/'; }
header('Location: ' . strtok($back, '#') . '#lead-form', true, 303);
