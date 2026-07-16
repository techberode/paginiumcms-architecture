#!/usr/bin/env python3
"""Inject PHPStan level-8 array value types into PHPDoc blocks."""

from __future__ import annotations

import re
import sys
from pathlib import Path

ARRAY_TYPE = "array<string, mixed>"
ROOT = Path(__file__).resolve().parents[1]
TARGETS = [ROOT / "backend/app", ROOT / "backend/bootstrap"]


def has_array_type_tag(doc: str, kind: str, name: str | None = None) -> bool:
    if kind == "return":
        return bool(re.search(r"@return\s+array<", doc) or re.search(rf"@return\s+{re.escape(ARRAY_TYPE)}", doc))
    if kind == "param" and name:
        return bool(
            re.search(rf"@param\s+array<[^>]+>\s+\${re.escape(name)}\b", doc)
            or re.search(rf"@param\s+{re.escape(ARRAY_TYPE)}\s+\${re.escape(name)}\b", doc)
        )
    if kind == "var":
        return bool(re.search(r"@var\s+array<", doc) or re.search(rf"@var\s+{re.escape(ARRAY_TYPE)}", doc))
    return False


def inject_tags(doc: str, params: list[str], returns_array: bool) -> str:
    doc = doc.rstrip()
    if doc.endswith("*/"):
        body = doc[:-2].rstrip()
    else:
        body = doc

    for param in params:
        if has_array_type_tag(body, "param", param):
            continue
        body += f"\n * @param {ARRAY_TYPE} ${param}"

    if returns_array and not has_array_type_tag(body, "return"):
        body += f"\n * @return {ARRAY_TYPE}"

    return body + "\n */"


def patch_properties(content: str) -> str:
    def repl(match: re.Match[str]) -> str:
        indent, prop = match.group(1), match.group(2)
        before = content[: match.start()]
        window = before[-400:]
        if re.search(r"@var\s+array<", window) or re.search(rf"@var\s+{re.escape(ARRAY_TYPE)}", window):
            return match.group(0)
        return f"{indent}/** @var {ARRAY_TYPE} */\n{indent}{prop}"

    return re.sub(
        r"(?P<indent>^[ \t]*)(?P<prop>(?:public|protected|private)\s+array\s+\$\w+)",
        repl,
        content,
        flags=re.MULTILINE,
    )


def patch_callables(content: str) -> str:
    signature_re = re.compile(
        r"(?P<doc>/\*\*(?:(?!\*/).)*\*/\s*)?"
        r"(?P<prefix>(?:public|protected|private|static)\s+(?:static\s+)?)?"
        r"function\s+(?P<name>\w+)\s*\((?P<params>[^)]*)\)"
        r"(?:\s*:\s*(?P<return>array))?",
        re.MULTILINE | re.DOTALL,
    )

    offset = 0
    parts: list[str] = []
    for match in signature_re.finditer(content):
        parts.append(content[offset : match.start()])
        doc = match.group("doc") or ""
        params_raw = match.group("params") or ""
        returns_array = match.group("return") == "array"

        array_params: list[str] = []
        for chunk in params_raw.split(","):
            chunk = chunk.strip()
            if not chunk:
                continue
            pm = re.search(r"array\s+\$(\w+)", chunk)
            if pm:
                array_params.append(pm.group(1))

        if not array_params and not returns_array:
            parts.append(match.group(0))
            offset = match.end()
            continue

        if doc:
            new_doc = inject_tags(doc, array_params, returns_array)
            parts.append(new_doc + match.group(0)[len(doc) :])
        else:
            indent_match = re.search(r"(?m)^([ \t]*)", content[: match.start()][::-1])
            indent = ""
            prefix = content[: match.start()]
            line_start = prefix.rfind("\n") + 1
            indent = prefix[line_start:]
            new_doc = f"/**"
            for param in array_params:
                new_doc += f"\n{indent} * @param {ARRAY_TYPE} ${param}"
            if returns_array:
                new_doc += f"\n{indent} * @return {ARRAY_TYPE}"
            new_doc += f"\n{indent} */"
            parts.append(f"{new_doc}\n{indent}{match.group(0)}")

        offset = match.end()

    parts.append(content[offset:])
    return "".join(parts)


def patch_file(path: Path) -> bool:
    original = path.read_text(encoding="utf-8")
    updated = patch_callables(patch_properties(original))
    if updated != original:
        path.write_text(updated, encoding="utf-8")
        return True
    return False


def main() -> int:
    changed = 0
    for base in TARGETS:
        for path in sorted(base.rglob("*.php")):
            if patch_file(path):
                changed += 1
                print(path.relative_to(ROOT))
    print(f"Patched {changed} files")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
