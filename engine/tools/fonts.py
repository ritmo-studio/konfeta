#!/usr/bin/env python3
"""
TTF → WOFF2: исходники в fonts-src/, результат в public/assets/fonts.

WOFF2 — это тот же шрифт, сжатый Brotli с учётом структуры таблиц:
картинка не меняется, вес падает примерно втрое. Поддержан всеми
браузерами с 2016 года, поэтому в @font-face достаточно одного src.

TTF остаются в репозитории как исходники: их отдаёт студия шрифтов,
и при замене trial-версий на купленные конвертация повторяется. Лежат
они вне public/ намеренно: отдавать наружу исходный ttf незачем, а для
trial-версий это ещё и вопрос лицензии — файл качается по угадываемому
адресу.
Скрипт идемпотентен — существующий woff2 перезаписывается.

Запуск: python3 engine/tools/fonts.py
Нужен fonttools с brotli: pip3 install fonttools brotli
"""

import pathlib
import sys

try:
    from fontTools.ttLib import TTFont
except ImportError:
    sys.exit("Нет fonttools. Поставить: pip3 install fonttools brotli")

ROOT = pathlib.Path(__file__).resolve().parents[2]
SRC = ROOT / "fonts-src"
OUT = ROOT / "public" / "assets" / "fonts"


def main() -> int:
    sources = sorted(SRC.glob("*.ttf"))
    if not sources:
        print("В fonts-src нет ttf — нечего конвертировать.")
        return 0

    OUT.mkdir(parents=True, exist_ok=True)
    for src in sources:
        dst = OUT / (src.stem + ".woff2")
        font = TTFont(src)
        font.flavor = "woff2"
        font.save(dst)
        was, now = src.stat().st_size, dst.stat().st_size
        print(f"{dst.name}: {was // 1024} КБ → {now // 1024} КБ "
              f"({100 - now * 100 // was}% меньше)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
