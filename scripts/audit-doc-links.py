#!/usr/bin/env python3
"""Audit internal markdown links under docs/ and repo-root doc entrypoints."""
from __future__ import annotations

import re
import sys
from pathlib import Path
from urllib.parse import unquote, urlparse

ROOT = Path(__file__).resolve().parents[1]
DOC_ROOTS = [
    ROOT / "docs",
    ROOT,
]
SCAN_FILES: list[Path] = []
for base in [ROOT / "docs/en", ROOT / "docs/sk", ROOT / "docs"]:
    if not base.exists():
        continue
    for path in sorted(base.rglob("*.md")):
        if path.is_symlink():
            continue
        if "meta/it18" in path.as_posix():
            continue
        SCAN_FILES.append(path)
for name in ("README.md", "CHANGELOG.md", "SECURITY.md", "LOCAL_TEST_LOGS.md.example"):
    p = ROOT / name
    if p.exists():
        SCAN_FILES.append(p)

LINK_RE = re.compile(r"\[[^\]]*\]\(([^)]+)\)")
SKIP_PREFIXES = ("http://", "https://", "mailto:", "#", "data:")

broken: list[tuple[str, str, str, str]] = []
checked = 0


def resolve_link(source: Path, target: str) -> Path | None:
    target = target.strip()
    if not target or any(target.startswith(p) for p in SKIP_PREFIXES):
        return None
    # strip anchor and title
    path_part = target.split("#", 1)[0].strip()
    if not path_part:
        return source
    path_part = unquote(path_part)
    if path_part.startswith("/"):
        candidate = ROOT / path_part.lstrip("/")
    else:
        candidate = (source.parent / path_part).resolve()
    return candidate


for source in SCAN_FILES:
    try:
        text = source.read_text(encoding="utf-8")
    except OSError as exc:
        broken.append((str(source.relative_to(ROOT)), "?", "read_error", str(exc)))
        continue
    for raw in LINK_RE.findall(text):
        checked += 1
        candidate = resolve_link(source, raw)
        if candidate is None:
            continue
        rel = str(source.relative_to(ROOT))
        if candidate.exists():
            continue
        # allow directory links if dir exists
        if candidate.is_dir():
            continue
        try:
            resolved = str(candidate.relative_to(ROOT))
        except ValueError:
            resolved = str(candidate)
        broken.append((rel, raw, resolved, "missing"))

print(f"Scanned {len(SCAN_FILES)} markdown files, {checked} links checked.")
print(f"Broken links: {len(broken)}")
print()

# group by pattern
by_target: dict[str, list[str]] = {}
for src, raw, resolved, reason in broken:
    by_target.setdefault(resolved, []).append(f"{src} -> ({raw})")

for resolved in sorted(by_target.keys())[:80]:
    refs = by_target[resolved]
    print(f"MISSING: {resolved}  ({len(refs)} refs)")
    for ref in refs[:3]:
        print(f"  - {ref}")
    if len(refs) > 3:
        print(f"  ... +{len(refs) - 3} more")
    print()

if len(by_target) > 80:
    print(f"... and {len(by_target) - 80} more missing targets")

sys.exit(1 if broken else 0)
