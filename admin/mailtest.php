<?php
/** Проверка отправки почты с сервера. Доступна только после входа. */
require_once __DIR__ . '/lib.php';
require_login();

const TEST_TO = 'ag_pg@mail.ru';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '') ?: TEST_TO;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $results[] = ['Адрес указан неверно', false, ''];
    } else {
        $when = date('d.m.Y H:i:s');
        $variants = [
            'С адреса site@ag-pg.ru (как в форме)' => ['site@ag-pg.ru', true],
            'С адреса site@ag-pg.ru, без параметра -f' => ['site@ag-pg.ru', false],
            'С адреса noreply@ag-pg.ru' => ['noreply@ag-pg.ru', true],
        ];
        foreach ($variants as $label => [$from, $useF]) {
            $headers = [
                'From: AG Project Group <' . $from . '>',
                'Reply-To: ' . $from,
                'Content-Type: text/plain; charset=UTF-8',
            ];
            $subj = '=?UTF-8?B?' . base64_encode("Проверка почты — $label") . '?=';
            $body = "Это проверочное письмо с сайта ag-pg.ru.\n\nВариант: $label\nОтправитель: $from\nВремя: $when\n";
            $ok = $useF
                ? @mail($to, $subj, $body, implode("\r\n", $headers), '-f' . $from)
                : @mail($to, $subj, $body, implode("\r\n", $headers));
            $results[] = [$label, $ok, $from];
        }
    }
}

$mailExists = function_exists('mail');
$sendmail = ini_get('sendmail_path');
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Проверка почты · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?><a href="list.php?set=<?= $k ?>"><?= h($s['title']) ?></a><?php endforeach; ?>
    <a href="team.php">Команда</a><a href="texts.php">Тексты</a>
    <a href="help.php">Инструкция</a><a href="/" target="_blank">Сайт ↗</a><a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap" style="max-width:720px">
  <h1>Проверка почты</h1>
  <p class="sub">Служебная страница. Показывает, умеет ли сервер отправлять письма — от этого зависит, дойдут ли заявки с формы.</p>

  <h2>Что умеет сервер</h2>
  <div class="card">
    <p style="margin:0 0 8px">Функция отправки писем: <strong><?= $mailExists ? 'доступна' : 'НЕДОСТУПНА' ?></strong></p>
    <p style="margin:0">Почтовая программа сервера: <strong><?= $sendmail ? h($sendmail) : 'не указана' ?></strong></p>
  </div>

  <h2>Отправить проверочные письма</h2>
  <form method="post" class="card">
    <div class="row">
      <label for="to">Куда отправить</label>
      <input id="to" type="text" name="to" value="<?= h($_POST['to'] ?? TEST_TO) ?>">
    </div>
    <button class="btn" type="submit">Отправить три варианта</button>
  </form>

  <?php if ($results): ?>
    <h2>Результат</h2>
    <table>
      <tr><th>Вариант</th><th style="width:150px">Сервер принял</th></tr>
      <?php foreach ($results as [$label, $ok, $from]): ?>
        <tr>
          <td class="nm"><?= h($label) ?><div class="meta" style="font-weight:400"><?= h($from) ?></div></td>
          <td><strong style="color:<?= $ok ? '#2f7d4f' : '#b3261e' ?>"><?= $ok ? 'да' : 'нет' ?></strong></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p class="hint">«Да» означает только то, что сервер принял письмо в очередь. Дошло ли оно — проверьте почтовый ящик, в том числе папку «Спам». Письма могут идти до нескольких минут.</p>
  <?php endif; ?>
</div>
<footer class="bot"><div class="wrap">Служебная страница · после проверки её можно удалить</div></footer>
</body></html>
