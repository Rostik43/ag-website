<?php
/** Команда: добавить человека, поменять должность, порядок, удалить. */
require_once __DIR__ . '/lib.php';
require_login();

$file = ROOT . '/team-data.js';
$dirAbs = ROOT . '/assets/team';
$msg = ''; $err = '';

function team_load(string $file): array {
    if (!is_file($file)) return [];
    $raw = file_get_contents($file);
    $a = strpos($raw, '['); $b = strrpos($raw, ']');
    if ($a === false || $b === false) return [];
    $d = json_decode(substr($raw, $a, $b - $a + 1), true);
    return is_array($d) ? $d : [];
}
function team_save(string $file, array $d): void {
    if (is_file($file)) @copy($file, ROOT . '/admin/backup/team-data.js.bak');
    file_put_contents($file, 'window.AG_TEAM = ' .
        json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n");
}
/** Портрет: квадрат 900px в цвете + такой же в ч/б. */
function team_photo(string $tmp, string $dirAbs, string $slug): bool {
    $im = read_image($tmp);
    if (!$im) return false;
    $ok = save_square($im, "$dirAbs/$slug.jpg", 900, 88);
    imagefilter($im, IMG_FILTER_GRAYSCALE);
    imagefilter($im, IMG_FILTER_CONTRAST, -8);
    $ok2 = save_square($im, "$dirAbs/$slug-bw.jpg", 900, 88);
    imagedestroy($im);
    return $ok && $ok2;
}

$team = team_load($file);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        if ($name === '') $err = 'Укажите имя';
        elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) $err = 'Выберите фотографию';
        else {
            $slug = $name; $i = 2;
            $base = slugify($name);
            $slug = $base;
            while (in_array($slug, array_column($team, 'slug'), true)) { $slug = $base . '-' . $i; $i++; }
            if (!team_photo($_FILES['photo']['tmp_name'], $dirAbs, $slug)) $err = 'Не удалось обработать фотографию';
            else {
                $team[] = ['slug' => $slug, 'name' => $name, 'role' => $role];
                team_save($file, $team);
                $msg = 'Добавлен: ' . $name;
            }
        }
    }

    if ($act === 'save') {
        $names = $_POST['name'] ?? []; $roles = $_POST['role'] ?? [];
        foreach ($team as $k => $p) {
            if (isset($names[$p['slug']])) $team[$k]['name'] = trim($names[$p['slug']]);
            if (isset($roles[$p['slug']])) $team[$k]['role'] = trim($roles[$p['slug']]);
        }
        // порядок
        $order = array_filter(explode(',', $_POST['order'] ?? ''));
        if ($order) {
            $byslug = [];
            foreach ($team as $p) $byslug[$p['slug']] = $p;
            $sorted = [];
            foreach ($order as $s) if (isset($byslug[$s])) { $sorted[] = $byslug[$s]; unset($byslug[$s]); }
            foreach ($byslug as $p) $sorted[] = $p;
            $team = $sorted;
        }
        team_save($file, $team);
        $msg = 'Сохранено. Изменения уже на сайте.';
    }

    if ($act === 'photo') {                       // замена фотографии
        $slug = $_POST['slug'] ?? '';
        if (in_array($slug, array_column($team, 'slug'), true)
            && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK
            && team_photo($_FILES['photo']['tmp_name'], $dirAbs, $slug)) {
            $msg = 'Фотография заменена';
        } else $err = 'Не удалось заменить фотографию';
    }

    if ($act === 'del') {
        $slug = $_POST['slug'] ?? '';
        foreach ($team as $k => $p) if ($p['slug'] === $slug) {
            @unlink("$dirAbs/$slug.jpg"); @unlink("$dirAbs/$slug-bw.jpg");
            array_splice($team, $k, 1); team_save($file, $team);
            $msg = 'Удалён: ' . $p['name']; break;
        }
    }
    $team = $team ?: team_load($file);
}
$v = time();
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Команда · Управление сайтом</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header class="top"><div class="wrap">
  <a class="logo" href="list.php?set=projects">AG Project Group<small>управление сайтом</small></a>
  <nav class="sections">
    <?php foreach (SETS as $k => $s): ?><a href="list.php?set=<?= $k ?>"><?= h($s['title']) ?></a><?php endforeach; ?>
    <a href="team.php" class="on">Команда</a><a href="texts.php">Тексты</a>
    <a href="help.php">Инструкция</a><a href="/" target="_blank">Сайт ↗</a><a href="index.php?logout=1">Выйти</a>
  </nav>
