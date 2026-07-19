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

typeset -a FAILED_STEPS
typeset -A STEP_EXIT STEP_STATS STEP_ERRORS

strip_ansi() {
  sed 's/\x1b\[[0-9;]*m//g; s/\x1b\][^\x07]*\x07//g'
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
        err_count=$(print -r -- "$out" | grep -E '^\[ERROR\] Found [0-9]+ errors' | tail -1 | sed 's/^\[ERROR\] Found //; s/ errors.*//')
        stats="Failed | errors: ${err_count:-?}"
      fi
      ;;

    *Vitest*|*MSW*)
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

    *TypeScript*)
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

    *Diagnose*)
      if print -r -- "$out" | grep -q 'Content storage looks healthy'; then
        local index_entries orphans unreadable pages
        index_entries=$(print -r -- "$out" | sed -n 's/.*Index entries[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        pages=$(print -r -- "$out" | sed -n 's/.*Pages on disk[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        articles=$(print -r -- "$out" | sed -n 's/.*Articles on disk[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        orphans=$(print -r -- "$out" | sed -n 's/.*Index orphans (missing file)[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        unreadable=$(print -r -- "$out" | sed -n 's/.*Unreadable content files[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
        backups=$(print -r -- "$out" | sed -n 's/.*Backup files in content dirs[[:space:]]*\([0-9][0-9]*\).*/\1/p' | tail -1)
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

# Vytiahne konkrétne bloky chýb pre zlyhaný krok.
extract_error_blocks() {
  local label="$1"
  local file="$2"
  local blocks=""

  case "$label" in
    *PHPUnit*)
      blocks=$(awk '
        /^There (was|were) [0-9]+ (failure|error)/ { show=1 }
        /^FAILURES|^ERRORS|^OK, but/ { show=1 }
        show { print }
        /^Tests: / && show { print; exit }
      ' "$file")
      if [[ -z "${blocks//[[:space:]]/}" ]]; then
        blocks=$(grep -E -A 12 ' ✘ |^[0-9]+\) ' "$file" | head -80)
      fi
      ;;

    *PHPStan*)
      blocks=$(grep -E -A 25 ' ------ ' "$file" | head -120)
      if [[ -z "${blocks//[[:space:]]/}" ]]; then
        blocks=$(print -r -- "$(grep -E '\[ERROR\]|Line' "$file" | tail -30)")
      fi
      ;;

    *Vitest*|*MSW*)
      blocks=$(awk '
        /^ FAIL / { show=1; buf=$0 ORS; next }
        show {
          if ($0 ~ /^⎯/ || $0 ~ /^ Test Files /) { print buf; show=0; buf="" }
          else { buf=buf $0 ORS }
        }
        END { if (buf!="") print buf }
      ' "$file")
      if [[ -z "${blocks//[[:space:]]/}" ]]; then
        blocks=$(grep -E -A 15 '^ FAIL |AssertionError|TestingLibraryElementError|Error:' "$file" | head -100)
      fi
      ;;

    *ESLint*|*TypeScript*)
      blocks=$(awk '
        /^\// { if (file && has) print buf; file=$0; buf=$0 ORS; has=0; next }
        /error|warning|problems/ { buf=buf $0 ORS; has=1 }
        END { if (file && has) print buf }
      ' "$file")
      ;;

    *Diagnose*)
      blocks=$(awk '
        /^Issues/ { show=1; print; next }
        show && /^ \* / { print; next }
        /^Problems detected/ { print; show=0 }
        /Unreadable content files|Index orphans|Backup files in content dirs/ {
          if (match($0, /[[:space:]][1-9][0-9]*[[:space:]]*$/)) print
        }
      ' "$file")
      if [[ -z "${blocks//[[:space:]]/}" ]]; then
        blocks=$(grep -E -A 12 'Issues|Problems detected|\[WARNING\]' "$file" | tail -25)
      fi
      ;;

    *)
      blocks=$(grep -E -i 'error|failed|failure|critical|ERRORS|FAILURES' "$file" | tail -25)
      ;;
  esac

  if [[ -z "${blocks//[[:space:]]/}" ]]; then
    blocks=$(tail -20 "$file")
  fi

  print -r -- "$blocks"
}

run_step() {
  local num="$1"
  local label="$2"
  local cmd="$3"
  local tmp_out
  tmp_out=$(mktemp)

  print "==================================================" | tee -a "$LOG_FILE"
  print "⚙️ ${num}/${TOTAL_STEPS} ${label}" | tee -a "$LOG_FILE"
  print "----------------" >> "$LOG_FILE"

  (
    cd "$PROJECT_ROOT" || exit 127
    eval "$cmd"
  ) 2>&1 | tee -a "$LOG_FILE" | tee "$tmp_out" >/dev/null

  local rc=${pipestatus[1]:-1}
  local raw_output clean_output stats
  raw_output=$(<"$tmp_out")
  clean_output=$(print -r -- "$raw_output" | strip_ansi)
  print -r -- "$clean_output" > "$tmp_out"

  stats=$(extract_stats "$label" "$clean_output")
  STEP_STATS[$label]="$stats"
  STEP_EXIT[$label]=$rc

  if (( rc != 0 )); then
    FAILED_STEPS+=("${num}/${TOTAL_STEPS} ${label} (exit ${rc})")
    STEP_ERRORS[$label]=$(extract_error_blocks "$label" "$tmp_out")
    print "❌ ZLYHALO: ${label} (exit ${rc}) | ${stats}" | tee -a "$LOG_FILE"
  else
    print "✅ OK: ${label} | ${stats}" | tee -a "$LOG_FILE"
  fi

  rm -f "$tmp_out"
}

print "🚀 Spúšťam kompletnú sadu testov PaginiumCMS..."
print "📂 Projekt: ${PROJECT_ROOT}"
print "📂 Log: ${LOG_FILE}"

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
  print "🔍 DETAILNÉ BLOKY CHÝB" | tee -a "$LOG_FILE"
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

print "==================================================" | tee -a "$LOG_FILE"
print ""
print "👉 Log: file://${LOG_FILE}"

cd "$CURRENT_DIR"
(( ${#FAILED_STEPS[@]} == 0 )) || exit 1
