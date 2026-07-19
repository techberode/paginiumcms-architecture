#!/usr/bin/env zsh
# scripts/run-all-tests.zsh
# Kompletná testovacia sada PaginiumCMS + súhrn Failed/Error/Skipped na konci.
#
# Použitie (z ľubovoľného podadresára projektu):
#   ./scripts/run-all-tests.zsh
#   zsh scripts/run-all-tests.zsh

emulate -L zsh
setopt pipefail 2>/dev/null || true

TARGET_DIR="${PAGINIUMCMS_TEST_LOG_DIR:-$HOME/projects/paginiumcms_tests}"
TIMESTAMP=$(date +%d%m%y_%H%M)
LOG_FILE="${TARGET_DIR}/alltests_${TIMESTAMP}.log"
CURRENT_DIR=$PWD

mkdir -p "$TARGET_DIR"

# Nájdi koreň projektu (vendor/bin/phpunit)
while [[ "$PWD" != "/" && ! -f vendor/bin/phpunit ]]; do
  cd ..
done

if [[ ! -f vendor/bin/phpunit ]]; then
  print -u2 "❌ Chyba: Spusť skript zvnútra projektu PaginiumCMS (chýba vendor/bin/phpunit)."
  cd "$CURRENT_DIR"
  exit 1
fi

PROJECT_ROOT=$PWD
TOTAL_STEPS=11
CLEANUP_STEP=12

typeset -a FAILED_STEPS
typeset -A STEP_EXIT STEP_STATS STEP_ERRORS

PROGRESS_BAR_WIDTH=28
PROGRESS_ENABLED=1
[[ -n "${PAGINIUMCMS_NO_PROGRESS:-}" || ! -t 2 ]] && PROGRESS_ENABLED=0

strip_ansi() {
  sed 's/\x1b\[[0-9;]*m//g; s/\x1b\][^\x07]*\x07//g'
}

# Progress bar po dokončení kroku (stderr — neprekrýva live výstup testov).
render_step_progress() {
  local done=$1
  local total=$2
  local label="$3"
  local state=${4:-running}

  (( PROGRESS_ENABLED )) || return 0

  local pct=0 filled=0 i bar="" icon="⏳"
  (( total > 0 )) && pct=$(( done * 100 / total ))
  (( total > 0 )) && filled=$(( done * PROGRESS_BAR_WIDTH / total ))

  for (( i = 1; i <= PROGRESS_BAR_WIDTH; i++ )); do
    if (( i <= filled )); then
      bar+="#"
    else
      bar+="-"
    fi
  done

  case "$state" in
    ok) icon="✅" ;;
    fail) icon="❌" ;;
  esac

  print -u2 "[${bar}] ${done}/${total} (${pct}%) ${icon} ${label}"
}

