<?php
/**
 * Обработчик формы заявки. Порядок действий важнее кода:
 *
 *   1. проверки (CSRF, частота, ловушка, длина, обязательность) — до всего;
 *   2. запись в журнал на диск — это первичная запись заявки;
 *   3. письмо.
 *
 * Если не удалась запись на диск, посетителю отказываем честно. Если не
 * удалось письмо — заявка уже лежит в журнале строкой с "mail": false,
 * её видно и можно переотправить, поэтому посетителю показываем успех.
 * Терять заявку молча — единственный по-настоящему дорогой исход.
 *
 * Журнал — JSONL, по объекту в строку, дописыванием под flock: единый
 * JSON-массив порвали бы две одновременные заявки. Лежит вне докрута
 * и вне гита. Это персональные данные: срок хранения — в политике сайта.
 *
 * Зависимостей нет. Журнал обязателен, каналы доставки — опция: нет
 * smtp.host или to в конфиге, и письмо просто не отправляется ("mail": null),
 * заявка всё равно принята. Телеграм добавится вторым вызовом в
 * rs_form_deliver(), остальное не меняется.
 */
require_once __DIR__ . '/smtp.php';

function rs_form_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
                                   'secure' => !empty($_SERVER['HTTPS'])]);
        session_start();
    }
}

/** Токен формы. Заодно запоминает время выдачи — по нему ловятся боты. */
function rs_form_token(): string
{
    rs_form_session();
    if (empty($_SESSION['rs_form_token'])) {
        $_SESSION['rs_form_token'] = bin2hex(random_bytes(16));
        $_SESSION['rs_form_born']  = time();
    }
    return $_SESSION['rs_form_token'];
}

function rs_form_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Частота по IP: счётчик в файле, базы здесь нет.
 *
 * Проверка и отметка разделены намеренно. Считаем принятые заявки, а не
 * попытки: иначе три опечатки подряд закрывают человеку форму на десять
 * минут — а это живой клиент, который просто ошибся в адресе. Отбитые
 * проверками попытки ничего не стоят и ничего не достигают, их не считаем.
 */
/**
 * Ключ лимита. IPv6 считается по /64: адресов внутри префикса у клиента
 * триллионы, и лимит по полному адресу обходится сменой хвоста,
 * а файлов в var/rate становилось бы по одному на каждый.
 */
function rs_form_rate_key(): string
{
    $bin = @inet_pton(rs_form_ip());
    return $bin !== false && strlen($bin) === 16 ? bin2hex(substr($bin, 0, 8)) : rs_form_ip();
}

function rs_form_rate_file(string $dir): ?string
{
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { return null; }
    return $dir . '/' . hash('sha256', rs_form_rate_key()) . '.json';
}

/** @return int[] отметки за последние сутки */
function rs_form_rate_hits(?string $file): array
{
    if ($file === null || !is_file($file)) { return []; }
    $now = time();
    $raw = @file_get_contents($file);
    return array_values(array_filter(array_map('intval', json_decode($raw ?: '[]', true) ?: []),
                                     static fn($t) => $t > $now - 86400));
}

/** Можно ли принять ещё одну заявку с этого адреса. */
function rs_form_rate_ok(string $dir, int $per10min, int $perDay): bool
{
    $hits = rs_form_rate_hits(rs_form_rate_file($dir));
    $now  = time();
    return count(array_filter($hits, static fn($t) => $t > $now - 600)) < $per10min
        && count($hits) < $perDay;
}

/** Отметить принятую заявку. */
function rs_form_rate_hit(string $dir): void
{
    $file = rs_form_rate_file($dir);
    if ($file === null) { return; }
    $fp = @fopen($file, 'c+');
    if (!$fp) { return; }
    try {
        if (!flock($fp, LOCK_EX)) { return; }
        $now  = time();
        $hits = array_values(array_filter(array_map('intval', json_decode(stream_get_contents($fp) ?: '[]', true) ?: []),
                                          static fn($t) => $t > $now - 86400));
        $hits[] = $now;
        ftruncate($fp, 0); rewind($fp); fwrite($fp, json_encode($hits));
        @chmod($file, 0600);
    } finally { flock($fp, LOCK_UN); fclose($fp); }
    rs_form_rate_purge($dir);
}

/**
 * Файлы старше суток уже ничего не считают — без чистки папка росла бы
 * на файл с каждого нового адреса. Раз в ~20 заявок, не на каждой.
 */
function rs_form_rate_purge(string $dir): void
{
    if (random_int(1, 20) !== 1) { return; }
    $limit = time() - 86400;
    foreach (glob($dir . '/*.json') ?: [] as $f) {
        if (@filemtime($f) < $limit) { @unlink($f); }
    }
}

/** Дописывает строку в журнал. false — заявку принимать нельзя. */
function rs_form_log(string $dir, array $record): bool
{
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { return false; }
    $file = $dir . '/' . date('Y-m') . '.jsonl';
    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) { return false; }
    $fp = @fopen($file, 'a');
    if (!$fp) { return false; }
    try {
        if (!flock($fp, LOCK_EX)) { return false; }
        $ok = fwrite($fp, $line . "\n") !== false;
        fflush($fp);
        @chmod($file, 0600);
        return $ok;
    } finally { flock($fp, LOCK_UN); fclose($fp); }
}

/**
 * Разбирает поля по описанию из content/lead-form.php — второго списка
 * полей не заводим, иначе он разойдётся с формой.
 * @return array{values: array<string,string>, errors: array<string,string>}
 */
