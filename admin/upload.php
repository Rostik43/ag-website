<?php
/** Приём одной фотографии: обработка, сохранение, запись в данные. Ответ — JSON. */
ini_set('display_errors', '0');   // ответ должен быть чистым JSON
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) { echo json_encode(['ok' => false, 'error' => 'Сессия закончилась, войдите заново']); exit; }

$set  = $_POST['set'] ?? '';
$slug = $_POST['slug'] ?? '';
$c    = set_conf($set);
$data = load_set($set);
$i    = find_index($data, $slug);

if ($i < 0) { echo json_encode(['ok' => false, 'error' => 'Объект не найден']); exit; }
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE => 'файл слишком большой',
        UPLOAD_ERR_FORM_SIZE => 'файл слишком большой',
        UPLOAD_ERR_PARTIAL => 'файл передан не полностью',
        UPLOAD_ERR_NO_FILE => 'файл не передан',
    ];
    $e = $codes[$_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'ошибка загрузки';
    echo json_encode(['ok' => false, 'error' => $e]); exit;
}

$dirAbs = ROOT . '/' . $data[$i]['dir'];
$name = add_photo($_FILES['photo']['tmp_name'], $dirAbs);
if (!$name) { echo json_encode(['ok' => false, 'error' => 'не похоже на картинку (нужен JPG, PNG или WEBP)']); exit; }

$data[$i]['photos'] = array_values(array_merge($data[$i]['photos'] ?? [], [$name]));
save_set($set, $data);

echo json_encode(['ok' => true, 'file' => $name, 'total' => count($data[$i]['photos'])]);
