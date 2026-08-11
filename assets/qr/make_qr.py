#!/usr/bin/env python3
"""QR-код на страницу контактов AG Project Group.

Геометрия сайта: чистый квадрат, нулевое скругление, чёрное на #f9f9f7.
Модули рисуются как сплошные квадраты, поисковые узоры — цельными фигурами,
в центре вырубка под монограмму AG.
"""
import base64, pathlib, sys, segno
sys.path.insert(0, str(pathlib.Path(__file__).parent))
from text2path import text_path

BARLOW = '/tmp/Barlow-Black.ttf'          # латиница, начертание Black — как на сайте
GOLOS  = '/tmp/GolosText-SemiBold.ttf'    # кириллица: у Barlow её нет

URL   = 'https://ag-pg.ru/contact.html'
INK   = '#000000'
PAPER = '#f9f9f7'
QUIET = 4          # обязательная тихая зона, модулей
HOLE  = 9          # вырубка под логотип, модулей (предел при уровне H — 11)

ROOT = pathlib.Path('/Users/user/Клауд/ag-website-deploy')
OUT  = ROOT / 'assets/qr'
OUT.mkdir(parents=True, exist_ok=True)


def matrix():
    qr = segno.make(URL, error='h')
    return [list(row) for row in qr.matrix], qr.version


def is_finder(r, c, n):
    """Модуль принадлежит одному из трёх поисковых узоров 7×7."""
    return ((r < 7 and c < 7) or (r < 7 and c >= n - 7) or (r >= n - 7 and c < 7))


def logo_data_uri(name='logo-mono.png', white=False):
    """Знак как data-URI. white=True перекрашивает чёрное в белое, сохраняя прозрачность."""
    path = ROOT / 'assets/logo' / name
    if not white:
        return 'data:image/png;base64,' + base64.b64encode(path.read_bytes()).decode()
    from PIL import Image
    import io
    im = Image.open(path).convert('RGBA')
    r, g, b, a = im.split()
    inv = Image.merge('RGBA', (r.point(lambda v: 255 - v),
                               g.point(lambda v: 255 - v),
                               b.point(lambda v: 255 - v), a))
    buf = io.BytesIO(); inv.save(buf, 'PNG')
    return 'data:image/png;base64,' + base64.b64encode(buf.getvalue()).decode()


def build_svg(with_logo=True, background=None, caption=None,
              invert=False, logo='logo-mono.png', hole=None):
    """invert=True — светлые модули на тёмном фоне (как в присланном примере)."""
    from PIL import Image
    ink   = '#ffffff' if invert else INK
    paper = background if background else (INK if invert else None)
    m, version = matrix()
    n = len(m)
    size = n + QUIET * 2                      # в модулях
    o = QUIET                                 # отступ
    HOLE_ = hole or HOLE
    lw_, lh_ = Image.open(ROOT / 'assets/logo' / logo).size

    # Вырубка повторяет пропорции монограммы (1176×959), поэтому знак крупнее,
    # а перекрытых модулей меньше, чем у квадратной вырубки.
    ASPECT = lw_ / lh_
    hole_w = HOLE_
    hole_h = HOLE_ / ASPECT
    hx = (n - hole_w) / 2 + o
    hy = (n - hole_h) / 2 + o

    parts = []
    if paper:
        parts.append(f'<rect width="{size}" height="{size}" fill="{paper}"/>')

    # 1. Поисковые узоры — цельными фигурами, так они выглядят чище
    for (fr, fc) in [(0, 0), (0, n - 7), (n - 7, 0)]:
        x, y = fc + o, fr + o
        parts.append(
            f'<path d="M{x} {y}h7v7h-7z M{x+1} {y+1}v5h5v-5z" fill="{ink}" fill-rule="evenodd"/>'
            f'<rect x="{x+2}" y="{y+2}" width="3" height="3" fill="{ink}"/>')

    # 2. Остальные модули
    mods = []
    for r in range(n):
        for c in range(n):
            if not m[r][c] or is_finder(r, c, n):
                continue
            x, y = c + o, r + o
            if with_logo and hx - 0.01 <= x < hx + hole_w and hy - 0.01 <= y < hy + hole_h:
                continue                       # место под монограмму
            mods.append(f'M{x} {y}h1v1h-1z')
    parts.append(f'<path d="{"".join(mods)}" fill="{ink}"/>')

    # 3. Монограмма в центре
    if with_logo:
        pad = 0.85                              # воздух вокруг знака, модулей
        lw = hole_w - pad * 2
        lh = lw / ASPECT
        parts.append(f'<rect x="{hx:.4f}" y="{hy:.4f}" width="{hole_w:.4f}" height="{hole_h:.4f}" '
                     f'fill="{paper or PAPER}"/>')
        parts.append(
            f'<image href="{logo_data_uri(logo, white=invert)}" x="{hx + pad:.4f}" y="{hy + (hole_h - lh) / 2:.4f}" '
            f'width="{lw:.4f}" height="{lh:.4f}" preserveAspectRatio="xMidYMid meet"/>')

    height = size
    extra = ''
    if caption:
        cap_h = 7.6
        height = size + cap_h
        title, sub = caption

        # Текст переведён в кривые настоящими шрифтами сайта: файл выглядит
        # одинаково везде, даже если у типографии этих шрифтов нет.
        t_path, t_w = text_path(BARLOW, title, 2.6, 0.02)
        s_path, s_w = text_path(GOLOS,  sub,   1.3, 0.22)
        extra = (
            f'<g transform="translate({(size - t_w) / 2:.4f} {size + 3.1:.4f})" fill="{ink}">{t_path}</g>'
            f'<g transform="translate({(size - s_w) / 2:.4f} {size + 5.9:.4f})" fill="{"#c9c9c4" if invert else "#5e5e5e"}">{s_path}</g>')
        if paper:
            parts.insert(0, f'<rect width="{size}" height="{height}" fill="{paper}"/>')
            parts.pop(1)

    return (f'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
            f'viewBox="0 0 {size} {height}" width="{size*24}" height="{height*24}" '
            f'shape-rendering="crispEdges" role="img" aria-label="QR-код: контакты AG Project Group">'
            f'<title>Контакты AG Project Group — {URL}</title>'
            + ''.join(parts) + extra + '</svg>'), version, n


if __name__ == '__main__':
    variants = {
        'qr-contact.svg':            dict(with_logo=True,  background=None),
        'qr-contact-paper.svg':      dict(with_logo=True,  background=PAPER),
        'qr-contact-plain.svg':      dict(with_logo=False, background=None),
        'qr-contact-card.svg':       dict(with_logo=True,  background=PAPER,
                                          caption=('AG PROJECT GROUP', 'КОНТАКТЫ · AG-PG.RU')),
        # Обратная полярность — как в присланном примере
        'qr-contact-invert.svg':     dict(with_logo=True, invert=True, logo='logo-full.png', hole=10),
        'qr-contact-white.svg':      dict(with_logo=True, invert=True, logo='logo-full.png', hole=10,
                                          background='none'),
        # Правильная полярность, но с полным логотипом в центре
        'qr-contact-full.svg':       dict(with_logo=True, background=PAPER, logo='logo-full.png', hole=10),
    }
    for name, kw in variants.items():
        svg, version, n = build_svg(**kw)
        (OUT / name).write_text(svg, encoding='utf-8')
        print(f'{name:26} {len(svg):7} байт')
    print(f'\nверсия QR {version}, {n}×{n} модулей, уровень коррекции H, ссылка: {URL}')
