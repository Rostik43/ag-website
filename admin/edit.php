<?php
require_once __DIR__ . '/lib.php';
require_login();

$set = $_GET['set'] ?? 'projects';
$c = set_conf($set);
$data = load_set($set);
$isNew = isset($_GET['new']);
$slug = $_GET['slug'] ?? '';
$idx = $isNew ? -1 : find_index($data, $slug);
$obj = $idx >= 0 ? $data[$idx] : ['slug' => '', 'name' => '', 'photos' => [], 'dir' => ''];
$msg = ''; $err = '';

/* ── Сохранение полей ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    foreach ($c['fields'] as $key => [$label, $type, $required]) {
        $v = trim($_POST[$key] ?? '');
        if ($required && $v === '') { $err = "Поле «$label» обязательно"; break; }
        if ($v !== '') $obj[$key] = $v; else unset($obj[$key]);
    }
    if (!$err) {
        if ($idx < 0) {  // новый объект
            $obj['slug'] = unique_slug(slugify($obj['name']), $data);
            $obj['dir']  = $c['dir'] . '/' . $obj['slug'];
            $obj['photos'] = $obj['photos'] ?? [];
            if ($set !== 'projects') $obj['set'] = $set === 'consortium' ? 'consortium' : $set;
            @mkdir(ROOT . '/' . $obj['dir'], 0755, true);
            $data[] = $obj;
            $idx = count($data) - 1;
        } else {
            $data[$idx] = $obj;
        }
        save_set($set, $data);
        header('Location: edit.php?set=' . urlencode($set) . '&slug=' . urlencode($obj['slug']) . '&saved=1');
        exit;
    }
}
if (isset($_GET['saved'])) $msg = 'Сохранено. Изменения уже на сайте.';

$dirAbs = $obj['dir'] ? ROOT . '/' . $obj['dir'] : '';
$photos = $obj['photos'] ?? [];
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= $idx < 0 ? 'Новый объект' : h($obj['name']) ?> · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?>
      <a href="list.php?set=<?= $k ?>" class="<?= $k === $set ? 'on' : '' ?>"><?= h($s['title']) ?></a>
    <?php endforeach; ?>
    <a href="team.php">Команда</a><a href="texts.php">Тексты</a>
    <a href="/" target="_blank">Сайт ↗</a><a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap">
  <h1><?= $idx < 0 ? 'Новый объект' : h($obj['name']) ?></h1>
  <p class="sub"><a href="list.php?set=<?= $set ?>">← <?= h($c['title']) ?></a>
    <?php if ($idx >= 0): ?> · <a href="/project.html?p=<?= urlencode($obj['slug']) ?>" target="_blank">Посмотреть на сайте ↗</a><?php endif; ?>
  </p>
  <?php if ($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>

  <form method="post" class="card">
    <input type="hidden" name="action" value="save">
    <?php foreach ($c['fields'] as $key => [$label, $type, $required]):
      $val = $obj[$key] ?? ''; ?>
      <div class="row">
        <label for="f_<?= $key ?>"><?= h($label) ?><?= $required ? ' *' : '' ?></label>
        <?php if ($type === 'textarea'): ?>
          <textarea id="f_<?= $key ?>" name="<?= $key ?>"><?= h($val) ?></textarea>
        <?php elseif (str_starts_with($type, 'select:')): ?>
          <select id="f_<?= $key ?>" name="<?= $key ?>">
            <option value="">—</option>
            <?php foreach (explode(',', substr($type, 7)) as $opt): ?>
              <option<?= $val === $opt ? ' selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input id="f_<?= $key ?>" type="text" name="<?= $key ?>" value="<?= h($val) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn" type="submit">Сохранить</button>
  </form>

  <?php if ($idx >= 0): ?>
  <h2>Фотографии</h2>
  <p class="hint">Первая фотография — обложка в списке проектов. Порядок меняется перетаскиванием. Загружать можно любые файлы — размер и миниатюры сделаются сами.</p>

  <div class="drop" id="drop">
    Перетащите фотографии сюда или нажмите для выбора
    <input type="file" id="file" multiple accept="image/*" hidden>
    <div class="bar" id="bar"><i></i></div>
  </div>

  <div class="photos" id="photos">
    <?php foreach ($photos as $i => $f): ?>
      <div class="ph" draggable="true" data-f="<?= h($f) ?>">
        <img src="/<?= h($obj['dir']) ?>/thumb-<?= h($f) ?>" alt="">
        <span class="n"><?= $i + 1 ?></span>
        <?php if ($i === 0): ?><span class="cover">обложка</span><?php endif; ?>
        <button class="x" type="button">удалить</button>
      </div>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:20px"><a class="btn ghost" href="list.php?set=<?= $set ?>">Готово</a></p>
  <?php else: ?>
    <p class="hint">Сначала сохраните объект — потом появится загрузка фотографий.</p>
  <?php endif; ?>
</div>
<footer class="bot"><div class="wrap">Изменения появляются на сайте сразу после сохранения</div></footer>

<script>
const SET = <?= json_encode($set) ?>, SLUG = <?= json_encode($obj['slug'] ?? '') ?>;
const grid = document.getElementById('photos');

/* ── загрузка ── */
const drop = document.getElementById('drop'), file = document.getElementById('file'),
      bar = document.getElementById('bar'), barI = bar?.querySelector('i');
