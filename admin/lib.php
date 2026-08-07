<?php
require_once __DIR__ . '/config.php';

session_start();

/* ─────────── Авторизация ─────────── */
function is_logged(): bool { return !empty($_SESSION['ag_admin']); }

function require_login(): void {
    if (!is_logged()) { header('Location: index.php'); exit; }
}

function check_password(string $pw): bool {
    return password_verify($pw, ADMIN_PASSWORD_HASH);
}

/* ─────────── Чтение и запись данных ─────────── */
function set_conf(string $set): array {
    if (!isset(SETS[$set])) { http_response_code(400); exit('Неизвестный раздел'); }
    return SETS[$set];
}

function load_set(string $set): array {
    $c = set_conf($set);
    $path = ROOT . '/' . $c['file'];
    if (!is_file($path)) return [];
    $raw = file_get_contents($path);
    $a = strpos($raw, '['); $b = strrpos($raw, ']');
    if ($a === false || $b === false) return [];
    $data = json_decode(substr($raw, $a, $b - $a + 1), true);
    return is_array($data) ? $data : [];
}

function save_set(string $set, array $data): bool {
    $c = set_conf($set);
    $path = ROOT . '/' . $c['file'];
    // резервная копия последней версии
    if (is_file($path)) @copy($path, ROOT . '/admin/backup/' . $c['file'] . '.bak');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $out = 'window.' . $c['var'] . ' = ' . $json . ";\n";
    return file_put_contents($path, $out) !== false;
}

/* ─────────── Транслитерация для имени папки ─────────── */
function slugify(string $s): string {
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
        'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s',
        'т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y',
        'ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? substr($s, 0, 40) : 'obj-' . substr(md5(microtime()), 0, 6);
}

function unique_slug(string $base, array $data, string $exclude = ''): string {
    $slugs = array_column($data, 'slug');
    $slug = $base; $i = 2;
    while (in_array($slug, $slugs, true) && $slug !== $exclude) { $slug = $base . '-' . $i; $i++; }
    return $slug;
}

/* ─────────── Обработка изображений ─────────── */
/** До PHP 8.0 память надо было освобождать вручную, с 8.5 функция объявлена устаревшей. */
function free_image($im): void { if (PHP_VERSION_ID < 80000) imagedestroy($im); }

function read_image(string $path) {
    $info = @getimagesize($path);
    if (!$info) return null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($path); break;
        case IMAGETYPE_PNG:  $im = @imagecreatefrompng($path); break;
        case IMAGETYPE_WEBP: $im = @imagecreatefromwebp($path); break;
        default: return null;
    }
    if (!$im) return null;
    // поворот по EXIF
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $ex = @exif_read_data($path);
        if (!empty($ex['Orientation'])) {
            $deg = [3 => 180, 6 => -90, 8 => 90][$ex['Orientation']] ?? 0;
            if ($deg) { $r = imagerotate($im, $deg, 0); if ($r) { free_image($im); $im = $r; } }
        }
    }
    return $im;
}

function save_resized($im, string $dest, int $maxSide, int $quality): bool {
    $w = imagesx($im); $h = imagesy($im);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagejpeg($out, $dest, $quality);
    free_image($out);
    return $ok;
}

function save_square($im, string $dest, int $size, int $quality): bool {
    $w = imagesx($im); $h = imagesy($im);
    $s = min($w, $h);
    $x = (int)(($w - $s) / 2); $y = (int)(($h - $s) / 2);
    $side = min($size, $s);
    $out = imagecreatetruecolor($side, $side);
    imagecopyresampled($out, $im, 0, 0, $x, $y, $side, $side, $s, $s);
    $ok = imagejpeg($out, $dest, $quality);
    free_image($out);
    return $ok;
}

/** Кладёт загруженный файл в папку объекта: NN.jpg + thumb-NN.jpg. Возвращает имя файла. */
function add_photo(string $tmpPath, string $dirAbs): ?string {
    if (!is_dir($dirAbs)) @mkdir($dirAbs, 0755, true);
    $im = read_image($tmpPath);
    if (!$im) return null;
    // следующий свободный номер
    $n = 1;
    while (file_exists("$dirAbs/" . sprintf('%02d', $n) . '.jpg')) $n++;
    $name = sprintf('%02d', $n) . '.jpg';
    $ok1 = save_resized($im, "$dirAbs/$name", MAX_SIDE, Q_FULL);
    $ok2 = save_square($im, "$dirAbs/thumb-$name", THUMB_SIZE, Q_THUMB);
    free_image($im);
    return ($ok1 && $ok2) ? $name : null;
}

function delete_photo(string $dirAbs, string $name): void {
    @unlink("$dirAbs/$name");
    @unlink("$dirAbs/thumb-$name");
}

/* ─────────── Вспомогательное ─────────── */
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function find_index(array $data, string $slug): int {
    foreach ($data as $i => $o) if (($o['slug'] ?? '') === $slug) return $i;
    return -1;
}