</div></header>

<div class="wrap">
  <h1>Команда</h1>
  <p class="sub">Человек в сетке: <?= count($team) ?>. Фотография сама обрежется в квадрат, чёрно-белая версия сделается автоматически.</p>
  <?php if ($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>

  <h2>Добавить человека</h2>
  <form method="post" enctype="multipart/form-data" class="card">
    <input type="hidden" name="action" value="add">
    <div class="grid2">
      <div class="row"><label>Имя и фамилия *</label><input type="text" name="name" required></div>
      <div class="row"><label>Должность</label><input type="text" name="role" placeholder="Архитектор"></div>
    </div>
    <div class="row"><label>Фотография *</label><input type="file" name="photo" accept="image/*" required></div>
    <button class="btn" type="submit">Добавить</button>
  </form>

  <h2>Список</h2>
  <p class="hint">Порядок меняется перетаскиванием карточек или стрелками ◀ ▶. После изменений нажмите «Сохранить всё».</p>
  <form method="post" id="teamForm">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="order" id="order">
    <div class="photos" id="teamGrid">
      <?php foreach ($team as $p): $s = $p['slug']; ?>
        <div class="ph card-p" draggable="true" data-s="<?= h($s) ?>">
          <img src="/assets/team/<?= h($s) ?>-bw.jpg?v=<?= $v ?>" alt="">
          <button class="mv" type="button" data-d="-1" title="Сдвинуть влево">◀</button>
          <button class="mv" type="button" data-d="1" title="Сдвинуть вправо">▶</button>
          <div class="pf">
            <input type="text" name="name[<?= h($s) ?>]" value="<?= h($p['name'] ?? '') ?>" placeholder="Имя">
            <input type="text" name="role[<?= h($s) ?>]" value="<?= h($p['role'] ?? '') ?>" placeholder="Должность">
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:20px"><button class="btn" type="submit">Сохранить всё</button></p>
  </form>

  <h2>Заменить фотографию или удалить</h2>
  <table>
    <?php foreach ($team as $p): ?>
    <tr>
      <td class="cover"><img src="/assets/team/<?= h($p['slug']) ?>.jpg?v=<?= $v ?>" alt=""></td>
      <td class="nm"><?= h($p['name'] ?? '') ?><div class="meta" style="font-weight:400"><?= h($p['role'] ?? '') ?></div></td>
      <td>
        <form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center">
          <input type="hidden" name="action" value="photo">
          <input type="hidden" name="slug" value="<?= h($p['slug']) ?>">
          <input type="file" name="photo" accept="image/*" required style="width:auto">
          <button class="btn sm ghost" type="submit">Заменить</button>
        </form>
      </td>
      <td class="act">
        <form method="post" onsubmit="return confirm('Удалить <?= h($p['name'] ?? '') ?> из команды? Фотографии тоже удалятся.')">
          <input type="hidden" name="action" value="del">
          <input type="hidden" name="slug" value="<?= h($p['slug']) ?>">
          <button class="btn sm danger" type="submit">Удалить</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<footer class="bot"><div class="wrap">Изменения появляются на сайте сразу после сохранения</div></footer>
<script>
const grid = document.getElementById('teamGrid'), orderInput = document.getElementById('order');
grid.addEventListener('click', e => {
  const b = e.target.closest('.mv'); if (!b) return;
  const card = b.closest('.ph'), d = +b.dataset.d;
  const sib = d < 0 ? card.previousElementSibling : card.nextElementSibling;
  if (!sib) return;
  d < 0 ? grid.insertBefore(card, sib) : grid.insertBefore(sib, card);
  sync();
});
let dragged = null;
grid.addEventListener('dragstart', e => {
  if (e.target.tagName === 'INPUT') { e.preventDefault(); return; }
  dragged = e.target.closest('.ph'); dragged?.classList.add('drag');
});
grid.addEventListener('dragend', () => { dragged?.classList.remove('drag'); dragged = null; sync(); });
grid.addEventListener('dragover', e => {
  e.preventDefault();
  const over = e.target.closest('.ph');
  if (!over || !dragged || over === dragged) return;
  const r = over.getBoundingClientRect();
  grid.insertBefore(dragged, (e.clientX - r.left) > r.width / 2 ? over.nextSibling : over);
});
function sync(){ orderInput.value = [...grid.querySelectorAll('.ph')].map(p => p.dataset.s).join(','); }
sync();
document.getElementById('teamForm').addEventListener('submit', sync);
</script>
</body></html>
