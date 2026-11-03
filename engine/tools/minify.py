#!/usr/bin/env python3
"""
Минификация собранных CSS и JS.

Трогает только то, что уходит в assets/: исходники остаются как есть,
с комментариями. Это и есть смысл шага — комментарии в проекте нужны
в исходниках, а в бандле не нужны никому. Наши 12 КиБ, на которые
ругается PageSpeed, это почти целиком они.

Поэтому здесь нет ничего из того, чем занимаются настоящие
минификаторы: ни переименования переменных, ни склейки селекторов,
ни вычисления выражений. Только комментарии и лишние пробелы —
преобразования, после которых смысл кода заведомо тот же.

Главное правило: пробелы и переводы строк режутся ТОЛЬКО в коде.
Литерал — строка, шаблон, регулярка, сохранённый комментарий /*! —
уезжает в сборку байт в байт. Поэтому сканер не просто различает
комментарии, а делит файл на куски «код / литерал», и правила про
пробелы применяются к первым и не применяются ко вторым.

Так закрыты две поломки, которые этот файл делал молча:

    content: "привет, мир"   ->   "привет,мир"
        правило про запятую не знало про строки и съедало пробел
        в видимом на экране тексте;

    /https?:\\/\\//   ->   обрубок и конец строки
        сканер не знал про регулярки, видел в экранированном слэше
        начало строчного комментария и срезал остаток строки. Один
        такой литерал — и весь собранный main.js перестаёт
        разбираться, а исходник при этом цел.

В JS переводы строк внутри кода сохраняются — без них точка с запятой
может подставиться не там, где ожидает автор (ASI).

Комментарий, начинающийся с /*! , остаётся в сборке. Так помечается
то, что должно уехать на сервер вместе с кодом: сейчас это
предупреждение о TRIAL-версиях шрифтов. Теперь он доезжает и с
переводами строк: это текст для человека, а не код.
"""
import re

# После чего слэш — это начало регулярки, а не деление. Отличить их
# можно только по тому, что стоит слева: после значения (имени, числа,
# закрывающей скобки) идёт деление, после оператора или ключевого
# слова — регулярка.
_RE_AFTER_CHARS = set('(,=:[!&|?{};+-*%~^<>')
_RE_AFTER_WORDS = frozenset((
    'return', 'typeof', 'case', 'in', 'of', 'new', 'delete', 'void',
    'throw', 'instanceof', 'do', 'else', 'yield', 'await',
))
_TAIL_WORD = re.compile(r'([A-Za-z_$][\w$]*)$')


def _end_of_string(src: str, i: int, quote: str) -> int:
    """Индекс за закрывающей кавычкой. i указывает на открывающую."""
    n = len(src)
    j = i + 1
    while j < n:
        c = src[j]
        if c == '\\':
            j += 2
            continue
        if c == quote:
            return j + 1
        j += 1
    return n            # незакрытая кавычка: отдаём остаток как есть


def _end_of_regex(src: str, i: int) -> int:
    """
    Индекс за закрывающим слэшом и флагами, или -1, если это не
    регулярка. Внутри [...] слэш — обычный символ и литерал не
    закрывает; перевод строки до закрытия означает, что мы приняли
    за регулярку деление.
    """
    n = len(src)
    j = i + 1
    in_class = False
    while j < n:
        c = src[j]
        if c == '\\':
            j += 2
            continue
        if c == '\n':
            return -1
        if in_class:
            if c == ']':
                in_class = False
        elif c == '[':
            in_class = True
        elif c == '/':
            j += 1
            while j < n and src[j].isalpha():   # флаги: i, g, u, ...
                j += 1
            return j
        j += 1
    return -1


def _scan(src: str, js: bool):
    """
    Делит файл на куски (литерал?, текст). Комментарии выбрасываются
    здесь же — кроме /*! , он уходит в литералы и потому доезжает
    до сборки нетронутым.
    """
    chunks = []
    code = []
    i, n = 0, len(src)

    def flush():
        if code:
            chunks.append((False, ''.join(code)))
            code.clear()

    def regex_can_start():
        tail = ''.join(code).rstrip()
        if not tail:
            return True
        if tail[-1] in _RE_AFTER_CHARS:
            return True
        m = _TAIL_WORD.search(tail)
        return bool(m and m.group(1) in _RE_AFTER_WORDS)

    while i < n:
        c = src[i]

        if c in '"\'' or (js and c == '`'):
            end = _end_of_string(src, i, c)
            flush()
            chunks.append((True, src[i:end]))
            i = end
            continue

        if c == '/' and i + 1 < n and src[i + 1] == '*':
            end = src.find('*/', i + 2)
            end = n if end == -1 else end + 2
            if src.startswith('/*!', i):
                flush()
                chunks.append((True, src[i:end]))
            i = end
            continue

        if js and c == '/' and i + 1 < n and src[i + 1] == '/':
            end = src.find('\n', i)
            i = n if end == -1 else end
            continue

        if js and c == '/' and regex_can_start():
            end = _end_of_regex(src, i)
            if end > 0:
                flush()
                chunks.append((True, src[i:end]))
                i = end
                continue

        code.append(c)
        i += 1

    flush()
    return chunks


def _css_code(s: str) -> str:
    s = re.sub(r'\s+', ' ', s)
    # Пробелы рядом с этими знаками не значат ничего. Плюс, минус,
    # звёздочка и слэш не тронуты намеренно: внутри calc() они
    # считаются оператором только с пробелами по бокам.
    s = re.sub(r'\s*([{};,])\s*', r'\1', s)
    s = re.sub(r':\s+', ':', s)
    return s.replace(';}', '}')


def _js_code(s: str) -> str:
    lines = (ln.strip() for ln in s.split('\n'))
    return '\n'.join(ln for ln in lines if ln)


def _run(src: str, js: bool) -> str:
    fix = _js_code if js else _css_code
    out = [text if lit else fix(text) for lit, text in _scan(src, js)]
    return ''.join(out).strip() + '\n'


def css(src: str) -> str:
    return _run(src, js=False)


def js(src: str) -> str:
    return _run(src, js=True)
