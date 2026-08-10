<?php
/**
 * Приём заявок с формы «Контакты».
 * Данные обрабатываются на сервере сайта в РФ и никуда за рубеж не передаются.
 * Ответ — JSON, форма отправляется через fetch.
 */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

const MAIL_TO   = 'ag_pg@mail.ru';                 // куда падают заявки
const MAIL_FROM = 'site@ag-pg.ru';                 // почтовый ящик на домене (создать в панели хостинга)
const LOG_DIR   = __DIR__ . '/admin/leads';        // копия заявок на случай сбоя почты
const LIMIT_SEC = 30;                              // не чаще одной заявки с адреса раз в 30 секунд

function out(bool $ok, string $msg = ''): never {
    echo json_encode(['ok' => $ok, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Неверный запрос');

// ловушка для роботов: поле скрыто, человек его не заполнит
if (trim($_POST['_honey'] ?? '') !== '') out(true);

// простое ограничение частоты по IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stamp = sys_get_temp_dir() . '/agpg_' . md5($ip);
if (is_file($stamp) && (time() - filemtime($stamp)) < LIMIT_SEC) {
    out(false, 'Заявка уже отправлена, подождите немного');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$type    = trim($_POST['type'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = ($_POST['consent'] ?? '') !== '';

if ($name === '' || $message === '')                       out(false, 'Заполните имя и сообщение');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))            out(false, 'Проверьте адрес почты');
if (!$consent)                                             out(false, 'Нужно согласие на обработку данных');
if (mb_strlen($name) > 150 || mb_strlen($message) > 5000)  out(false, 'Слишком длинный текст');

// перевод строки в заголовке — способ подделать письмо, вырезаем
$clean = fn(string $s): string => str_replace(["\r", "\n"], ' ', $s);
$name  = $clean($name);
$email = $clean($email);
$type  = $clean($type);

$when = date('d.m.Y H:i');
$body = "Заявка с сайта ag-pg.ru\n"
      . str_repeat('—', 32) . "\n"
      . "Имя:          $name\n"
      . "Email:        $email\n"
      . ($type !== '' ? "Тип проекта:  $type\n" : '')
      . "Время:        $when\n\n"
      . "Сообщение:\n$message\n";

// копия на диск — если почта не дойдёт, заявка не потеряется
if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0755, true);
@file_put_contents(LOG_DIR . '/' . date('Y-m') . '.log',
    $body . "\n" . str_repeat('=', 60) . "\n\n", FILE_APPEND | LOCK_EX);

// На локальной машине письма не отправляем — иначе тестовые заявки уходят заказчику
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host) === 1;
if ($isLocal) {
    @touch($stamp);
    out(true);   // заявка записана в лог, письмо не отправляется
}

$headers = [
    'From: AG Project Group <' . MAIL_FROM . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: ag-pg.ru',
];
$subject = '=?UTF-8?B?' . base64_encode('Заявка с сайта — ' . $name) . '?=';
$sent = @mail(MAIL_TO, $subject, $body, implode("\r\n", $headers), '-f' . MAIL_FROM);

@touch($stamp);

// заявка уже сохранена на диске, поэтому для посетителя это успех в любом случае
out(true);
