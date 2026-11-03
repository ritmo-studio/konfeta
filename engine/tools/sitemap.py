#!/usr/bin/env python3
"""
Карта сайта из страниц в public/.

Источника правды о списке страниц на инклюдах нет — страница это просто
PHP-файл в public/ (докрут). Поэтому адрес берётся из самой страницы: $pageUrl, который
она обязана задать по контракту (engine/blocks/main/head.php). Руками список вести
нельзя — забудется ровно на той странице, которая важна.

lastmod — mtime файла страницы. Это честнее произвольной даты: если файл не
менялся, незачем говорить краулеру, что менялся.

Страница с $noindex = true в карту не попадает: приглашать краулера туда,
откуда его гонит мета-тег, — противоречивый сигнал.
"""

import pathlib
import re
import datetime

ROOT = pathlib.Path(__file__).resolve().parents[2]
PUBLIC = ROOT / "public"
URL = re.compile(r'\$pageUrl\s*=\s*[\'"]([^\'"]+)[\'"]')
NOINDEX = re.compile(r'\$noindex\s*=\s*true')


def main() -> None:
    pages = []
    # Страница это либо PHP-файл в докруте, либо папка с index.php —
    # второе нужно, чтобы адрес был /about/, а не /about.php.
    #
    # Папки ищутся на любой глубине: страницы живут и вложенно
    # (/authors/dmitry-glukhov/). При поиске только по одному уровню
    # такая страница молча не попадала в карту — а узнать об этом
    # можно было лишь по счётчику в конце сборки.
    files = sorted(set(PUBLIC.glob("*.php")) | set(PUBLIC.rglob("index.php")))
    for f in files:
        src = f.read_text(encoding="utf-8")
        m = URL.search(src)
        if not m or NOINDEX.search(src):
            continue
        mtime = datetime.date.fromtimestamp(f.stat().st_mtime)
        pages.append((m.group(1), mtime.isoformat()))

    out = ['<?xml version="1.0" encoding="UTF-8"?>',
           '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    for url, lastmod in pages:
        out.append(f"  <url><loc>{url}</loc><lastmod>{lastmod}</lastmod></url>")
    out.append("</urlset>")

    (PUBLIC / "sitemap.xml").write_text("\n".join(out) + "\n", encoding="utf-8")
    print(f"sitemap.xml — страниц: {len(pages)}")


if __name__ == "__main__":
    main()