if (drop) {
  drop.onclick = () => file.click();
  drop.ondragover = e => { e.preventDefault(); drop.classList.add('over'); };
  drop.ondragleave = () => drop.classList.remove('over');
  drop.ondrop = e => { e.preventDefault(); drop.classList.remove('over'); upload(e.dataTransfer.files); };
  file.onchange = () => upload(file.files);
}
async function upload(files) {
  if (!files.length) return;
  bar.style.display = 'block'; barI.style.width = '0';
  let done = 0;
  for (const f of files) {
    const fd = new FormData();
    fd.append('set', SET); fd.append('slug', SLUG); fd.append('photo', f);
    try {
      const r = await fetch('upload.php', { method: 'POST', body: fd });
      const j = await r.json();
      if (!j.ok) alert('Не удалось загрузить ' + f.name + ': ' + (j.error || ''));
    } catch (_) { alert('Ошибка загрузки ' + f.name); }
    done++; barI.style.width = Math.round(done / files.length * 100) + '%';
  }
  location.reload();
}

/* ── удаление ── */
grid?.addEventListener('click', async e => {
  const btn = e.target.closest('.x'); if (!btn) return;
  const card = btn.closest('.ph');
  if (!confirm('Удалить эту фотографию?')) return;
  const fd = new FormData();
  fd.append('action', 'delete'); fd.append('set', SET); fd.append('slug', SLUG); fd.append('file', card.dataset.f);
  const r = await fetch('actions.php', { method: 'POST', body: fd });
  const j = await r.json();
  if (j.ok) location.reload(); else alert('Ошибка: ' + (j.error || ''));
});

/* ── порядок перетаскиванием ── */
let dragged = null;
grid?.addEventListener('dragstart', e => { dragged = e.target.closest('.ph'); dragged?.classList.add('drag'); });
grid?.addEventListener('dragend', () => { dragged?.classList.remove('drag'); dragged = null; saveOrder(); });
grid?.addEventListener('dragover', e => {
  e.preventDefault();
  const over = e.target.closest('.ph');
  if (!over || !dragged || over === dragged) return;
  const r = over.getBoundingClientRect();
  const after = (e.clientX - r.left) > r.width / 2;
  grid.insertBefore(dragged, after ? over.nextSibling : over);
});
async function saveOrder() {
  const order = [...grid.querySelectorAll('.ph')].map(p => p.dataset.f);
  const fd = new FormData();
  fd.append('action', 'order'); fd.append('set', SET); fd.append('slug', SLUG); fd.append('order', JSON.stringify(order));
  const r = await fetch('actions.php', { method: 'POST', body: fd });
  const j = await r.json();
  if (j.ok) location.reload(); else alert('Не удалось сохранить порядок');
}
</script>
</body></html>