# Vráti jednoriadkový súhrn metrik (Passed / Failed / Errors / Skipped / Warnings).
extract_stats() {
  local label="$1"
  local out="$2"
  local stats=""

  case "$label" in
    *PHPUnit*)
      local summary tests_line total failures errors skipped passed
      summary=$(print -r -- "$out" | grep -E '^(OK|FAILURES|ERRORS|OK, but)' | tail -1 | sed 's/^[[:space:]]*//; s/!$//')
      tests_line=$(print -r -- "$out" | grep -E '^Tests:' | tail -1 | sed 's/^[[:space:]]*//')
      total=$(print -r -- "$tests_line" | sed -n 's/.*Tests: \([0-9][0-9]*\).*/\1/p')
      failures=$(print -r -- "$tests_line" | sed -n 's/.*Failures: \([0-9][0-9]*\).*/\1/p')
      errors=$(print -r -- "$tests_line" | sed -n 's/.*Errors: \([0-9][0-9]*\).*/\1/p')
      skipped=$(print -r -- "$tests_line" | sed -n 's/.*Skipped: \([0-9][0-9]*\).*/\1/p')
      failures=${failures:-0}
      errors=${errors:-0}
      skipped=${skipped:-0}
      if [[ -n "$total" ]]; then
        passed=$(( total - failures - errors - skipped ))
        stats="Passed: ${passed} | Failed: ${failures} | Errors: ${errors} | Skipped: ${skipped}"
      else
        stats="${summary:-?}"
      fi
      ;;

    *PHPStan*)
      if print -r -- "$out" | grep -q '\[OK\] No errors'; then
        stats="OK | errors: 0"
      else
        local err_count
        err_count=$(print -r -- "$out" | grep -E '\[ERROR\] Found [0-9]+ errors?' | tail -1 | sed -E 's/.*Found ([0-9]+) errors?.*/\1/')
        stats="Failed | errors: ${err_count:-?}"
      fi
      ;;

    *Vitest*|*MSW*|*bezpečnostné*)
      local tf tests_line
      tf=$(print -r -- "$out" | grep -E 'Test Files' | tail -1 | sed 's/^[[:space:]]*//')
      tests_line=$(print -r -- "$out" | grep -E '^[[:space:]]*Tests[[:space:]]' | tail -1 | sed 's/^[[:space:]]*//')
      stats="${tf:-?}"
      [[ -n "$tests_line" ]] && stats="${stats} | ${tests_line}"
      ;;

    *ESLint*)
      stats=$(print -r -- "$out" | grep -E '[0-9]+ problems' | tail -1 | sed 's/^[[:space:]]*//')
      [[ -z "$stats" ]] && stats=$(print -r -- "$out" | grep -Ei 'error|warning' | tail -1)
      ;;

    *type-check*)
      local err_count
      err_count=$(print -r -- "$out" | grep -cE 'error TS[0-9]+' || true)
      if (( err_count == 0 )); then
        stats="OK | TS errors: 0"
      else
        stats="Failed | TS errors: ${err_count}"
      fi
      ;;

    *Composer*)
      stats=$(print -r -- "$out" | grep -Ei 'No security vulnerability|Found [0-9]+ advisories|audit found' | tail -1 | sed 's/^[[:space:]]*//')
      [[ -z "$stats" ]] && stats="see log"
      ;;

    *NPM*)
      stats=$(print -r -- "$out" | grep -Ei 'found [0-9]+ vulnerabilities|audited [0-9]+ packages|0 vulnerabilities' | tail -1 | sed 's/^[[:space:]]*//')
      [[ -z "$stats" ]] && stats="see log"
      ;;

    *build*)
      if print -r -- "$out" | grep -qiE 'built in|built in|Build complete'; then
        stats="OK | build succeeded"
      else
        stats="Failed | see error block"
      fi
      ;;

    *diagnose*)
      if print -r -- "$out" | grep -q 'Content storage looks healthy'; then
        local index_entries orphans unreadable pages
        index_entries=$(print -r -- "$out" | sed -n 's/.*Index entries[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        pages=$(print -r -- "$out" | sed -n 's/.*Pages on disk[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        orphans=$(print -r -- "$out" | sed -n 's/.*Index orphans (missing file)[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        unreadable=$(print -r -- "$out" | sed -n 's/.*Unreadable content files[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        stats="OK | index: ${index_entries:-?} | pages: ${pages:-?} | orphans: ${orphans:-0} | unreadable: ${unreadable:-0}"
      else
        local issue_count
        issue_count=$(print -r -- "$out" | grep -cE '^ \* ' || true)
        stats="Failed | issues: ${issue_count:-?}"
      fi
      ;;

    *)
      stats="exit captured"
      ;;
  esac

  [[ -z "$stats" ]] && stats="—"
  print -r -- "$stats"
}

# Vytiahne stručný zoznam: cesta (+ riadok) + popis chyby / failure / skip.
extract_error_summary() {
  local label="$1"
  local file="$2"
  local lines=""

  case "$label" in
    *PHPUnit*)
      lines=$(awk '
        / ✘ / {
          if (name != "") emit()
          name=$0
          sub(/^.* ✘ /, "", name)
          gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
          msg=""
          loc=""
          next
        }
        /├/ {
          line=$0
          sub(/^.*├[[:space:]]*/, "", line)
          if (line ~ /Failed asserting|Exception:|Error:|TypeError:/) msg=line
          next
        }
        /│/ && /\.php:[0-9]+/ {
          if (match($0, /(\/[^[:space:]]+\.php:[0-9]+)/, a)) loc=a[1]
          else if (match($0, /([A-Za-z0-9_./-]+\.php:[0-9]+)/, a)) loc=a[1]
          next
        }
        /┴/ { if (name != "") emit(); next }
        /^[0-9]+\) / {
          if (name != "") emit()
          name=$0; sub(/^[0-9]+\) /, "", name)
          msg=""; loc=""
          next
        }
        /^Failed asserting|^Exception:|^Error:|^TypeError:/ {
          if (msg == "") msg=$0
          next
        }
        /^\/.*\.php:[0-9]+$/ { loc=$0; next }
        /^(FAILURES|ERRORS)!/ { if (name != "") emit(); exit }
        function emit() {
          if (loc != "") print loc " — " name " — " msg
          else if (msg != "") print name " — " msg
          else print name
          name=""; msg=""; loc=""
        }
        END { if (name != "") emit() }
      ' "$file")
      if [[ -z "${lines//[[:space:]]/}" ]]; then
        lines=$(grep -E '^Tests: ' "$file" | tail -1 | sed 's/^/SUMMARY — /')
      fi
      ;;

    *PHPStan*)
      lines=$(awk '
        /^  Line   / { path=$0; sub(/^  Line   /, "", path); next }
        /^  [0-9]+     / {
          line=$0
          sub(/^  /, "", line)
          split(line, parts, /[[:space:]]+/, seps)
          n=parts[1]
          msg=line
          sub(/^[0-9]+[[:space:]]+/, "", msg)
          print path ":" n " — " msg
        }
      ' "$file")
      if [[ -z "${lines//[[:space:]]/}" ]]; then
        lines=$(grep -E '^\[ERROR\]|\.php' "$file" | tail -15)
      fi
      ;;

    *Vitest*|*MSW*|*bezpečnostné*)
      lines=$(awk '
        /^ FAIL / { test=$0; sub(/^ FAIL  /, ""); msg=""; loc=""; next }
        /^AssertionError:|^Error:|^TypeError:|^TestingLibraryElementError:/ { msg=$0; next }
        /❯/ {
          loc=$0
          sub(/^.*❯[[:space:]]*/, "", loc)
          if (test != "" && loc != "") {
            if (msg != "") print loc " — " test " — " msg
            else print loc " — " test
            test=""; msg=""; loc=""
          }
          next
        }
        /^ Test Files / && test != "" {
          if (loc != "") print loc " — " test " — " msg
          else print test " — " msg
          test=""
        }
      ' "$file")
      if [[ -z "${lines//[[:space:]]/}" ]]; then
        lines=$(grep -E '^ FAIL |\.tsx?:[0-9]+' "$file" | head -20)
      fi
      ;;

    *ESLint*)
      lines=$(awk '
        /^\/|^\\.\\./ {
          if (file != "" && detail != "") print file " — " detail
          file=$0; detail=""; next
        }
        /^[[:space:]]+[0-9]+:[0-9]+[[:space:]]+(error|warning)/ {
          detail=$0; gsub(/^[[:space:]]+/, "", detail)
        }
        END { if (file != "" && detail != "") print file " — " detail }
      ' "$file")
      ;;

    *type-check*)
      lines=$(grep -E '\.(tsx?|ts)\([0-9]+,[0-9]+\): error TS' "$file" | head -30)
      ;;

    *diagnose*)
      lines=$(grep -E '^ \\* |Problems detected|Unreadable content files|Index orphans' "$file" | sed 's/^[[:space:]]*\\* /ISSUE — /')
      ;;

    *Composer*|*NPM*|*build*)
      lines=$(grep -Ei 'advisor|vulnerabilit|error|failed|critical' "$file" | grep -viE '^npm notice' | tail -15)
      ;;

    *)
      lines=$(grep -Ei '\\.(php|tsx?|ts|js):[0-9]+|error TS|Failed asserting|FAIL ' "$file" | tail -20)
      ;;
  esac

  if [[ -z "${lines//[[:space:]]/}" ]]; then
    lines=$(grep -Ei 'error|failed|failure' "$file" | tail -10 | sed 's/^[[:space:]]*//')
  fi

  print -r -- "$lines"
}

