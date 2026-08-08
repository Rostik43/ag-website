<?php
/** Тексты страниц. Оригинал живёт в HTML, здесь хранятся только изменённые значения. */
require_once __DIR__ . '/lib.php';
require_login();

/** Что можно править: ключ => [страница, файл, подпись, многострочное?] */
const TEXTS = [
  'Бюро' => [
    'buro.lead'             => ['buro.html', 'Вступление под заголовком', true],
    'buro.founders_name'    => ['buro.html', 'Основатели — имена', false],
    'buro.founders_text'    => ['buro.html', 'Основатели — описание', true],
    'buro.founders_roles'   => ['buro.html', 'Основатели — должности', false],
    'buro.philosophy_title' => ['buro.html', 'Философия — заголовок', true],
    'buro.philosophy_1'     => ['buro.html', 'Философия — левый абзац', true],
    'buro.philosophy_2'     => ['buro.html', 'Философия — правый абзац', true],
  ],
  'Контакты' => [
    'contact.lead'    => ['contact.html', 'Вступление под заголовком', true],
    'contact.address' => ['contact.html', 'Адрес', true],
    'contact.office'  => ['contact.html', 'Офис, этаж', false],
    'contact.email'   => ['contact.html', 'Почта', false],
    'contact.phone'   => ['contact.html', 'Телефон', false],
    'contact.hours'   => ['contact.html', 'Часы работы', false],
  ],
];

$file = ROOT . '/texts-data.js';
$msg = '';

function texts_load(string $file): array {
    if (!is_file($file)) return [];
    $raw = file_get_contents($file);
    $a = strpos($raw, '{'); $b = strrpos($raw, '}');
    if ($a === false || $b === false) return [];
    $d = json_decode(substr($raw, $a, $b - $a + 1), true);
    return is_array($d) ? $d : [];
}
function texts_save(string $file, array $d): void {
    if (is_file($file)) @copy($file, ROOT . '/admin/backup/texts-data.js.bak');
    file_put_contents($file, 'window.AG_TEXTS = ' .
        json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT) . ";\n");
}
/** Исходный текст прямо из HTML — чтобы показать его, пока правок не было. */
function texts_default(string $page, string $key): string {
    static $cache = [];
    $cache[$page] ??= @file_get_contents(ROOT . '/' . $page) ?: '';
    if (!preg_match('/data-t="' . preg_quote($key, '/') . '"[^>]*>(.*?)<\/[a-z0-9]+>/si', $cache[$page], $m)) return '';
    return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $m[1])), ENT_QUOTES, 'UTF-8'));
}

$vals = texts_load($file);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = [];
    foreach (TEXTS as $group) foreach ($group as $key => [$page, $label, $multi]) {
        $v = trim($_POST['t'][$key] ?? '');
        $def = texts_default($page, $key);
        // если текст совпал с исходным — переопределение не храним
        if ($v !== '' && $v !== $def) $new[$key] = $v;
    }
    $vals = $new;
    texts_save($file, $vals);
    $msg = 'Сохранено. Изменения уже на сайте.';
}
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Тексты · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?><a href="list.php?set=<?= $k ?>"><?= h($s['title']) ?></a><?php endforeach; ?>
    <a href="team.php">Команда</a><a href="texts.php" class="on">Тексты</a>
    <a href="help.php">Инструкция</a><a href="/" target="_blank">Сайт ↗</a><a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap">
  <h1>Тексты страниц</h1>
  <p class="sub">Правится текст на страницах «Бюро» и «Контакты». Перенос строки в поле — перенос строки на сайте.</p>
  <?php if ($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>

  <form method="post">
    <?php foreach (TEXTS as $group => $items): ?>
      <h2><?= h($group) ?></h2>
      <div class="card">
        <?php foreach ($items as $key => [$page, $label, $multi]):
          $def = texts_default($page, $key);
          $val = $vals[$key] ?? $def; ?>
          <div class="row">
            <label for="k_<?= h($key) ?>"><?= h($label) ?><?= isset($vals[$key]) ? ' · изменено' : '' ?></label>
            <?php if ($multi): ?>
              <textarea id="k_<?= h($key) ?>" name="t[<?= h($key) ?>]"><?= h($val) ?></textarea>
            <?php else: ?>
              <input id="k_<?= h($key) ?>" type="text" name="t[<?= h($key) ?>]" value="<?= h($val) ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <p><button class="btn" type="submit">Сохранить тексты</button>
       <a class="btn ghost" href="/contact.html" target="_blank" style="margin-left:10px">Посмотреть сайт ↗</a></p>
  </form>

  <h2>Пароль</h2>
  <p class="hint"><a href="password.php">Сменить пароль для входа в эту панель →</a></p>
</div>
<footer class="bot"><div class="wrap">Изменения появляются на сайте сразу после сохранения</div></footer>
</body></html>
