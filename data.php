<?php
/**
 * Отдаёт файлы данных сайта с правильным кешированием.
 *
 * Зачем: nginx на этом хостинге отдаёт .js напрямую, минуя Apache, и вешает
 * кеш на 45 дней — .htaccess на это не влияет. Из-за этого правки из админки
 * не доходили до посетителей неделями. PHP отдаётся с нашими заголовками,
 * поэтому данные ходят через этот файл.
 *
 * Отдаём с проверкой по времени изменения: если файл не менялся, браузер
 * получает короткий ответ «не изменилось» и берёт свою копию — трафика почти нет.
 */
$sets = ['projects', 'consortium', 'design', 'konkurs', 'team', 'texts'];

$f = $_GET['f'] ?? '';
if (!in_array($f, $sets, true)) { http_response_code(404); exit; }

$path = __DIR__ . '/' . $f . '-data.js';
if (!is_file($path)) { http_response_code(404); exit; }

$etag = '"' . filemtime($path) . '-' . filesize($path) . '"';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('ETag: ' . $etag);

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);   // не изменилось
    exit;
}

readfile($path);
