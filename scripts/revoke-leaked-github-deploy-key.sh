#!/usr/bin/env bash
# Revoke the leaked GitHub deploy key from cbc167fa (comment admin@techberode.com).
# Does not print private key material. Requires: gh auth login (repo admin).
#
#   REPO=techberode/paginiumcms-architecture ./scripts/revoke-leaked-github-deploy-key.sh
set -euo pipefail

REPO="${REPO:-techberode/paginiumcms-architecture}"
COMMENT_MARK="${COMMENT_MARK:-admin@techberode.com}"

if ! gh auth status -h github.com >/dev/null 2>&1; then
  echo "revoke-leaked-github-deploy-key: gh is not authenticated. Run: gh auth login -h github.com" >&2
  exit 1
fi

echo "→ listing deploy keys on ${REPO}"
mapfile -t ROWS < <(gh api "repos/${REPO}/keys" --jq '.[] | [.id, .title, .created_at, (.read_only|tostring), .key] | @tsv')

if [[ ${#ROWS[@]} -eq 0 ]]; then
  echo "No deploy keys on ${REPO}."
  exit 0
fi

deleted=0
for row in "${ROWS[@]}"; do
  id="${row%%$'\t'*}"
  rest="${row#*$'\t'}"
  title="${rest%%$'\t'*}"
  rest2="${rest#*$'\t'}"
  created="${rest2%%$'\t'*}"
  rest3="${rest2#*$'\t'}"
  readonly="${rest3%%$'\t'*}"
  key="${rest3#*$'\t'}"
  comment="${key##* }"
  echo "  id=${id} title=${title} created=${created} read_only=${readonly} comment=${comment}"
  if [[ "${key}" == *"${COMMENT_MARK}"* ]]; then
    echo "→ DELETE deploy key id=${id} (matched ${COMMENT_MARK})"
    gh api -X DELETE "repos/${REPO}/keys/${id}"
    deleted=$((deleted + 1))
  fi
done

if [[ "${deleted}" -eq 0 ]]; then
  echo "No deploy key matched comment ${COMMENT_MARK}. Delete the leaked key in the GitHub UI if it uses another title."
  exit 2
fi

echo "Deleted ${deleted} deploy key(s). Add the NEW host public key next — never the leaked one."
