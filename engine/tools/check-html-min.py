#!/usr/bin/env python3
"""
Проверка фильтра минификации HTML (engine/blocks/main/html-min.php).

Рендерит каждую страницу дважды — с фильтром и без — и сверяет, что
отличия только в пробелах и выброшенных комментариях. Ради 130 байт
на страницу нельзя полагаться на «вроде открылось»: правило про
пробелы ломает текст молча, в одном абзаце из сорока.

Три проверки на страницу:

  1. Текст совпадает. Оба рендера приводятся к одной строке (все
     пробелы схлопнуты в один, комментарии сняты) и сравниваются
     байт в байт. Любое отличие, кроме пробелов, всплывает здесь.
  2. Литералы целы. Содержимое script/style/pre/textarea сверяется
     байт в байт: там JSON-LD, Метрика и преформат.
  3. Комментарий с ! сохранён, остальные убраны.

Запуск:  python3 engine/tools/check-html-min.py
"""

import gzip
import os
import re
import subprocess
from html.parser import HTMLParser
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
PHP = os.environ.get('PHP_BIN', str(ROOT.parent / '.tools/php/php'))

COMMENT = re.compile(r'<!--.*?-->', re.S)
KEEP = re.compile(r'<!--!.*?-->', re.S)
RAW = re.compile(
    r'<(script|style|pre|textarea)\b[^>]*>(.*?)</\1\s*>', re.S | re.I)


# Опасные случаи по одному. Страницы сайта их сейчас не содержат —
# именно поэтому они здесь: правило ломает такой случай молча, а
# появиться он может в любом новом блоке.
CASES = [
    ('пробел между строчными элементами',
     '<p>\n  <a>продвижение</a> <a>блог</a>\n</p>',
     '<p><a>продвижение</a> <a>блог</a></p>'),
    ('перевод строки между строчными = пробел',
     '<a class="rs-btn">раз</a>\n<a class="rs-btn">два</a>',
     '<a class="rs-btn">раз</a> <a class="rs-btn">два</a>'),
    ('иконка рядом со словом',
     '<a>Смотреть\n  <svg><path d="M0"/></svg></a>',
     '<a>Смотреть <svg><path d="M0"/></svg></a>'),
    ('между блочными пробел исчезает',
     '<li>\n  раз\n</li>\n<li>\n  два\n</li>',
     '<li>раз</li><li>два</li>'),
    ('кириллица с байтом \\x85 внутри буквы',
     '<p>\n  возможно, она переехала\n</p>',
     '<p>возможно, она переехала</p>'),
    ('перенос внутри значения атрибута',
     '<meta content="строка\n  вторая">',
     '<meta content="строка\n  вторая">'),
    ('преформат',
     '<pre>  раз\n   два</pre>',
     '<pre>  раз\n   два</pre>'),
    ('литерал в скрипте',
     '<script>var s = "  две  ";\n  var b = 1;</script>',
     '<script>var s = "  две  ";\n  var b = 1;</script>'),
    ('угловая скобка внутри атрибута',
     '<a href="a>b" title="тест">x</a>',
     '<a href="a>b" title="тест">x</a>'),
    ('обычный комментарий убран, помеченный оставлен',
     '<i><!-- служебный --><!--! подпись --></i>',
     '<i><!--! подпись --></i>'),
    ('пустые строки и отступы',
     '<div>\n\n\n    <b>раз</b>\n    \n</div>',
     '<div><b>раз</b></div>'),
]


def selftest() -> int:
    """Прогон фильтра на случаях, которых на страницах пока нет."""
    import base64
    import json

    # Случаи уезжают в PHP аргументом в base64: временный файл
    # php-wasm не видит, он читает только из папки проекта.
    payload = base64.b64encode(
        json.dumps([c[1] for c in CASES]).encode()).decode()
    code = ("require 'engine/blocks/main/html-min.php';"
            " echo json_encode(array_map('rs_html_min',"
            " json_decode(base64_decode($argv[1]), true)),"
            " JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);")
    r = subprocess.run([PHP, '-r', code, payload], cwd=ROOT,
                       capture_output=True)
    if r.returncode != 0 or not r.stdout:
        raise RuntimeError(r.stderr.decode()[-400:] or 'пустой ответ php')
    got = json.loads(r.stdout.decode('utf-8'))

    bad = 0
    for (name, _, want), have in zip(CASES, got):
        if have == want:
            print(f'  ок      {name}')
        else:
            bad += 1
            print(f'  ОШИБКА  {name}\n          ждали {want!r}\n'
                  f'          вышло {have!r}')
    return bad

