#!/usr/bin/env bash
# Read-only admin concurrency probe (Iteration 100). See docs/en/developer/ADMIN_LOAD_SIMULATION.md
set -euo pipefail

BASE_URL="${PAGINIUM_PROBE_BASE_URL:-http://127.0.0.1:8080}"
# When BASE_URL is loopback but vhost needs a name (prod Docker nginx on 8089).
HOST_HEADER="${PAGINIUM_PROBE_HOST:-}"
USER_AGENT="${PAGINIUM_PROBE_USER_AGENT:-PaginiumCMS-AdminLoadProbe/1.0 (authorized read-only GET)}"
USER="${PAGINIUM_PROBE_USER:-}"
PASS="${PAGINIUM_PROBE_PASS:-}"
WORKERS=5
ROUNDS=1
CONFIRM=0
# One login, N parallel GET workers (avoids prod login rate limit 5/5min per email+IP).
SHARED_SESSION=0

usage() {
  echo "Usage: $0 [-n workers] [-r rounds] [-s shared-session] [-y confirm-prod] [-h]"
  echo "Env: PAGINIUM_PROBE_BASE_URL, PAGINIUM_PROBE_USER, PAGINIUM_PROBE_PASS,"
  echo "     PAGINIUM_PROBE_HOST (optional Host header), PAGINIUM_PROBE_USER_AGENT,"
  echo "     PAGINIUM_PROBE_SHARED_SESSION=1 (same as -s)"
  exit "${1:-0}"
}

while getopts "n:r:syh" opt; do
  case "$opt" in
    n) WORKERS="$OPTARG" ;;
    r) ROUNDS="$OPTARG" ;;
    s) SHARED_SESSION=1 ;;
    y) CONFIRM=1 ;;
    h) usage 0 ;;
    *) usage 1 ;;
  esac
done

if [[ "${PAGINIUM_PROBE_SHARED_SESSION:-0}" == "1" ]]; then
  SHARED_SESSION=1
fi

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

curl_probe() {
  # shellcheck disable=SC2068
  local extra=()
  if [[ -n "$HOST_HEADER" ]]; then
    extra+=(-H "Host: ${HOST_HEADER}")
  fi
  curl -sS -A "$USER_AGENT" "${extra[@]}" "$@"
}

extract_csrf_token() {
  local body="$1"
  if command -v jq >/dev/null 2>&1; then
    jq -r '.token // empty' <<<"$body" 2>/dev/null || true
    return
  fi
  sed -n 's/.*"token"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' <<<"$body" | head -1
}

login_worker() {
  local id="$1"
  local jar="$WORKDIR/cookies-${id}.txt"
  local body code csrf
  body="$(curl_probe -c "$jar" -b "$jar" -w $'\n%{http_code}' "${BASE_URL}/api/auth/csrf-token")" || {
    echo "Worker $id: CSRF fetch failed (curl error)" >&2
    return 1
  }
  code="${body##*$'\n'}"
  body="${body%$'\n'*}"
  csrf="$(extract_csrf_token "$body")"
  if [[ -z "$csrf" ]]; then
    echo "Worker $id: CSRF fetch failed (http=${code}, no token). Body: ${body:0:120}" >&2
    return 1
  fi
  code="$(curl_probe -c "$jar" -b "$jar" -o /dev/null -w '%{http_code}' -X POST "${BASE_URL}/api/auth/login" \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $csrf" \
    -d "$(printf '{"email":"%s","password":"%s"}' "$USER" "$PASS")")"
  if [[ "$code" != "200" ]]; then
    echo "Worker $id: login failed (http=${code})" >&2
    return 1
  fi
  echo "$jar"
}

probe_route() {
  local jar="$1"
  local route="$2"
  local start end ms code
  start="$(date +%s%3N)"
  code="$(curl_probe -o /dev/null -w '%{http_code}' -b "$jar" "${BASE_URL}${route}")"
  end="$(date +%s%3N)"
  ms=$((end - start))
  echo "${ms} ${code} ${route}"
}

run_worker() {
  local id="$1"
  local jar="$2"
  local round r
  for ((round = 1; round <= ROUNDS; round++)); do
    for r in "${ROUTES[@]}"; do
      probe_route "$jar" "$r"
    done
  done
}

echo "Probe: base=$BASE_URL workers=$WORKERS rounds=$ROUNDS routes=${#ROUTES[@]} shared_session=$SHARED_SESSION" >&2

RESULTS="$WORKDIR/results.txt"
: >"$RESULTS"

if [[ "$SHARED_SESSION" -eq 1 ]]; then
  echo "Shared session: models one PHPSESSID + parallel GETs (session lock). See ADMIN_LOAD_SIMULATION.md § shared session." >&2
  master_jar="$(login_worker master)" || exit 1
  for ((w = 1; w <= WORKERS; w++)); do
    cp "$master_jar" "$WORKDIR/cookies-${w}.txt"
  done
  for ((w = 1; w <= WORKERS; w++)); do
    run_worker "$w" "$WORKDIR/cookies-${w}.txt" >>"$RESULTS" &
  done
else
  if [[ "$WORKERS" -gt 5 ]]; then
    echo "Note: prod login rate limit is 5 POST /login per 5 min per email+IP; use -s or -n 5." >&2
  fi
  run_worker_with_login() {
    local id="$1"
    local jar
    jar="$(login_worker "$id")" || return 1
    run_worker "$id" "$jar"
  }
  for ((w = 1; w <= WORKERS; w++)); do
    run_worker_with_login "$w" >>"$RESULTS" &
  done
fi
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
