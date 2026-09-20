#!/usr/bin/env bash
# Block private-key material before commit/push (ISS-173).
# Prints paths only — never file contents. Used by iteration-gate and .githooks/pre-commit.
# CI uses gitleaks; this script is the no-extra-dep local gate.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

fail() { echo -e "${RED}secret-scan: $*${NC}" >&2; exit 1; }
ok() { echo -e "${GREEN}secret-scan: $*${NC}"; }

# Split the PEM fence so this file is not itself a gitleaks hit.
pem_fence='-----'
pem_line() {
  printf '%sBEGIN %s%s' "$pem_fence" "$1" "$pem_fence"
}

pem_regex() {
  printf '^(%s|%s|%s|%s|%s)' \
    "$(pem_line 'OPENSSH PRIVATE KEY')" \
    "$(pem_line 'RSA PRIVATE KEY')" \
    "$(pem_line 'EC PRIVATE KEY')" \
    "$(pem_line 'DSA PRIVATE KEY')" \
    "$(pem_line 'PRIVATE KEY')"
}

file_looks_like_private_key() {
  local f="$1"
  [[ -f "$f" && -r "$f" ]] || return 1
  [[ "$(wc -c < "$f")" -gt 2000000 ]] && return 1
  grep -q -E -e "$(pem_regex)" "$f"
}

if [[ "${1:-}" == "--self-test" ]]; then
  tmp="$(mktemp -d)"
  trap 'rm -rf "$tmp"' EXIT
  printf '%s\n' "$(pem_line 'OPENSSH PRIVATE KEY')" > "$tmp/leaked"
  if file_looks_like_private_key "$tmp/leaked"; then
    :
  else
    fail "self-test missed OpenSSH PEM fixture"
  fi
  printf 'not a key\n' > "$tmp/clean"
  file_looks_like_private_key "$tmp/clean" && fail "self-test false positive on clean file"
  ok "self-test OK"
  exit 0
fi

if [[ "${1:-}" == "--scan-file" ]]; then
  if file_looks_like_private_key "${2:?}"; then
    echo "private-key-pem  $2"
    exit 1
  fi
  exit 0
fi

hits=0

while IFS= read -r f; do
  [[ -z "$f" ]] && continue
  case "$(basename "$f")" in
    id_rsa|id_dsa|id_ecdsa|id_ed25519|github_deploy_key)
      echo "blocked-filename  $f"
      hits=1
      ;;
  esac
done < <(git ls-files -c -o --exclude-standard)

rg_globs=(
  --glob '!.git/**'
  --glob '!vendor/**'
  --glob '!**/node_modules/**'
  --glob '!frontend/dist/**'
  --glob '!frontend/build/**'
  --glob '!scripts/secret-scan.sh'
)

if command -v rg >/dev/null 2>&1; then
  mapfile -t pem_hits < <(
    rg -l --hidden --follow --max-filesize 2M -I "${rg_globs[@]}" \
      -e "$(pem_regex)" . 2>/dev/null || true
  )
else
  mapfile -t pem_hits < <(
    git grep -I -l -E "$(pem_regex)" -- . 2>/dev/null || true
  )
fi

for f in "${pem_hits[@]+"${pem_hits[@]}"}"; do
  [[ -z "$f" ]] && continue
  echo "private-key-pem  $f"
  hits=1
done

if [[ "$hits" -ne 0 ]]; then
  fail "blocked private-key path(s). Remove them, rotate if they were ever pushed, never commit."
fi

if command -v gitleaks >/dev/null 2>&1; then
  if ! git diff --cached --quiet 2>/dev/null; then
    gitleaks protect --staged --redact --exit-code 1 --config "${ROOT}/.gitleaks.toml" \
      || fail "gitleaks protect --staged failed"
  fi
fi

ok "no private-key material in the working tree"