function rs_form_read(array $leadForm, array $post): array
{
    $values = $errors = [];
    $specs  = array_merge($leadForm['fields'] ?? [], $leadForm['message_field'] ?? []);
    foreach ($specs as $f) {
        $name = (string)($f['name'] ?? '');
        if ($name === '') { continue; }
        $raw = (string)($post[$name] ?? '');
        $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
        if (!mb_check_encoding($raw, 'UTF-8')) { $errors[$name] = 'Непонятная кодировка'; continue; }
        $max = (int)($f['max'] ?? (empty($f['is_textarea']) ? 150 : 2000));
        $values[$name] = $raw;

        if (!empty($f['required']) && $raw === '')      { $errors[$name] = 'Заполните поле'; continue; }
        if ($raw === '')                                 { continue; }
        if (mb_strlen($raw) > $max)                      { $errors[$name] = "Не длиннее $max символов"; continue; }
        $type = (string)($f['input_type'] ?? 'text');
        if ($type === 'email' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) { $errors[$name] = 'Проверьте адрес'; }
        if ($type === 'tel' && !preg_match('/^[0-9+()\-\s]{6,30}$/u', $raw)) { $errors[$name] = 'Проверьте номер'; }
    }
    return ['values' => $values, 'errors' => $errors];
}

/**
 * Письмо и (позже) телеграм. Почта — опция: нет хоста или получателя в конфиге,
 * значит отправлять некуда, и это не ошибка. mail: null — не настроено,
 * false — настроено и не ушло, true — ушло.
 * @return array{mail:bool|null,error:string}
 */
function rs_form_deliver(array $cfg, array $leadForm, array $values): array
{
    $smtp = (array)($cfg['smtp'] ?? []);
    if (($smtp['host'] ?? '') === '' || !array_filter((array)($cfg['to'] ?? []))) {
        return ['mail' => null, 'error' => ''];
    }

    $lines = [];
    foreach (array_merge($leadForm['fields'] ?? [], $leadForm['message_field'] ?? []) as $f) {
        $name = (string)($f['name'] ?? '');
        if ($name === '' || ($values[$name] ?? '') === '') { continue; }
        $lines[] = ($f['label'] ?? $name) . ': ' . $values[$name];
    }
    $lines[] = '';
    $lines[] = 'Страница: ' . ($cfg['site'] ?? '');
    $lines[] = 'Время: ' . date('d.m.Y H:i');

    $emailField = '';
    foreach ($leadForm['fields'] ?? [] as $f) {
        if (($f['input_type'] ?? '') === 'email') { $emailField = (string)$f['name']; break; }
    }
    $nameField = (string)(($leadForm['fields'][0]['name'] ?? ''));

    $res = rs_smtp_send($smtp, [
        'to'         => (array)($cfg['to'] ?? []),
        'subject'    => (string)($cfg['subject'] ?? 'Заявка с сайта'),
        'text'       => implode("\n", $lines),
        'reply_to'   => (string)($values[$emailField] ?? ''),
        'reply_name' => (string)($values[$nameField] ?? ''),
    ]);
    return ['mail' => (bool)$res['ok'], 'error' => (string)$res['error']];
}

/**
 * Полный цикл. @return array{ok:bool, errors:array, message:string}
 */
function rs_form_handle(array $cfg, array $leadForm, array $post): array
{
    rs_form_session();
    $fail = static fn(string $m, array $e = []) => ['ok' => false, 'errors' => $e, 'message' => $m];

    $token = (string)($_SESSION['rs_form_token'] ?? '');
    if ($token === '' || !hash_equals($token, (string)($post['rs_token'] ?? '')))
        { return $fail('Форма устарела — обновите страницу и попробуйте ещё раз.'); }

    if (($post[$cfg['honeypot'] ?? 'rs_company_url'] ?? '') !== '')
        { return $fail('Заявка не отправлена.'); }                     // бот: причину не объясняем

    if (time() - (int)($_SESSION['rs_form_born'] ?? 0) < 3)
        { return $fail('Заявка не отправлена.'); }

    if (empty($post[$leadForm['name'] ?? 'your-consent']))
        { return $fail('Нужно согласие на обработку персональных данных.', ['_consent' => 'Отметьте согласие']); }

    if (!rs_form_rate_ok((string)$cfg['rate_dir'], (int)($cfg['rate']['per_10min'] ?? 3), (int)($cfg['rate']['per_day'] ?? 10)))
        { return $fail('Слишком много заявок подряд. Попробуйте через несколько минут.'); }

    ['values' => $values, 'errors' => $errors] = rs_form_read($leadForm, $post);
    if ($errors) { return $fail('Проверьте отмеченные поля.', $errors); }

    $record = [
        'ts'     => date('c'),
        'ip'     => rs_form_ip(),
        'ua'     => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300),
        'page'   => mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 300),
        'fields' => $values,
        'mail'   => null,
    ];
    $delivery = rs_form_deliver($cfg, $leadForm, $values);
    $record['mail'] = $delivery['mail'];
    if ($delivery['mail'] === false) { $record['mail_error'] = $delivery['error']; }

    if (!rs_form_log((string)$cfg['log_dir'], $record)) {
        // Журнал — первичная запись. Нет её — обещать приём нельзя.
        error_log('rs_form: журнал недоступен, заявка не сохранена');
        return $fail('Не удалось принять заявку. Напишите нам на почту — адрес в подвале.');
    }
    if ($delivery['mail'] === false) { error_log('rs_form: письмо не ушло — ' . $delivery['error']); }

    rs_form_rate_hit((string)$cfg['rate_dir']);
    unset($_SESSION['rs_form_token'], $_SESSION['rs_form_born']);   // одноразовый токен
    return ['ok' => true, 'errors' => [], 'message' => (string)($cfg['success'] ?? 'Заявка отправлена.')];
}
