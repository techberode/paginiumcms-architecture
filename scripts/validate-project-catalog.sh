#!/usr/bin/env bash
# Validates docs/manifest/project-catalog.json against registered Origin probes and i18n keys.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CATALOG="$ROOT/docs/manifest/project-catalog.json"
CHECKLIST="$ROOT/docs/manifest/implementation-checklist.json"
PROBES_DIR="$ROOT/backend/app/Modules/Origin/Probes"
ORIGIN_I18N="$ROOT/frontend/src/i18n/modules/origin/en.ts"
ORIGIN_LANG_SK="$ROOT/backend/lang/sk/origin.php"
ORIGIN_LANG_EN="$ROOT/backend/lang/en/origin.php"

if [[ ! -f "$CATALOG" ]]; then
  echo "validate-project-catalog: missing $CATALOG" >&2
  exit 1
fi

python3 - <<'PY' "$CATALOG" "$CHECKLIST" "$PROBES_DIR" "$ORIGIN_I18N" "$ORIGIN_LANG_SK" "$ORIGIN_LANG_EN"
import json, re, sys
from pathlib import Path

catalog_path, checklist_path, probes_dir, i18n_path, lang_sk, lang_en = sys.argv[1:7]

with open(catalog_path, encoding='utf-8') as f:
    catalog = json.load(f)

if Path(checklist_path).is_file():
    with open(checklist_path, encoding='utf-8') as f:
        checklist = json.load(f)
    catalog_ids = {it.get('id') for it in catalog.get('iterations', [])}
    for sl in checklist.get('slices', []):
        for cid in sl.get('catalogIterationIds', []):
            if cid not in catalog_ids:
                raise SystemExit(f'validate-project-catalog: checklist slice references unknown iteration {cid!r}')

probe_ids_in_code = set()
for path in Path(probes_dir).glob('*FeatureProbe.php'):
    text = path.read_text(encoding='utf-8')
    m = re.search(r"return\s+'([^']+)'\s*;", text)
    if m and 'function id' in text:
        # find id() method return
        id_match = re.search(r"function id\(\)[^{]+\{[^}]*return\s+'([^']+)'", text, re.S)
        if id_match:
            probe_ids_in_code.add(id_match.group(1))

catalog_probe_ids = set()
title_keys = set()
for it in catalog.get('iterations', []):
    title_keys.add(it.get('titleKey', ''))
    for item in it.get('items', []):
        pid = item.get('probeId')
        if pid:
            catalog_probe_ids.add(pid)
        tk = item.get('titleKey')
        if tk:
            title_keys.add(tk)

missing_probes = sorted(catalog_probe_ids - probe_ids_in_code)
if missing_probes:
    raise SystemExit(f'validate-project-catalog: catalog probeId(s) without probe class: {missing_probes}')

i18n_text = Path(i18n_path).read_text(encoding='utf-8')

def i18n_key_present(key: str) -> bool:
    if not key:
        return True
    if key in i18n_text:
        return True
    # Nested origin keys: origin.catalog.it86 → catalog block uses it86:
    if key.startswith('origin.'):
        leaf = key.rsplit('.', 1)[-1]
        if re.search(rf'\b{re.escape(leaf)}\s*:', i18n_text):
            return True
    return False

missing_i18n = sorted(k for k in title_keys if not i18n_key_present(k))
if missing_i18n:
    raise SystemExit(f'validate-project-catalog: missing origin i18n keys: {missing_i18n[:5]}… ({len(missing_i18n)} total)')

def lang_catalog_keys(path: str) -> set[str]:
    text = Path(path).read_text(encoding='utf-8')
    if "'catalog'" not in text:
        return set()
    return set(re.findall(r"'(\w+)'\s*=>\s*'", text))

for lang_path in (lang_sk, lang_en):
    if not Path(lang_path).is_file():
        raise SystemExit(f'validate-project-catalog: missing backend lang file {lang_path}')
    catalog_keys = lang_catalog_keys(lang_path)
    missing_lang = sorted(
        k for k in title_keys
        if k.startswith('origin.catalog.')
        and k.rsplit('.', 1)[-1] not in catalog_keys
    )
    if missing_lang:
        raise SystemExit(
            f'validate-project-catalog: missing backend/lang catalog keys in {lang_path}: {missing_lang[:5]}… ({len(missing_lang)} total)'
        )

print(f'validate-project-catalog: {len(catalog_probe_ids)} probeId(s), {len(catalog.get("iterations", []))} iteration(s) OK')
PY

echo "validate-project-catalog: OK"
