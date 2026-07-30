#!/usr/bin/env bash
# Static security hygiene: outbound HTTP helpers must use OutboundUrlGuard.
# Fail-closed for new call sites; known allow-list below.
# Usage: ./scripts/security-static-grep.sh  (exit 0 = clean)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Files allowed to call curl_*/file_get_contents for HTTP without inline Guard
# (they must still gate via OutboundUrlGuard elsewhere in the same class).
ALLOW_REGEX='(OutboundUrlGuard\.php|GitHubService\.php|GitHubReleaseClient\.php|OAuthSsoService\.php|SystemDeployTriggerService\.php|GeoIp|Ntfy|Discord|Webhook|HttpClient|file_get_contents\(.*__DIR__|file_get_contents\(\$path|file_get_contents\(\$full)'

hits="$(rg -n --glob '*.php' -e 'curl_exec\s*\(|curl_init\s*\(|file_get_contents\s*\(\s*['\''\"]https?://' backend/app 2>/dev/null || true)"

if [[ -z "${hits}" ]]; then
  echo "security-static-grep: OK (no raw outbound HTTP literals in backend/app)"
  exit 0
fi

# Filter allow-listed paths / patterns
bad=""
while IFS= read -r line; do
  [[ -z "$line" ]] && continue
  file="${line%%:*}"
  if echo "$file" | rg -q 'OutboundUrlGuard\.php$'; then
    continue
  fi
  # Services known to call assertAllowed before fetch
  if echo "$file" | rg -q '(OAuthSsoService|GitHubService|GitHubReleaseClient|SystemDeploy|Notification|Ntfy|Discord|Webhook|GeoIp|IpApi|HttpOutbound)'; then
    continue
  fi
  bad+="$line"$'\n'
done <<< "$hits"

if [[ -n "${bad}" ]]; then
  echo "security-static-grep: FAIL — possible unguarded outbound HTTP:"
  echo "$bad"
  exit 1
fi

echo "security-static-grep: OK (hits only in allow-listed outbound clients)"
exit 0
