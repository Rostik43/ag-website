<?php
require_once __DIR__ . '/lib.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (check_password($_POST['password'])) {
        $_SESSION['ag_admin'] = true;
        session_regenerate_id(true);
        header('Location: list.php?set=projects'); exit;
    }
    $err = 'Неверный пароль';
    sleep(1);
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }
if (is_logged()) { header('Location: list.php?set=projects'); exit; }
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Вход · Управление сайтом AG Project Group</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="login">
  <a class="logo" href="/">AG Project Group<small>управление сайтом</small></a>
  <h1>Вход</h1>
  <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>
  <form method="post" class="card">
    <div class="row">
      <label for="p">Пароль</label>
      <input id="p" type="password" name="password" autofocus required>
    </div>
    <button class="btn" type="submit">Войти</button>
  </form>
  <p class="hint">Раздел для сотрудников бюро. Здесь меняются проекты, фотографии и тексты сайта.</p>
</div>
</body></html>
