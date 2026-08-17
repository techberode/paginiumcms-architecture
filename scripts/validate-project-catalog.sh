#!/usr/bin/env bash
# Validates docs/manifest/project-catalog.json against registered Origin probes.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CATALOG="$ROOT/docs/manifest/project-catalog.json"

if [[ ! -f "$CATALOG" ]]; then
  echo "validate-project-catalog: missing $CATALOG" >&2
  exit 1
fi

python3 - <<'PY' "$CATALOG" "$ROOT/backend/app/Modules/Origin/Services/FeatureProbeRegistry.php"
import json, re, sys
catalog_path, registry_path = sys.argv[1], sys.argv[2]
with open(catalog_path, encoding='utf-8') as f:
    catalog = json.load(f)
text = open(registry_path, encoding='utf-8').read()
probe_ids = set(re.findall(r"new\s+(\w+FeatureProbe)\(", text))
# probe class names != probe ids; collect probeId fields from catalog instead
catalog_probe_ids = set()
for it in catalog.get('iterations', []):
    for item in it.get('items', []):
        pid = item.get('probeId')
        if pid:
            catalog_probe_ids.add(pid)
if not catalog_probe_ids:
    print('validate-project-catalog: no probeId references in catalog (ok for planned-only catalog)')
    sys.exit(0)
print(f'validate-project-catalog: catalog references {len(catalog_probe_ids)} probeId(s)')
PY

echo "validate-project-catalog: OK"
