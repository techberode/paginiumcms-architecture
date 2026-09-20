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

gitleaks detect --no-git --source . --verbose --redact --exit-code 1 --config "$CONFIG"

event="${GITHUB_EVENT_NAME:-}"
if [[ "$event" == "pull_request" ]]; then
  base="${GITHUB_BASE_REF:-main}"
  git fetch --no-tags --depth=50 origin "$base"
  gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
    --log-opts="origin/${base}..HEAD"
elif [[ "$event" == "push" ]]; then
  before="${GITLEAKS_BEFORE:-${GITHUB_EVENT_BEFORE:-}}"
  sha="${GITHUB_SHA:-HEAD}"
  if [[ -z "$before" || "$before" == "0000000000000000000000000000000000000000" ]]; then
    gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
      --log-opts="-n 1"
  else
    gitleaks detect --source . --verbose --redact --exit-code 1 --config "$CONFIG" \
      --log-opts="${before}..${sha}"
  fi
fi
