#!/usr/bin/env python3
"""Apply bulk link path fixes after docs/en + docs/sk migration."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

# Order matters: longer / more specific patterns first.
REPLACEMENTS: list[tuple[str, str]] = [
    (r"\]\(\../../SK/docs/ISSUES\.md", "](../sk/ISSUES.md"),
    (r"\]\(\../../EN/docs/ISSUES\.md", "](../en/ISSUES.md"),
    (r"\]\(\../../../LOCAL_TEST_LOGS\.md\.example", "](../../LOCAL_TEST_LOGS.md.example"),
    (r"\]\(\../../LOCAL_TEST_LOGS\.md\.example", "](../../LOCAL_TEST_LOGS.md.example"),
    (r"\]\(\../LOCAL_TEST_LOGS\.md\.example", "](../../LOCAL_TEST_LOGS.md.example"),
    (r"\]\(\../../CHANGELOG\.md", "](../../../CHANGELOG.md"),
    (r"\]\(\../CHANGELOG\.md", "](../../CHANGELOG.md"),
    (r"\]\(\../../SECURITY\.md", "](../../../SECURITY.md"),
    (r"\]\(\../SECURITY\.md", "](../../SECURITY.md"),
    (r"\]\(\../AUDIT_REPORT\.md", "](../../AUDIT_REPORT.md"),
    (r"\]\(\../RECOMMENDATIONS\.md", "](../../RECOMMENDATIONS.md"),
]

NAVIGATION_ONLY: list[tuple[str, str]] = [
    (r"\]\(\../README\.md", "](README.md"),
]

FLAT_ISSUES_ONLY: list[tuple[str, str]] = [
    (r"\]\(\../sk/ISSUES\.md", "](sk/ISSUES.md"),
    (r"\]\(\../en/ISSUES\.md", "](en/ISSUES.md"),
    (r"\]\(\../../CHANGELOG\.md", "](../CHANGELOG.md"),
]


def process_file(path: Path, extra: list[tuple[str, str]] | None = None) -> int:
    text = path.read_text(encoding="utf-8")
    original = text
    rules = REPLACEMENTS + (extra or [])
    for pattern, repl in rules:
        text = re.sub(pattern, repl, text)
    if text != original:
        path.write_text(text, encoding="utf-8")
        return 1
    return 0


def main() -> None:
    changed = 0
    for tree in ("docs/en", "docs/sk"):
        base = ROOT / tree
        for path in base.rglob("*.md"):
            extra = NAVIGATION_ONLY if path.name == "NAVIGATION.md" else None
            changed += process_file(path, extra)
    flat_issues = ROOT / "docs/ISSUES.md"
    if flat_issues.is_file() and not flat_issues.is_symlink():
        changed += process_file(flat_issues, FLAT_ISSUES_ONLY)
    print(f"Updated {changed} markdown files.")


if __name__ == "__main__":
    main()
