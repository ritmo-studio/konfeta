#!/usr/bin/env python3
"""
Сборщик сайта «Ритм».

HTML не собирается — он живёт в PHP-инклюдах и отдаётся сервером.
Сборка нужна только для CSS и JS: порядок подключения важен,
а держать в <head> десяток файлов не хочется.

    tokens.css + engine/base.css + atoms/*  →  public/assets/css/main.css
    + engine/blocks/* + blocks/*                (то же для js)

Исходники лежат вне public/: наружу уходит только собранное.

Порядок склейки не алфавитный, а смысловой: токены, база сборки,
атомы, блоки сборки, блоки сайта. Сайтовый блок идёт последним,
чтобы мог переопределить сборочный, а не наоборот.

Запуск: python3 engine/tools/build.py
"""
import re
import sys

import minify
import sitemap
from pathlib import Path

# Файл лежит в engine/tools/, корень сайта на два уровня выше.
ROOT = Path(__file__).resolve().parents[2]
ENGINE = ROOT / "engine"
PUBLIC = ROOT / "public"   # докрут: всё остальное веб-сервер не видит
PLACEHOLDER = re.compile(r"(TODO|FIXME|__[A-Z_]+__|ЗАПОЛНИТЬ)")


def collect(suffix):
    """Токены, база сборки, атомы, блоки сборки, блоки сайта."""
    files = [ROOT / f"tokens{suffix}", ENGINE / f"base{suffix}"]
    files = [f for f in files if f.exists()]
    for base in (ROOT / "atoms", ENGINE / "blocks", ROOT / "blocks"):
        if base.is_dir():
            files += sorted(base.glob(f"*/*{suffix}"))
    return files


def build(suffix, out_path):
    files = collect(suffix)
    parts, problems = [], []
    for f in files:
        text = f.read_text(encoding="utf-8")
        rel = f.relative_to(ROOT)
        for n, line in enumerate(text.splitlines(), 1):
            if PLACEHOLDER.search(line):
                problems.append(f"{rel}:{n}: {line.strip()[:70]}")
        parts.append(f"/* ─── {rel} ─── */\n{text.rstrip()}\n")

    out_path.parent.mkdir(parents=True, exist_ok=True)
    raw = "\n".join(parts)
    # В сборку комментарии не идут: они нужны в исходниках, а браузеру
    # нет. На них и ругается PageSpeed («уменьшите размер кода»).
    small = minify.css(raw) if suffix == ".css" else minify.js(raw)
    out_path.write_text(small, encoding="utf-8")
    return files, problems, len(raw.encode()), len(small.encode())


def check_php():
    """
    Любой байт перед <?php уходит в вывод до заголовков, и тогда header()
    падает с "headers already sent" — то есть защита от индексации на
    стейджинге молча перестаёт работать. Ловим это на сборке.
    """
    problems = []
    for f in sorted(ROOT.rglob("*.php")):
        if "node_modules" in f.parts:
            continue
        raw = f.read_bytes()
        rel = f.relative_to(ROOT)
        if raw.startswith(b"\xef\xbb\xbf"):
            problems.append(f"{rel}: BOM перед <?php — уйдёт в вывод до заголовков")
            raw = raw[3:]
        if not raw.startswith(b"<?php") and not raw.startswith(b"<?="):
            head = raw[:20].decode("utf-8", "replace")
            problems.append(f"{rel}: перед <?php есть вывод — {head!r}")
    return problems


def main():
    all_problems = []
    for suffix, out in ((".css", PUBLIC / "assets/css/main.css"),
                        (".js", PUBLIC / "assets/js/main.js")):
        files, problems, raw, small = build(suffix, out)
        all_problems += problems
        names = ", ".join(f.relative_to(ROOT).as_posix() for f in files)
        cut = 100 - small * 100 // raw
        print(f"{out.relative_to(ROOT)}: {len(files)} файлов, "
              f"{small/1024:.1f} КБ (из {raw/1024:.1f}, −{cut}%)")
        print(f"   {names}")

    php_problems = check_php()

    sitemap.main()

    if all_problems:
        print("\nНезаполненные плейсхолдеры:")
        for p in all_problems:
            print("  " + p)
    if php_problems:
        print("\nВывод до заголовков в PHP:")
        for p in php_problems:
            print("  " + p)

    if all_problems or php_problems:
        return 1
    print("\nПлейсхолдеров нет, вывод до заголовков чист.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
