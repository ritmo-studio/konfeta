<?php
/**
 * Минимальный SMTP-клиент движка. Зависимостей нет сознательно: composer
 * в сборку без базы и без плагинов не заводим, а всё, что здесь нужно —
 * это ESMTP, STARTTLS и AUTH. Письмо уходит через ящик заказчика на его
 * же домене: SPF и DKIM там уже настроены почтовым провайдером, и письмо
 * проходит как своё. mail() с VPS уходит в спам молча — ради этого всё.
 *
 * Заголовки не склеиваются из полей формы: любое значение, попадающее
 * в заголовок, проходит через rs_smtp_header_safe() — перевод строки
 * в поле «Имя» иначе дописал бы в письмо чужой Bcc.
 *
 * rs_smtp_send() ничего не бросает: возвращает ['ok'=>bool,'error'=>string].
 * Решение, что делать с неудачей, принимает вызывающий — заявка к этому
 * моменту уже записана в журнал и не теряется.
 */

/** Срезает CR и LF: значение пойдёт в заголовок письма. */
function rs_smtp_header_safe(string $v): string
{
    return trim(str_replace(["\r", "\n", "\0"], ' ', $v));
}

/** Кодирует заголовок в =?UTF-8?B?…?= , если в нём есть не-ASCII. */
function rs_smtp_header_encode(string $v): string
{
    $v = rs_smtp_header_safe($v);
    if (preg_match('//u', $v) !== 1) { $v = ''; }
    return preg_match('/[^\x20-\x7E]/', $v) ? '=?UTF-8?B?' . base64_encode($v) . '?=' : $v;
}

/** "Имя" <адрес> — имя кодируется, адрес проверяется. */
function rs_smtp_address(string $email, string $name = ''): string
{
    $email = rs_smtp_header_safe($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return ''; }
    return $name === '' ? $email : rs_smtp_header_encode($name) . ' <' . $email . '>';
}

/** Читает ответ сервера (с учётом многострочных) и сверяет код. */
function rs_smtp_expect($fp, string $codes, ?string &$reply = null): bool
{
    $reply = '';
    while (($line = fgets($fp, 1024)) !== false) {
        $reply .= $line;
        if (strlen($line) < 4 || $line[3] !== '-') { break; }
    }
    return $reply !== '' && str_contains($codes, substr($reply, 0, 3));
}

function rs_smtp_cmd($fp, string $cmd, string $codes, ?string &$reply = null): bool
{
    fwrite($fp, $cmd . "\r\n");
    return rs_smtp_expect($fp, $codes, $reply);
}

/**
 * @param array $cfg host, port, secure ('ssl'|'tls'|''), user, pass, from, from_name, helo, timeout
 * @param array $msg to (array адресов), subject, text, reply_to, reply_name
 */
function rs_smtp_send(array $cfg, array $msg): array
{
    $host    = (string)($cfg['host'] ?? '');
    $port    = (int)($cfg['port'] ?? 465);
    $secure  = strtolower(trim((string)($cfg['secure'] ?? 'ssl')));
    $timeout = (int)($cfg['timeout'] ?? 15);
    $helo    = rs_smtp_header_safe((string)($cfg['helo'] ?? 'localhost'));
    $from    = rs_smtp_header_safe((string)($cfg['from'] ?? ''));
    $to      = array_values(array_filter(array_map('rs_smtp_header_safe', (array)($msg['to'] ?? [])),
                                         static fn($a) => (bool)filter_var($a, FILTER_VALIDATE_EMAIL)));

    if ($host === '' || $from === '' || !$to) { return ['ok' => false, 'error' => 'не задан host, from или получатели']; }

    $dsn = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['SNI_enabled' => true, 'peer_name' => $host]]);
    $fp  = @stream_socket_client($dsn, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { return ['ok' => false, 'error' => "соединение с $dsn: $errstr ($errno)"]; }
    stream_set_timeout($fp, $timeout);

    // Текст ошибки уходит в журнал и в error_log, поэтому в нём всегда виден адрес:
    // «не ответил на EHLO» без имени сервера через месяц ничего не объясняет.
    $fail = static function (string $what, string $reply = '') use ($fp, $dsn) {
        @fwrite($fp, "QUIT\r\n"); @fclose($fp);
        return ['ok' => false, 'error' => trim($dsn . ' — ' . $what . ' ' . str_replace(["\r", "\n"], ' ', $reply))];
    };

    if (!rs_smtp_expect($fp, '220', $r))                 { return $fail('приветствие', $r); }
    if (!rs_smtp_cmd($fp, "EHLO $helo", '250', $r))      { return $fail('EHLO', $r); }

    if ($secure === 'tls') {
        if (!rs_smtp_cmd($fp, 'STARTTLS', '220', $r))    { return $fail('STARTTLS', $r); }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { return $fail('TLS не поднялся'); }
        if (!rs_smtp_cmd($fp, "EHLO $helo", '250', $r))  { return $fail('EHLO после STARTTLS', $r); }
    }

    if (!empty($cfg['user'])) {
        // Пароль уходит base64, то есть открытым текстом: без ssl/tls не слать.
        // Опечатка в secure должна ронять отправку, а не молча снимать шифрование.
        if ($secure !== 'ssl' && $secure !== 'tls') { return $fail('AUTH без шифрования: secure должен быть ssl или tls'); }
        if (!rs_smtp_cmd($fp, 'AUTH LOGIN', '334', $r))                              { return $fail('AUTH LOGIN', $r); }
        if (!rs_smtp_cmd($fp, base64_encode((string)$cfg['user']), '334', $r))       { return $fail('логин', $r); }
        if (!rs_smtp_cmd($fp, base64_encode((string)($cfg['pass'] ?? '')), '235', $r)) { return $fail('пароль не принят', $r); }
    }

    if (!rs_smtp_cmd($fp, "MAIL FROM:<$from>", '250', $r)) { return $fail('MAIL FROM', $r); }
    foreach ($to as $rcpt) {
        if (!rs_smtp_cmd($fp, "RCPT TO:<$rcpt>", '250 251', $r)) { return $fail("RCPT TO <$rcpt>", $r); }
    }
    if (!rs_smtp_cmd($fp, 'DATA', '354', $r)) { return $fail('DATA', $r); }

    $headers = [
        'Date: ' . date('r'),
        'From: ' . (rs_smtp_address($from, (string)($cfg['from_name'] ?? '')) ?: $from),
        'To: ' . implode(', ', $to),
        'Subject: ' . rs_smtp_header_encode((string)($msg['subject'] ?? '')),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $helo . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $replyTo = rs_smtp_address((string)($msg['reply_to'] ?? ''), (string)($msg['reply_name'] ?? ''));
    if ($replyTo !== '') { array_splice($headers, 3, 0, ['Reply-To: ' . $replyTo]); }

    $body = chunk_split(base64_encode((string)($msg['text'] ?? '')), 76, "\r\n");
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    // Точка в начале строки экранируется: иначе сервер примет её за конец письма.
    $data = preg_replace('/^\./m', '..', $data);

    fwrite($fp, $data . "\r\n.\r\n");
    if (!rs_smtp_expect($fp, '250', $r)) { return $fail('письмо не принято', $r); }

    rs_smtp_cmd($fp, 'QUIT', '221');
    fclose($fp);
    return ['ok' => true, 'error' => ''];
}
