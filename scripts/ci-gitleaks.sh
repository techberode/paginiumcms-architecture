#!/usr/bin/env bash
# CI helper for ISS-173. Scans the checkout tree + commits introduced by this event.
# Does not scan unrelated historical branches (those trip default gitleaks rules).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
CONFIG="${ROOT}/.gitleaks.toml"

if ! command -v gitleaks >/dev/null 2>&1; then
  echo "ci-gitleaks: gitleaks is not installed" >&2
  exit 1
fi

echo "ci-gitleaks: phase 1 — working tree (--no-git), rule private-key-pem only (.gitleaks.toml)"
gitleaks detect --no-git --source . --verbose --redact --exit-code 1 --config "$CONFIG"
echo "ci-gitleaks: phase 1 OK — no private-key material in checkout tree"

event="${GITHUB_EVENT_NAME:-}"
if [[ "$event" == "pull_request" ]]; then
  base="${GITHUB_BASE_REF:-main}"
  git fetch --no-tags --depth=50 origin "$base"
  echo "ci-gitleaks: phase 2 — PR commits origin/${base}..HEAD"
  gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
    --log-opts="origin/${base}..HEAD"
  echo "ci-gitleaks: phase 2 OK — no private-key material in PR commits"
elif [[ "$event" == "push" ]]; then
  before="${GITLEAKS_BEFORE:-${GITHUB_EVENT_BEFORE:-}}"
  sha="${GITHUB_SHA:-HEAD}"
  if [[ -z "$before" || "$before" == "0000000000000000000000000000000000000000" ]]; then
    echo "ci-gitleaks: phase 2 — last commit (no GITHUB_EVENT_BEFORE)"
    gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
      --log-opts="-n 1"
  else
    echo "ci-gitleaks: phase 2 — push commits ${before}..${sha}"
    gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
      --log-opts="${before}..${sha}"
  fi
  echo "ci-gitleaks: phase 2 OK — no private-key material in event commits"
fi

echo "::notice title=Gitleaks (ISS-173)::PASSED — no PEM/OpenSSH private keys in tree or event commits. A red workflow may be backend (composer audit) or frontend, not this job."
