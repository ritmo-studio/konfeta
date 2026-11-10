#!/usr/bin/env python3
"""
Синхронизация папки engine/ с общим репозиторием сборки.

Зачем скрипт, а не git-сабмодуль: деплой сайта — это git pull в папку
сайта, выполняемый панелью. Сабмодуль при таком pull остаётся на старом
коммите, и на боевом молча оказывается прошлая версия сборки. Поэтому
engine/ лежит в репозитории сайта обычными файлами (деплой не меняется),
а обновляется явно — этой командой.

    python3 engine/tools/sync-engine.py            показать расхождение
    python3 engine/tools/sync-engine.py --pull     взять из движка в сайт
    python3 engine/tools/sync-engine.py --push     отдать правки в движок

Намеренные отличия сайта (свой 404, свой base.css) перечисляются в
engine/local.list — по пути в строке, папка со слэшом на конце. Они не
сравниваются и не переносятся. Сайтовые значения (домен, счётчик) в
engine/ не лежат вообще — они в content/site.php, см. site.example.php.

Репозиторий движка по умолчанию ищется рядом: ../ritmo-engine.
Другой путь — ключом --repo.

--pull и --push ничего не перезаписывают без --yes: сначала показывают,
что именно изменится. Правка, сделанная в сайте, может быть сайтовой —
тогда её в движок переносить нельзя, и решает это человек, а не скрипт.
"""

import argparse
import filecmp
import pathlib
import shutil
import subprocess
import sys

SKIP_DIRS = {"__pycache__", ".git"}
# .gitignore принадлежит репозиторию движка, а не папке engine/ внутри
# сайта: там свой, на весь сайт. Сравнивать их незачем.
SKIP_FILES = {".DS_Store", ".gitignore"}
# Список намеренных отличий сайта: пути относительно engine/, по одному в
# строке, # — комментарий, слэш на конце — вся папка. Эти файлы не
# сравниваются, не тянутся и не отдаются: иначе сигнал «что-то разошлось»
# тонет в постоянном шуме, и его перестают читать. Сам список — сайтовый.
LOCAL_LIST = "local.list"

SITE = pathlib.Path(__file__).resolve().parents[1]      # <сайт>/engine
ROOT = SITE.parent


def local_rules() -> list:
    f = SITE / LOCAL_LIST
    if not f.is_file():
        return []
    rules = []
    for line in f.read_text(encoding="utf-8").splitlines():
        line = line.split("#", 1)[0].strip()
        if line:
            rules.append(line)
    return rules


def is_local(rel: pathlib.PurePath, rules: list) -> bool:
    s = rel.as_posix()
    return any(s == r or (r.endswith("/") and s.startswith(r)) for r in rules)


def files(base: pathlib.Path) -> set:
    """Относительные пути всех файлов, кроме служебного мусора."""
    out = set()
    for p in base.rglob("*"):
        if not p.is_file():
            continue
        if SKIP_DIRS & set(p.relative_to(base).parts) or p.name in SKIP_FILES:
            continue
        if p.name == LOCAL_LIST and p.parent == base:
            continue
        out.add(p.relative_to(base))
    return out


def compare(repo: pathlib.Path):
    rules = local_rules()
    here, there = files(SITE), files(repo)
    here = {f for f in here if not is_local(f, rules)}
    there = {f for f in there if not is_local(f, rules)}
    same = sorted(here & there)
    diff = [f for f in same if not filecmp.cmp(SITE / f, repo / f, shallow=False)]
    return sorted(here - there), sorted(there - here), diff


def show(repo: pathlib.Path) -> int:
    only_site, only_repo, diff = compare(repo)
    rules = local_rules()
    if rules:
        print(f"Намеренно своё (engine/{LOCAL_LIST}): " + ", ".join(rules))
    if not (only_site or only_repo or diff):
        print(f"Сходится с {repo}: расхождений нет.")
        return 0
    if diff:
        print("Различается содержимое:")
        for f in diff:
            print(f"  {f}")
    if only_site:
        print("Есть только в сайте (новое здесь или забытое в движке):")
        for f in only_site:
            print(f"  {f}")
    if only_repo:
        print("Есть только в движке (не дошло до сайта):")
        for f in only_repo:
            print(f"  {f}")
    print("\nЧто дальше: --pull взять версию движка, --push отдать свою.")
    print("Посмотреть построчно:  diff -ru", repo, SITE)
    return 0


def copy(pairs, label: str, confirmed: bool) -> int:
    if not pairs:
        print("Нечего переносить.")
        return 0
    print(f"{label}:")
    for src, dst, rel in pairs:
        print(f"  {rel}")
    if not confirmed:
        print("\nНичего не записано. Повторить с --yes, если это то, что нужно.")
        return 1
    for src, dst, rel in pairs:
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(src, dst)
    print(f"\nПеренесено файлов: {len(pairs)}")
    return 0


def git_dirty(repo: pathlib.Path) -> bool:
    out = subprocess.run(["git", "-C", str(repo), "status", "--porcelain"],
                         capture_output=True, text=True)
    return bool(out.stdout.strip())


def main() -> int:
    ap = argparse.ArgumentParser(add_help=True)
    ap.add_argument("--repo", default=str(ROOT.parent / "ritmo-engine"))
    ap.add_argument("--pull", action="store_true")
    ap.add_argument("--push", action="store_true")
    ap.add_argument("--yes", action="store_true")
    a = ap.parse_args()

    repo = pathlib.Path(a.repo).expanduser().resolve()
    if not repo.is_dir():
        sys.exit(f"Нет репозитория движка: {repo}\nУкажи путь ключом --repo.")
    if a.pull and a.push:
        sys.exit("--pull и --push вместе не имеют смысла.")

    if a.pull:
        only_site, only_repo, diff = compare(repo)
        pairs = [(repo / f, SITE / f, f) for f in diff + only_repo]
        return copy(pairs, "Файлы сайта будут заменены версией движка", a.yes)

    if a.push:
        if git_dirty(repo):
            print(f"Внимание: в {repo} есть незакоммиченные изменения.")
        only_site, only_repo, diff = compare(repo)
        pairs = [(SITE / f, repo / f, f) for f in diff + only_site]
        code = copy(pairs, "В движок уйдут версии из сайта", a.yes)
        if code == 0 and a.yes:
            print("Проверить и закоммитить в репозитории движка руками:")
            print(f"  git -C {repo} diff")
        return code

    return show(repo)


if __name__ == "__main__":
    sys.exit(main())
