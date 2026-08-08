<?php
require_once __DIR__ . '/lib.php';
require_login();

$set = $_GET['set'] ?? 'projects';
$c = set_conf($set);
$data = load_set($set);
$flash = $_GET['ok'] ?? '';

// удаление объекта
if (($_GET['del'] ?? '') !== '') {
    $i = find_index($data, $_GET['del']);
    if ($i >= 0) {
        $obj = $data[$i];
        // папку с фото не трогаем — на случай ошибки, только запись из данных
        array_splice($data, $i, 1);
        save_set($set, $data);
        header('Location: list.php?set=' . urlencode($set) . '&ok=' . urlencode('Объект «' . ($obj['name'] ?? '') . '» удалён'));
        exit;
    }
}
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($c['title']) ?> · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?>
      <a href="list.php?set=<?= $k ?>" class="<?= $k === $set ? 'on' : '' ?>"><?= h($s['title']) ?></a>
    <?php endforeach; ?>
    <a href="team.php">Команда</a>
    <a href="texts.php">Тексты</a>
    <a href="/" target="_blank">Сайт ↗</a>
    <a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap">
  <h1><?= h($c['title']) ?></h1>
  <p class="sub">Объектов в разделе: <?= count($data) ?>. Порядок в таблице — порядок на сайте.</p>
  <?php if ($flash): ?><div class="msg ok"><?= h($flash) ?></div><?php endif; ?>

  <p><a class="btn" href="edit.php?set=<?= $set ?>&new=1">+ Добавить объект</a></p>

  <table>
    <tr><th style="width:80px">Фото</th><th>Название</th><th>Данные</th><th style="width:70px">Фото</th><th></th></tr>
    <?php foreach ($data as $o):
      $dir = $o['dir'] ?? '';
      $first = $o['photos'][0] ?? '';
      $cover = $first ? '/' . $dir . '/thumb-' . $first : '';
      $meta = array_filter([$o['years'] ?? $o['year'] ?? '', $o['status'] ?? '', $o['type'] ?? '', $o['place'] ?? '']);
    ?>
    <tr>
      <td class="cover"><?php if ($cover): ?><img src="<?= h($cover) ?>" alt=""><?php endif; ?></td>
      <td class="nm"><?= h($o['name'] ?? '(без названия)') ?></td>
      <td class="meta"><?= h(implode(' · ', array_slice($meta, 0, 3))) ?></td>
      <td class="meta cnt"><?= count($o['photos'] ?? []) ?></td>
      <td class="act">
        <a class="btn sm ghost" href="edit.php?set=<?= $set ?>&slug=<?= urlencode($o['slug']) ?>">Редактировать</a>
        <a class="btn sm danger" href="list.php?set=<?= $set ?>&del=<?= urlencode($o['slug']) ?>"
           onclick="return confirm('Удалить «<?= h($o['name'] ?? '') ?>» с сайта? Фотографии останутся на сервере.')">Удалить</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<footer class="bot"><div class="wrap">Изменения появляются на сайте сразу после сохранения</div></footer>
</body></html>
