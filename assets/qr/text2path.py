"""Перевод строки в SVG-путь настоящим шрифтом — чтобы файл не зависел от того,
установлен ли Barlow у типографии."""
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen


def text_path(ttf, text, font_size, letter_spacing=0.0):
    font = TTFont(ttf)
    upem = font['head'].unitsPerEm
    cmap = font.getBestCmap()
    gs = font.getGlyphSet()
    hmtx = font['hmtx']
    scale = font_size / upem

    d, x = [], 0.0
    for ch in text:
        gname = cmap.get(ord(ch))
        if gname is None:
            x += font_size * 0.4 + letter_spacing
            continue
        pen = SVGPathPen(gs)
        gs[gname].draw(pen)
        seg = pen.getCommands()
        if seg:
            # переносим в позицию и отражаем по Y: в шрифте ось вверх, в SVG вниз
            d.append(f'<g transform="translate({x:.4f} 0) scale({scale:.6f} {-scale:.6f})">'
                     f'<path d="{seg}"/></g>')
        x += hmtx[gname][0] * scale + letter_spacing
    return ''.join(d), x
