#!/usr/bin/env python3
"""Fix known broken internal doc links (deep paths, admin URLs, removed .cursorrules)."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SCAN = [
    *list((ROOT / "docs/en").rglob("*.md")),
    *list((ROOT / "docs/sk").rglob("*.md")),
    ROOT / "docs/ISSUES.md",
]


def fix_content(text: str, rel: Path) -> str:
    parts = rel.parts

    if len(parts) >= 3 and parts[0] == "docs" and parts[2] == "architecture":
        text = text.replace("](../../CHANGELOG.md", "](../../../CHANGELOG.md")

    if len(parts) >= 2 and parts[0] == "docs" and parts[1] in ("en", "sk"):
        locale = parts[1]
        text = re.sub(r"\]\(" + locale + r"/", "](", text)
        if locale == "en":
            text = text.replace("](../en/", "](../")
        if locale == "sk":
            text = text.replace("](../sk/", "](../")
            text = text.replace("](../en/architecture/", "../../en/architecture/")
            text = text.replace("](../en/user/LANDING_PAGE.md)", "](LANDING_PAGE.md)")
            text = text.replace("](../en/user/MEDIA_IN_CONTENT.md", "](MEDIA_IN_CONTENT.md)")
            text = text.replace("](../en/user/PAGE_HERO_IMAGES.md)", "](PAGE_HERO_IMAGES.md)")
            text = text.replace(
                "](../architecture/CODE_POLICY.md)",
                "](../../en/architecture/CODE_POLICY.md)",
            )

        text = re.sub(
            r"\]\(CHANGELOG\.md\)",
            "](../../CHANGELOG.md)" if locale == "en" else "](../../CHANGELOG.md)",
            text,
        )
        text = text.replace("](meta/MKDOCS", "](../../meta/MKDOCS")
        if "runbooks" in parts or rel.name == "NAVIGATION.md":
            text = text.replace("](../meta/MKDOCS", "](../../meta/MKDOCS")

    if rel == Path("docs/ISSUES.md"):
        text = text.replace("](../deploy/", "](deploy/")

    text = text.replace("../../deploy/DEPLOY.md", "../deploy/DEPLOY.md")

    text = re.sub(r"\]\(ISS-(\d+)\)", r"](../ISSUES.md#iss-\1)", text)

    if "developer" in parts:
        text = text.replace("](../../scripts/", "](../../../scripts/")

    text = re.sub(
        r"\[`\.cursorrules`\]\([^)]+\)",
        "[Security baseline](../developer/SECURITY.md)",
        text,
    )
    text = text.replace("../../../.cursorrules", "../../developer/SECURITY.md")
    text = text.replace("](../../.cursorrules", "../../developer/SECURITY.md")

    text = text.replace("/platform/origin", "/origin")

    text = re.sub(
        r"\[([^\]]+)\]\([^)]*sk/marketing[^)]*\)",
        r"\1 (SK marketing drafts — optional local `docs/sk/marketing/`, gitignored)",
        text,
    )

    text = text.replace(
        "[CLANEK_BETA_35_45_SUHRN.md](CLANEK_BETA_35_45_SUHRN.md)",
        "CHANGELOG entries for beta.35–beta.45",
    )
    text = text.replace(
        "[Súhrn beta 35–45](CLANEK_BETA_35_45_SUHRN.md)",
        "CHANGELOG entries for beta.35–beta.45",
    )

    text = text.replace("](ITERATION_SITE_DESIGN.md)", "](../ITERATION_SITE_DESIGN.md)")
    text = text.replace("](developer/ACCESS_CONTROL.md)", "](user/ACCESS_CONTROL.md)")

    text = text.replace("`![alt](url)`", "`![alt](media-url)`")
    text = text.replace("`![…](…)`", "`duplicate hero image markdown`")
    text = text.replace("Remove duplicate `![…](…)` images", "Remove duplicate hero `img` / markdown images")

    text = text.replace(
        "](../RELEASE_2_1_0_BETA_88.md)",
        "](../en/RELEASE_2_1_0_BETA_88.md)",
    )

    return text


def main() -> None:
    changed = 0
    for path in SCAN:
        if not path.is_file() or path.is_symlink():
            continue
        if "meta/it18" in path.as_posix():
            continue
        rel = path.relative_to(ROOT)
        original = path.read_text(encoding="utf-8")
        updated = fix_content(original, rel)
        if updated != original:
            path.write_text(updated, encoding="utf-8")
            changed += 1
    print(f"fix-doc-links-deep: updated {changed} file(s)")


if __name__ == "__main__":
    main()