run_step() {
  local num="$1"
  local label="$2"
  local cmd="$3"
  local tmp_out start_ts elapsed
  typeset -a _pipe
  tmp_out=$(mktemp)
  start_ts=$SECONDS

  print "==================================================" | tee -a "$LOG_FILE"
  print "⚙️ ${num}/${TOTAL_STEPS} ${label}" | tee -a "$LOG_FILE"
  print "----------------" >> "$LOG_FILE"

  # Live výstup do terminálu aj logu (ako pôvodný skript).
  (
    cd "$PROJECT_ROOT" || exit 127
    eval "$cmd"
  ) 2>&1 | tee -a "$LOG_FILE" | tee "$tmp_out"

  _pipe=("${pipestatus[@]}")
  local rc=${_pipe[1]:-1}

  local raw_output clean_output stats
  raw_output=$(<"$tmp_out")
  clean_output=$(print -r -- "$raw_output" | strip_ansi)
  print -r -- "$clean_output" > "$tmp_out"

  stats=$(extract_stats "$label" "$clean_output")
  STEP_STATS[$label]="$stats"
  STEP_EXIT[$label]=$rc

  if [[ "$label" == *PHPUnit* ]] && print -r -- "$stats" | grep -qE 'Failed: [1-9]|Errors: [1-9]'; then
    rc=1
    STEP_EXIT[$label]=1
  fi

  elapsed=$(( SECONDS - start_ts ))

  if (( rc != 0 )); then
    FAILED_STEPS+=("${num}/${TOTAL_STEPS} ${label} (exit ${rc})")
    STEP_ERRORS[$label]=$(extract_error_summary "$label" "$tmp_out")
    print "❌ ZLYHALO: ${label} (exit ${rc}, ${elapsed}s) | ${stats}" | tee -a "$LOG_FILE"
    render_step_progress "$num" "$TOTAL_STEPS" "${label} | ${stats} (${elapsed}s)" fail
  else
    print "✅ OK: ${label} (${elapsed}s) | ${stats}" | tee -a "$LOG_FILE"
    render_step_progress "$num" "$TOTAL_STEPS" "${label} | ${stats} (${elapsed}s)" ok
  fi

  rm -f "$tmp_out"
}

