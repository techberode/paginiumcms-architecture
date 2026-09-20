#!/usr/bin/env bash
# Point this clone at repo-managed hooks (.githooks/). Safe to re-run.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit scripts/secret-scan.sh
echo "git hooks: core.hooksPath=.githooks (pre-commit → scripts/secret-scan.sh)"
