<?php
/** Удаление фотографии и сохранение нового порядка. Ответ — JSON. */
ini_set('display_errors', '0');   // ответ должен быть чистым JSON
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) { echo json_encode(['ok' => false, 'error' => 'Сессия закончилась, войдите заново']); exit; }

$action = $_POST['action'] ?? '';
$set    = $_POST['set'] ?? '';
$slug   = $_POST['slug'] ?? '';
$c      = set_conf($set);
$data   = load_set($set);
$i      = find_index($data, $slug);
if ($i < 0) { echo json_encode(['ok' => false, 'error' => 'Объект не найден']); exit; }

$photos = $data[$i]['photos'] ?? [];
$dirAbs = ROOT . '/' . $data[$i]['dir'];

if ($action === 'delete') {
    $file = basename($_POST['file'] ?? '');          // basename — защита от путей вида ../
    if (!in_array($file, $photos, true)) { echo json_encode(['ok' => false, 'error' => 'Файл не найден']); exit; }
    delete_photo($dirAbs, $file);
    $data[$i]['photos'] = array_values(array_diff($photos, [$file]));
    save_set($set, $data);
    echo json_encode(['ok' => true, 'total' => count($data[$i]['photos'])]); exit;
}

if ($action === 'order') {
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (!is_array($order)) { echo json_encode(['ok' => false, 'error' => 'Неверный порядок']); exit; }
    $order = array_values(array_filter(array_map('basename', $order), fn($f) => in_array($f, $photos, true)));
    // на случай, если что-то потерялось — дописываем недостающие в конец
    foreach ($photos as $f) if (!in_array($f, $order, true)) $order[] = $f;
    $data[$i]['photos'] = $order;
    save_set($set, $data);
    echo json_encode(['ok' => true]); exit;
}

echo json_encode(['ok' => false, 'error' => 'Неизвестное действие']);