run_test_storage_cleanup() {
  local report start_ts elapsed stats rc=0
  start_ts=$SECONDS

  print "" | tee -a "$LOG_FILE"
  print "==================================================" | tee -a "$LOG_FILE"
  print "⚙️ ${CLEANUP_STEP}/${CLEANUP_STEP} Cleanup test artefaktov (iba generické / @example.com)" | tee -a "$LOG_FILE"
  print "----------------" >> "$LOG_FILE"

  report=$(cd "$PROJECT_ROOT" && php backend/bin/test-artifacts.php --purge 2>&1) || rc=$?
  print -r -- "$report" | tee -a "$LOG_FILE"

  elapsed=$(( SECONDS - start_ts ))
  stats=$(print -r -- "$report" | grep -E 'Po cleanup|test_users|Reálne účty' | head -3 | tr '\n' ' ' | sed 's/[[:space:]]*$//')
  [[ -z "$stats" ]] && stats="purge completed"

  if (( rc != 0 )); then
    print "❌ ZLYHALO: Cleanup test artefaktov (exit ${rc}, ${elapsed}s)" | tee -a "$LOG_FILE"
    render_step_progress "$CLEANUP_STEP" "$CLEANUP_STEP" "Cleanup | ${stats} (${elapsed}s)" fail
    FAILED_STEPS+=("${CLEANUP_STEP}/${CLEANUP_STEP} Cleanup test artefaktov (exit ${rc})")
    return $rc
  fi

  print "✅ OK: Cleanup test artefaktov (${elapsed}s) | ${stats}" | tee -a "$LOG_FILE"
  render_step_progress "$CLEANUP_STEP" "$CLEANUP_STEP" "Cleanup | ${stats} (${elapsed}s)" ok
  print "==================================================" | tee -a "$LOG_FILE"
}

print "🚀 Spúšťam kompletnú sadu testov PaginiumCMS..."
print "📂 Projekt: ${PROJECT_ROOT}"
print "📂 Log: ${LOG_FILE}"
print ""

{
  print "=================================================="
  print " CMS TEST LOG - $(date '+%Y-%m-%d %H:%M:%S')"
  print " Project: ${PROJECT_ROOT}"
  print "=================================================="
  print ""
} > "$LOG_FILE"

# ==================================================
# BACKEND
# ==================================================

run_step 1 "PHPUnit (backend testy)" \
  'vendor/bin/phpunit --colors=always'

run_step 2 "PHPStan (statická analýza, Level 8)" \
  'vendor/bin/phpstan analyse backend --level=8 --ansi'