def render(page: str, minify: bool) -> str:
    env = dict(os.environ, RS_HTML_MIN='1' if minify else '0')
    # display_errors=0 намеренно. В php-wasm не открывается путь
    # сессий, и предупреждение печатается прямо в страницу: в нём имя
    # временного файла, своё на каждый запуск, — сравнивать по нему
    # нечего. Снимать его потом регуляркой нельзя: в сжатом режиме оно
    # склеивается с разметкой в одну строку, и регулярка съедала весь
    # документ. Fatal error так не прячется — он меняет код возврата.
    r = subprocess.run([PHP, '-d', 'display_errors=0',
                        'engine/tools/render.php', page],
                       cwd=ROOT, env=env, capture_output=True)
    if r.returncode != 0:
        raise RuntimeError(f'{page}: {r.stderr.decode()[-300:]}')
    return r.stdout.decode('utf-8')


def split_tail(html: str) -> tuple:
    """Документ и хвост за </html> — подпись студии печатается вне
    буфера и фильтром не проходит вообще."""
    i = html.rfind('</html>')
    return (html, '') if i < 0 else (html[:i + 7], html[i + 7:])


# Список блочных элементов читается из самого фильтра: держать его
# здесь копией значит однажды проверять не то, что работает.
BLOCK = set(re.findall(
    r"'([a-z0-9]+)' => 1",
    (ROOT / 'engine/blocks/main/html-min.php').read_text(encoding='utf-8')))


# Граница блочного элемента помечается знаком, которого в тексте не
# бывает. Переводом строки её метить нельзя: перевод строки внутри
# текста — обычный пробел, и сравнение путало одно с другим.
SEP = '\x00'


class Reader(HTMLParser):
    """Текст страницы так, как его сложит браузер: пробелы схлопнуты,
    граница блочного элемента — перенос, содержимое строчных склеено
    как есть. Именно это сравнение ловит «продвижениеблог»: пробел
    между строчными тут значащий, а между блочными его нет вообще."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.out = []
        self.skip = 0

    def handle_starttag(self, tag, attrs):
        if tag in ('script', 'style'):
            self.skip += 1
        if tag in BLOCK:
            self.out.append(SEP)

    def handle_startendtag(self, tag, attrs):
        if tag in BLOCK:
            self.out.append(SEP)

    def handle_endtag(self, tag):
        if tag in ('script', 'style') and self.skip:
            self.skip -= 1
        if tag in BLOCK:
            self.out.append(SEP)

    def handle_data(self, data):
        if not self.skip:
            self.out.append(data)


def text_only(html: str) -> str:
    r = Reader()
    r.feed(COMMENT.sub('', html))
    lines = (re.sub(r'\s+', ' ', ln).strip() for ln in ''.join(r.out).split(SEP))
    return '\n'.join(ln for ln in lines if ln)


def literals(html: str) -> list:
    return [m.group(2) for m in RAW.finditer(html)]


def main() -> int:
    pages = sorted(
        str(p.relative_to(ROOT / 'public'))
        for p in (ROOT / 'public').rglob('*.php')
    )
    # Длина в БАЙТАХ: len() у строки считает символы, и кириллица
    # занижала цифру ровно на число русских букв.
    size = lambda s: len(s.encode())
    gz = lambda s: len(gzip.compress(s.encode(), 9))
    print('Фильтр на опасных случаях:')
    bad = selftest()

    print(f'\n{"страница":42} {"raw":>7} {"min":>7} {"gzip":>6} {"min+gz":>7}  итог')
    for page in pages:
        raw, mini = render(page, False), render(page, True)
        raw, raw_tail = split_tail(raw)
        mini, mini_tail = split_tail(mini)
        errs = []

        if raw_tail.strip() != mini_tail.strip():
            errs.append('подпись за </html> изменилась')

        if text_only(raw) != text_only(mini):
            # Показать первое расхождение, иначе искать его негде.
            a, b = text_only(raw), text_only(mini)
            i = next((i for i in range(min(len(a), len(b))) if a[i] != b[i]),
                     min(len(a), len(b)))
            errs.append(f'текст расходится на позиции {i}: '
                        f'{a[max(0,i-40):i+40]!r} / {b[max(0,i-40):i+40]!r}')

        if literals(raw) != literals(mini):
            errs.append('литерал в script/style/pre изменился')

        kept_before, kept_after = KEEP.findall(raw), KEEP.findall(mini)
        if kept_before != kept_after:
            errs.append('комментарий <!--! --> не доехал')
        if len(COMMENT.findall(mini)) != len(kept_after):
            errs.append('обычный комментарий остался в выдаче')

        mark = 'ок' if not errs else 'ОШИБКА'
        full_raw, full_mini = raw + raw_tail, mini + mini_tail
        print(f'{page:42} {size(full_raw):7} {size(full_mini):7} '
              f'{gz(full_raw):6} {gz(full_mini):7}  {mark}')
        for e in errs:
            print(f'    {e}')
            bad += 1

    print('\nВсе страницы совпали по тексту' if not bad
          else f'\nРасхождений: {bad}')
    return 1 if bad else 0


if __name__ == '__main__':
    sys.exit(main())
