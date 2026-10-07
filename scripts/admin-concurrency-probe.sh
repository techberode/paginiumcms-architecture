#!/usr/bin/env bash
# Read-only admin concurrency probe (Iteration 100). See docs/en/developer/ADMIN_LOAD_SIMULATION.md
set -euo pipefail

BASE_URL="${PAGINIUM_PROBE_BASE_URL:-http://127.0.0.1:8080}"
USER="${PAGINIUM_PROBE_USER:-}"
PASS="${PAGINIUM_PROBE_PASS:-}"
WORKERS=5
ROUNDS=1
CONFIRM=0

usage() {
  echo "Usage: $0 [-n workers] [-r rounds] [-y confirm-prod] [-h]"
  echo "Env: PAGINIUM_PROBE_BASE_URL, PAGINIUM_PROBE_USER, PAGINIUM_PROBE_PASS"
  exit "${1:-0}"
}

while getopts "n:r:yh" opt; do
  case "$opt" in
    n) WORKERS="$OPTARG" ;;
    r) ROUNDS="$OPTARG" ;;
    y) CONFIRM=1 ;;
    h) usage 0 ;;
    *) usage 1 ;;
  esac
done

if [[ "$WORKERS" -gt 12 ]]; then
  echo "Max 12 workers (-n) for safety." >&2
  exit 1
fi

if [[ -z "$USER" || -z "$PASS" ]]; then
  echo "Set PAGINIUM_PROBE_USER and PAGINIUM_PROBE_PASS." >&2
  exit 1
fi

if [[ "$CONFIRM" -eq 0 ]] && [[ "$BASE_URL" =~ https?://[^/]*\.(sk|com|fun|eu) ]]; then
  echo "Refusing to run against likely production URL: $BASE_URL" >&2
  echo "Re-run with -y if this is intentional (read-only GETs only)." >&2
  exit 1
fi

# P1/P2 read-only admin GETs (extend per ITERATION_100.md)
ROUTES=(
  "/api/admin/dashboard/overview"
  "/api/admin/audit/stats"
  "/api/admin/jobs"
  "/api/admin/metrics/apm"
  "/api/admin/project-plans/overview"
)

WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT

login_worker() {
  local id="$1"
  local jar="$WORKDIR/cookies-${id}.txt"
  local csrf
  csrf="$(curl -sS -c "$jar" -b "$jar" "${BASE_URL}/api/auth/csrf-token" | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')"
  if [[ -z "$csrf" ]]; then
    echo "Worker $id: CSRF fetch failed" >&2
    return 1
  fi
  curl -sS -c "$jar" -b "$jar" -X POST "${BASE_URL}/api/auth/login" \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $csrf" \
    -d "$(printf '{"email":"%s","password":"%s"}' "$USER" "$PASS")" >/dev/null
  echo "$jar"
}

probe_route() {
  local jar="$1"
  local route="$2"
  local start end ms code
  start="$(date +%s%3N)"
  code="$(curl -sS -o /dev/null -w '%{http_code}' -b "$jar" "${BASE_URL}${route}")"
  end="$(date +%s%3N)"
  ms=$((end - start))
  echo "${ms} ${code} ${route}"
}

run_worker() {
  local id="$1"
  local jar
  jar="$(login_worker "$id")" || return 1
  local round r
  for ((round = 1; round <= ROUNDS; round++)); do
    for r in "${ROUTES[@]}"; do
      probe_route "$jar" "$r"
    done
  done
}

echo "Probe: base=$BASE_URL workers=$WORKERS rounds=$ROUNDS routes=${#ROUTES[@]}" >&2

RESULTS="$WORKDIR/results.txt"
: >"$RESULTS"

for ((w = 1; w <= WORKERS; w++)); do
  run_worker "$w" >>"$RESULTS" &
done
wait

if [[ ! -s "$RESULTS" ]]; then
  echo "No results (login failed?)." >&2
  exit 1
fi

sort -n "$RESULTS" | awk '
  { ms=$1; code=$2; route=$3; sum+=ms; if (NR==1) min=ms; max=ms; if (code>=500) err++ }
  END {
    printf "samples=%d min_ms=%d max_ms=%d avg_ms=%.0f http5xx=%d\n", NR, min, max, sum/NR, err+0
  }
'

echo "--- slowest 10 ---" >&2
sort -rn "$RESULTS" | head -10 >&2