run_step 3 "Composer Audit (bezpečnosť PHP závislostí)" \
  'composer audit'

# ==================================================
# FRONTEND
# ==================================================

run_step 4 "Vitest (frontend funkčné testy)" \
  'cd frontend && FORCE_COLOR=1 CI=true npm test'

run_step 5 "Frontend bezpečnostné testy (TypeScript/Middleware)" \
  'cd frontend && FORCE_COLOR=1 npm run test:security'

run_step 6 "TypeScript type-check (tsc --noEmit)" \
  'cd frontend && FORCE_COLOR=1 npm run type-check'

run_step 7 "ESLint (frontend lint)" \
  'cd frontend && FORCE_COLOR=1 npm run lint'

run_step 8 "Vitest MSW (mock API handlery)" \
  'cd frontend && FORCE_COLOR=1 npm run test:msw'

run_step 9 "Produkčný build (+ verify-dist-api-url)" \
  'cd frontend && FORCE_COLOR=1 npm run build:prod'

run_step 10 "NPM Audit (bezpečnosť frontend závislostí)" \
  'cd frontend && FORCE_COLOR=1 npm run audit:security'

run_step 11 "Content diagnose (backend/bin/console)" \
  'php backend/bin/console content:diagnose'

# ==================================================
# ZÁVEREČNÝ SÚHRN
# ==================================================
print ""
print "==================================================" | tee -a "$LOG_FILE"
print "📊 SÚHRN NÁSTROJOV (Passed / Failed / Errors / Skipped)" | tee -a "$LOG_FILE"
print "==================================================" | tee -a "$LOG_FILE"

labels=(
  "PHPUnit (backend testy)"
  "PHPStan (statická analýza, Level 8)"
  "Composer Audit (bezpečnosť PHP závislostí)"
  "Vitest (frontend funkčné testy)"
  "Frontend bezpečnostné testy (TypeScript/Middleware)"
  "TypeScript type-check (tsc --noEmit)"
  "ESLint (frontend lint)"
  "Vitest MSW (mock API handlery)"
  "Produkčný build (+ verify-dist-api-url)"
  "NPM Audit (bezpečnosť frontend závislostí)"
  "Content diagnose (backend/bin/console)"
)

i=1
for label in "${labels[@]}"; do
  status_icon="✅"
  rc=${STEP_EXIT[$label]:-1}
  (( rc != 0 )) && status_icon="❌"
  printf '%s %2d. %-45s %s\n' "$status_icon" "$i" "$label" "${STEP_STATS[$label]:-—}" | tee -a "$LOG_FILE"
  (( i++ ))
done

print "==================================================" | tee -a "$LOG_FILE"

if (( ${#FAILED_STEPS[@]} == 0 )); then
  print "✅ Všetky testy dobehli ÚSPEŠNE (${TOTAL_STEPS}/${TOTAL_STEPS})." | tee -a "$LOG_FILE"
else
  print "❌ Zlyhalo ${#FAILED_STEPS[@]} z ${TOTAL_STEPS} krokov:" | tee -a "$LOG_FILE"
  for step in "${FAILED_STEPS[@]}"; do
    print "   • ${step}" | tee -a "$LOG_FILE"
  done

  print "" | tee -a "$LOG_FILE"
  print "==================================================" | tee -a "$LOG_FILE"
  print "🔍 KONKRÉTNE CHYBY (cesta + popis)" | tee -a "$LOG_FILE"
  print "==================================================" | tee -a "$LOG_FILE"

  for label in ${(k)STEP_ERRORS}; do
    print "" | tee -a "$LOG_FILE"
    print "[ ${label} ]" | tee -a "$LOG_FILE"
    print "Stats: ${STEP_STATS[$label]:-—}" | tee -a "$LOG_FILE"
    print "--------------------------------------------------" | tee -a "$LOG_FILE"
    print -r -- "${STEP_ERRORS[$label]}" | tee -a "$LOG_FILE"
    print "--------------------------------------------------" | tee -a "$LOG_FILE"
  done
fi

# ==================================================
# CLEANUP — až po všetkých 11 testovacích krokoch
# ==================================================
run_test_storage_cleanup

print ""
print "👉 Log: file://${LOG_FILE}"

cd "$CURRENT_DIR"
(( ${#FAILED_STEPS[@]} == 0 )) || exit 1
