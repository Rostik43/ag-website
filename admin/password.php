<?php
/** Смена пароля: переписывает строку с хешем в config.php. */
require_once __DIR__ . '/lib.php';
require_login();

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old'] ?? ''; $new = $_POST['new'] ?? ''; $new2 = $_POST['new2'] ?? '';
    if (!check_password($old))            $err = 'Текущий пароль указан неверно';
    elseif (mb_strlen($new) < 8)          $err = 'Новый пароль — минимум 8 символов';
    elseif ($new !== $new2)               $err = 'Пароли не совпали';
    else {
        $cfg = __DIR__ . '/config.php';
        $src = file_get_contents($cfg);
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $out = preg_replace(
            "/const ADMIN_PASSWORD_HASH = '.*?';/",
            "const ADMIN_PASSWORD_HASH = '" . $hash . "';",
            $src, 1, $n);
        if ($n === 1 && file_put_contents($cfg, $out) !== false) $msg = 'Пароль изменён. Запишите его в надёжном месте.';
        else $err = 'Не удалось записать config.php — проверьте права на файл';
    }
}
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Пароль · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?><a href="list.php?set=<?= $k ?>"><?= h($s['title']) ?></a><?php endforeach; ?>
    <a href="team.php">Команда</a><a href="texts.php">Тексты</a>
    <a href="/" target="_blank">Сайт ↗</a><a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap" style="max-width:520px">
  <h1>Пароль</h1>
  <p class="sub">Пароль один на всё бюро. Меняйте его, если он мог попасть к посторонним.</p>
  <?php if ($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>
  <form method="post" class="card">
    <div class="row"><label>Текущий пароль</label><input type="password" name="old" required></div>
    <div class="row"><label>Новый пароль (от 8 символов)</label><input type="password" name="new" required></div>
    <div class="row"><label>Новый пароль ещё раз</label><input type="password" name="new2" required></div>
    <button class="btn" type="submit">Сменить пароль</button>
  </form>
</div>
<footer class="bot"><div class="wrap">Изменения появляются на сайте сразу после сохранения</div></footer>
</body></html>
