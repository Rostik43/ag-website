<?php
/**
 * Приём заявок с формы «Контакты».
 * Данные обрабатываются на сервере сайта в РФ и за рубеж не передаются.
 * Ответ — JSON, форма отправляется через fetch.
 */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

const MAIL_TO   = 'ag_pg@mail.ru';                 // куда падают заявки
const MAIL_FROM = 'site@ag-pg.ru';                 // адрес отправителя на домене
const LOG_DIR   = __DIR__ . '/admin/leads';        // копия заявок и журнал отсева
const LIMIT_SEC = 30;                              // не чаще одной заявки с адреса
const SECRET    = 'c87c458b4ddd4819cd6348e1d80c971f';                     // ключ подписи пропуска
const MIN_FILL  = 4;                               // быстрее человек форму не заполнит
const MAX_AGE   = 86400;                           // пропуск живёт сутки

function out(bool $ok, string $msg = ''): never {
    echo json_encode(['ok' => $ok, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Пропуск: время выдачи + подпись. Подделать без ключа нельзя. */
function make_pass(): string {
    $t = time();
    return $t . '.' . hash_hmac('sha256', (string)$t, SECRET);
}
function check_pass(string $p): string {
    $parts = explode('.', $p, 2);
    if (count($parts) !== 2) return 'нет пропуска';
    [$t, $sig] = $parts;
    if (!ctype_digit($t)) return 'нет пропуска';
    if (!hash_equals(hash_hmac('sha256', $t, SECRET), $sig)) return 'нет пропуска';
    $age = time() - (int)$t;
    if ($age < MIN_FILL) return 'слишком быстро';
    if ($age > MAX_AGE)  return 'страница открыта давно';
    return '';
}

/** Журнал отсева — чтобы видеть, что и почему не прошло. */
function reject(string $why, array $d): never {
    @file_put_contents(LOG_DIR . '/otsev.log',
        date('d.m.Y H:i') . " | $why | " . ($d['name'] ?? '') . ' | ' . ($d['email'] ?? '')
        . ' | ' . mb_substr(str_replace("\n", ' ', $d['message'] ?? ''), 0, 90) . "\n",
        FILE_APPEND | LOCK_EX);
    out(true);   // боту сообщаем «принято», чтобы он не подбирал обход
}

// Выдача пропуска: страница запрашивает его при загрузке
if (($_GET['pass'] ?? '') !== '') { echo json_encode(['pass' => make_pass()]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Неверный запрос');

if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0755, true);

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$type    = trim($_POST['type'] ?? '');
$message = trim($_POST['message'] ?? '');
$d = ['name' => $name, 'email' => $email, 'message' => $message];

// 1. Две ловушки: скрытые поля, которые человек не видит, а бот заполняет
if (trim($_POST['_honey'] ?? '') !== '')   reject('ловушка _honey', $d);
if (trim($_POST['website'] ?? '') !== '')  reject('ловушка website', $d);

// 2. Пропуск со страницы: бот, шлющий запрос напрямую, его не получит
$why = check_pass($_POST['pass'] ?? '');
// Человеку, у которого страница висела открытой сутки, честно говорим обновить —
// молча терять его письмо нельзя. Остальные причины — молчаливый отсев.
if ($why === 'страница открыта давно') out(false, 'Страница была открыта слишком долго. Обновите её и отправьте снова');
if ($why !== '') reject($why, $d);

// 3. Ограничение частоты по адресу
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stamp = sys_get_temp_dir() . '/agpg_' . md5($ip);
if (is_file($stamp) && (time() - filemtime($stamp)) < LIMIT_SEC) {
    out(false, 'Заявка уже отправлена, подождите немного');
}

// 4. Обычная проверка полей
if ($name === '' || $message === '')                       out(false, 'Заполните имя и сообщение');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))            out(false, 'Проверьте адрес почты');
if (($_POST['consent'] ?? '') === '')                      out(false, 'Нужно согласие на обработку данных');
if (mb_strlen($name) > 150 || mb_strlen($message) > 5000)  out(false, 'Слишком длинный текст');

// 5. Содержимое: ссылка в письме без единой русской буквы — почти наверняка спам.
//    Русский текст со ссылкой проходит: клиент может дать ссылку на свой участок.
$hasLink = preg_match('~https?://|www\.|\b[a-z0-9-]+\.(?:com|org|net|info|xyz|top|ru|su)\b~iu', $message . ' ' . $name);
$hasCyr  = preg_match('/[а-яё]/iu', $message);
if ($hasLink && !$hasCyr) reject('ссылка без русского текста', $d);

// 6. Типичные фразы рассылок про продвижение
if (preg_match('/(search index|seo|backlink|листинг|продвижени|раскрутк|casino|crypto|traffic to your)/iu', $message)
    && !$hasCyr) reject('рекламная рассылка', $d);

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
@file_put_contents(LOG_DIR . '/' . date('Y-m') . '.log',
    $body . "\n" . str_repeat('=', 60) . "\n\n", FILE_APPEND | LOCK_EX);

// На локальной машине письма не отправляем — иначе тестовые заявки уходят заказчику
$host = $_SERVER['HTTP_HOST'] ?? '';
if (preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host)) {
    @touch($stamp);
    out(true);
}

$headers = [
    'From: AG Project Group <' . MAIL_FROM . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: ag-pg.ru',
];
$subject = '=?UTF-8?B?' . base64_encode('Заявка с сайта — ' . $name) . '?=';
@mail(MAIL_TO, $subject, $body, implode("\r\n", $headers), '-f' . MAIL_FROM);

@touch($stamp);
out(true);
