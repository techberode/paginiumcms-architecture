---
title: Release lifecycle and production gate
description: Versioning, the 21-step test gate, manual review, CI log sanitization, artifacts, tagging, deployment, upgrade, and rollback for PaginiumCMS
icon: material/package-variant-closed-check
---

# Release lifecycle and production gate

> This document is the living release contract for the **`v2.1.0-beta.*`** family and its successors. The historical `RELEASE.md` contained dozens of per-version copy-paste blocks. This revision preserves verifiable release continuity while separating the **release process** from **historical release notes**, which belong in `CHANGELOG.md`.

## 1. Purpose and scope

A release is not merely a Git tag or a successful `git push`. It is a documented decision that a specific immutable commit:

- passed mandatory automated checks,
- received manual review of the complete test log,
- has resolved or formally dispositioned anomalies,
- was built from a reproducible environment and lockfiles,
- has an artifact, checksum, and release manifest,
- has verified upgrade, backup, restore, and rollback/roll-forward procedures,
- does not expose secrets in CI logs or artifacts,
- contains truthful release notes and known limitations,
- was deployed, or is ready to deploy, to an explicitly named profile.

The contract covers:

- beta, release candidate, stable, and hotfix releases,
- the local 21-step test runner,
- GitHub Actions CI,
- GitHub Releases and distributed archives,
- production, demo, and staging deployments,
- administrator self-update mechanisms,
- release documentation and the subsequent incident/hotfix process.

Detailed nginx, Docker, and cron deployment documentation is handled in the next documentation iteration. This document defines their release contract rather than full server configuration.

## 2. Sources of truth

When sources conflict, use this order:

1. the commit identified by the release tag,
2. lockfiles, CI workflows, and test scripts in that commit,
3. the release manifest and artifact checksum,
4. `CHANGELOG.md` and the GitHub Release for the tag,
5. this process document,
6. older copy-paste blocks or historical notes.

The word `latest`, a local branch without a commit hash, or a manually modified server tree is not a release identity.

Canonical identity:

```text
repository + commit SHA + annotated tag + artifact SHA-256
```

Optionally:

```text
SBOM digest + provenance/signature identity
```

## 3. Release types

| Type | Example | Purpose | Minimum gate |
|---|---|---|---|
| development snapshot | no public tag | internal branch validation | relevant local tests |
| beta | `v2.1.0-beta.24` | publicly testable functional build | full gate + manual review + CI |
| release candidate | `v2.1.0-rc.1` | candidate with no planned feature changes | beta gate + upgrade/rollback acceptance |
| stable release | `v2.1.0` | supported production release | all release evidence and approved deployment |
| hotfix | `v2.1.1` or beta patch | narrow urgent fix | relevant regression + full mandatory gate |
| security release | according to SemVer impact | vulnerability remediation | coordinated security gate and advisory process |

Beta does not permit authorization/CSRF bypasses, SSOT corruption, secret disclosure, or a non-reproducible upgrade. A beta may have incomplete capabilities, not ignored critical invariants.

## 4. Versioning and continuity

PaginiumCMS uses SemVer syntax with a prerelease suffix. The current beta family is:

```text
v2.1.0-beta.N
```

Rules:

- `N` increases monotonically and a tag is never recycled,
- the tag is annotated and targets the approved release commit,
- an existing published tag is never moved with force push,
- the application, health API, artifact, release manifest, and GitHub Release must report the same version,
- a functional change after publication creates a new tag rather than replacing an old artifact,
- a stable hotfix increments patch; a beta hotfix increments the prerelease number unless a new minor line is explicitly chosen.

Before tagging:

```bash
git status --short
git rev-parse HEAD
git log -1 --show-signature --format=fuller
git fetch origin --tags --prune
git tag --list 'v2.1.0-beta.*' --sort=version:refname | tail
```

## 5. Roles and separation of responsibility

Even when one person maintains the project, the process distinguishes logical roles:

| Role | Responsibility |
|---|---|
| change author | implementation, tests, migrations, and documentation |
| gate owner | execute automated checks and preserve their output |
| log reviewer | manually inspect warnings, skips, and anomalies |
| release owner | final decision, version, tag, and release notes |
| deployment owner | backup, deployment, smoke, and rollback readiness |
| security reviewer | audits, secrets, advisories, and risk disposition |

One person may perform all roles, but the decisions must not collapse into “the script was green.” Manual review is separate evidence.

## 6. Candidate state and decision model

Every automated step and the overall candidate use extended states:

| State | Meaning |
|---|---|
| `PASS` | the step passed and output contains no material anomaly |
| `PASS_WITH_REVIEW` | successful exit, but output requires documented assessment |
| `INVESTIGATION_REQUIRED` | result cannot be accepted without investigation |
| `FAILED` | a mandatory step failed or a release invariant was violated |
| `SKIPPED_EXPECTED` | approved skip with reason and owner |
| `SKIPPED_UNEXPLAINED` | unacceptable skip without explanation |
| `NOT_APPLICABLE` | the step does not apply and the reason is recorded |

The release can be `READY` only when:

- no `FAILED` state exists,
- no `INVESTIGATION_REQUIRED` remains open,
- every `PASS_WITH_REVIEW` has a disposition,
- every skip is explained,
- local and CI results are comparable,
- the release owner signs the final checklist.

## 7. Prerelease freeze and entry criteria

A release candidate commit is created before the final gate. During freeze, only the following are accepted:

- fixes for gate findings,
- tests proving the fix,
- release documentation,
- required version metadata,
- safe CI/reporting changes.

A feature is ready for the release gate when it has:

- an implementation and security contract,
- backend authorization for every mutation,
- validation, audit, and recovery behavior,
- relevant unit/integration/frontend tests,
- documented upgrade/migration impact,
- user and technical documentation,
- known limitations.

## 8. Local 21-step test gate

The observed runner on **2026-08-02 16:18** contained exactly 21 steps. The number may change as the project grows; names and results in the concrete release tag are authoritative.

| # | Category |
|---:|---|
| 1 | PHPUnit backend tests |
| 2 | PHPStan Level 8 |
| 3 | Composer Audit |
| 4 | Vitest frontend functional tests |
| 5 | frontend security tests |
| 6 | TypeScript `tsc --noEmit` |
| 7 | ESLint |
| 8 | Vitest MSW handlers |
| 9 | production build and API URL verification |
| 10 | NPM Audit |
| 11 | content diagnose |
| 12 | security regression pack |
| 13 | at-rest encryption pack |
| 14 | log-injection and SSRF guard pack |
| 15 | Path ACL pack |
| 16 | WAF POST body pack |
| 17 | UserRepository index pack |
| 18 | OTP rate-limit pack |
| 19 | CodePolicy pack |
| 20 | XSS/ZIP/headers pack |
| 21 | outbound security static grep |

The runner must:

- preserve every mandatory step exit code,
- report duration, status, and concise metrics,
- avoid presenting a step with findings as unconditional `PASS`,
- report the concrete path and error description,
- perform controlled cleanup of test artifacts,
- export the complete local log outside the project for manual review.

### Current observed snapshot

The 2026-08-02 control log reported:

```text
PHPUnit: 972 passed, 0 failed, 0 errors, 15 skipped
PHPStan Level 8: 1 error
Composer Audit: no advisories
Vitest: 93 files / 285 tests passed
Frontend security: 3 files / 17 tests passed
Content diagnose: index 522, pages 519, orphans 0, unreadable 0
NPM Audit: 3 high severity vulnerabilities although the command used a critical threshold
```

This is evidence for one run, not a permanent test-count baseline. Its candidate is not `READY` until the PHPStan error and manual disposition of the remaining anomalies are closed.

## 9. Manual review of the complete log

The complete local log is stored in a directory fully outside the project and Git repository. The reviewer does not inspect only lines labelled `Failed` or `Error`.

Mandatory review areas:

- new warnings and deprecations,
- changed test or assertion counts,
- new or unexplained `Skipped` results,
- audit findings hidden behind a successful exit code,
- real network requests during isolated tests,
- `ECONNREFUSED`, `AbortError`, `AggregateError`, socket errors, or unhandled rejections,
- production bundle size and build warnings,
- differences between local and CI results,
- cleanup before/after state and remaining test artifacts,
- sensitive values in output,
- timing or performance regressions,
- non-zero exits captured by wrappers without correct classification.

Every anomaly receives:

```text
ID / source step / severity / owner / decision / closure evidence
```

Possible decisions:

- fixed before release,
- false positive with evidence,
- expected environmental behavior,
- accepted risk with rationale, expiry, and follow-up issue,
- release blocker.

## 10. Skipped tests

A skip is not automatically an error, but it must be explained. The release report identifies:

- test suite and name,
- reason,
- environmental dependency,
- whether another test covers the contract,
- owner and removal plan when temporary.

Recommended output:

```bash
./vendor/bin/phpunit --display-skipped
```

Critical areas such as backup/restore, authentication, storage, or security policy must not be broadly skipped without an explicit release decision.

## 11. Dependency audit policy

Audit exit code and finding severity are separate dimensions. For example:

```bash
npm audit --audit-level=critical
```

may exit successfully while `high` findings exist. The summary must therefore include counts by severity and manual-review state.

Every finding receives a disposition:

- fixed by a safe update,
- unreachable in the used mode with evidence,
- mitigated by configuration or disabled functionality,
- temporarily accepted with a deadline,
- release blocker.

`npm audit fix --force` is not run without reviewing breaking changes, the lockfile diff, and the complete test gate.

## 12. GitHub Actions CI

CI is independent clean-run evidence and must not merely reproduce a developer workspace. It uses:

- a fresh checkout of the release commit,
- `composer install` and `npm ci` from lockfiles,
- an explicit runtime matrix,
- isolated test storage,
- no local `.env`,
- deterministic bootstrap,
- equivalent mandatory gate categories,
- branch protection for mandatory jobs.

The local log and GitHub CI are separate evidence:

```text
complete local log outside the project
+ independent GitHub CI run
= release review evidence
```

Differences between them must not be silently ignored.

## 13. Sensitive-data protection in CI logs

Primary rule:

```text
Sensitive values must not reach STDOUT or STDERR.
```

Tests must not print complete responses containing:

- a TOTP/2FA secret,
- QR Base64 payload,
- `otpauth://` provisioning URI,
- OTP/TOTP code,
- passwords and confirmations,
- API, access, refresh, or reset tokens,
- session IDs, cookies, or `Authorization` headers,
- private keys or provider credentials.

Implementation has three layers:

1. a shared `SensitiveDataRedactor` in test/support code,
2. raw CI output captured in `$RUNNER_TEMP` and only a sanitized log published,
3. GitHub `::add-mask::` for dynamically generated long values as defense in depth.

Raw CI output must not be passed through `tee` or uploaded as an artifact.

Recommended workflow pattern:

```yaml
- name: Run full release gate safely
  shell: bash
  run: |
    set +e
    ./scripts/run-all-tests.zsh > "$RUNNER_TEMP/alltests.raw.log" 2>&1
    gate_exit=$?
    set -e

    python3 .github/scripts/sanitize-ci-log.py \
      "$RUNNER_TEMP/alltests.raw.log" \
      > "$RUNNER_TEMP/alltests.safe.log"

    .github/scripts/verify-ci-log-redaction.sh \
      "$RUNNER_TEMP/alltests.safe.log"

    cat "$RUNNER_TEMP/alltests.safe.log"
    exit "$gate_exit"
```

Sanitization is fail-closed. If `otpauth://`, a secret JSON field, bearer token, or similar pattern remains, the job fails.

Only the sanitized report may be uploaded, with a short retention period. Debug trace through `set -x`, `bash -x`, `ACTIONS_STEP_DEBUG`, or `ACTIONS_RUNNER_DEBUG` is not used when secrets are processed.

## 14. Release evidence bundle

Each release candidate creates an evidence directory such as:

```text
release-evidence/v2.1.0-beta.24/
├── manifest.json
├── checksums.sha256
├── test-summary.json
├── manual-review.md
├── dependency-audit.json
├── artifact-inventory.txt
├── upgrade-report.md
├── rollback-report.md
└── sanitized-ci-log.txt
```

This directory need not be part of the distributed package. It may be stored as a protected release artifact or internal record according to sensitivity.

Minimum manifest:

```json
{
  "version": "2.1.0-beta.24",
  "tag": "v2.1.0-beta.24",
  "commit": "FULL_COMMIT_SHA",
  "builtAt": "2026-08-02T16:18:20Z",
  "artifact": "paginiumcms-2.1.0-beta.24.zip",
  "sha256": "...",
  "gate": "READY",
  "manualReview": "approved",
  "upgradeFrom": ["v2.1.0-beta.23"]
}
```

## 15. Building the release artifact

The artifact is produced from a clean checkout of the tagged commit, not from a dirty developer workspace.

Recommended procedure:

```bash
git clone --no-local <repository> release-build
cd release-build
git checkout --detach <full-commit-sha>
composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative
cd frontend
npm ci
npm run build:prod
cd ..
```

The tag-specific release script remains authoritative for exact build commands.

The artifact must not contain:

- `.git/` and `.github/` according to distribution policy,
- `.env` or local secrets,
- test logs and raw CI logs,
- non-runtime test fixtures,
- `node_modules`, caches, or temporary files,
- user content, backups, or uploads,
- editor swap files and IDE metadata,
- private security incident logs.

It must contain everything required for supported first run, or clearly declare external build/runtime dependencies.

## 16. Checksum, SBOM, and provenance

Minimum integrity:

```bash
sha256sum paginiumcms-2.1.0-beta.24.zip \
  > paginiumcms-2.1.0-beta.24.zip.sha256
sha256sum -c paginiumcms-2.1.0-beta.24.zip.sha256
```

Recommended extension:

- CycloneDX or SPDX SBOM,
- artifact signature or attestation,
- container image digest,
- build provenance tied to commit and workflow run,
- third-party license inventory.

A checksum detects unintended modification but does not independently prove origin when checksum and artifact come from the same compromised channel.

## 17. Changelog and release notes

`CHANGELOG.md` is the historical record. A GitHub Release is the readable distribution record for a tag. This document defines process.

Release notes include:

- concise summary and audience,
- Added / Changed / Fixed / Security,
- breaking changes and migrations,
- configuration or environment changes,
- known limitations,
- upgrade and rollback notes,
- sanitized test summary,
- links to relevant issues and documentation,
- checksum and optionally SBOM.

They must not claim “enterprise-ready” merely because a gate passed.

## 18. Release commit and annotated tag

Before tagging:

```bash
git status --porcelain
# expected: empty

git rev-parse --verify HEAD
git diff --exit-code
git diff --cached --exit-code
```

Tag:

```bash
git tag -a v2.1.0-beta.24 \
  -m "v2.1.0-beta.24 — concise release title" \
  <full-commit-sha>
git show v2.1.0-beta.24 --no-patch
git push origin v2.1.0-beta.24
```

If a tag targets the wrong commit and has not been published, fix it before push. A published tag is never moved; create a new prerelease patch and document the incident.

## 19. GitHub Release

A GitHub Release is created only after pushing the tag and verifying that:

- CI ran on the same commit SHA,
- artifact checksum matches,
- release notes link to correct anchors,
- artifacts contain no raw logs or secrets,
- prerelease/latest flags match release type.

GitHub CLI example:

```bash
gh release create v2.1.0-beta.24 \
  paginiumcms-2.1.0-beta.24.zip \
  paginiumcms-2.1.0-beta.24.zip.sha256 \
  --prerelease \
  --title "v2.1.0-beta.24 — release title" \
  --notes-file release-notes.md
```

Automation must not publish when the evidence manifest is not `READY`.

## 20. Predeployment backup

Before production or demo deployment, create and verify a backup of authoritative data and recovery-critical configuration:

- flat-file content and metadata,
- user and security data,
- settings and encrypted secrets,
- media metadata and binary objects according to storage profile,
- `APP_KEY`/encryption key under safe key-management policy,
- relevant deployment environment and compose/nginx versions without exposing secrets.

A backup without a restore test is hope wrapped in a ZIP file. At minimum, verify hash and readability; for material storage changes, restore into a disposable environment.

## 21. Upgrade acceptance

Upgrade testing runs from every declared supported previous version or an explicitly selected minimum baseline.

It verifies:

1. backup of the previous state,
2. deployment of the new artifact/tag,
3. schema/content migration or lazy migration,
4. index/cache rebuild from SSOT,
5. login, 2FA, RBAC, and ACL,
6. read/write of existing content,
7. media and public routes,
8. schedulers/workers,
9. audit/logging,
10. health/version endpoint,
11. preservation of encrypted secrets,
12. idempotence of repeated migration execution.

An upgrade is not tested only against an empty fresh install.

## 22. Deployment and smoke

A deployment explicitly records:

- environment,
- previous and target version,
- commit/tag,
- backup ID,
- deployment command/workflow run,
- owner,
- start/end time,
- smoke-test result.

Minimum smoke:

```text
health/version
public read
admin login + 2FA according to policy
CSRF-protected mutation
content create/update with revision
media read/upload according to profile
settings read
logs/audit
scheduler/worker health
```

Feature-specific smoke is added from release scope. The deployment script fails when the health endpoint reports an unexpected version.

## 23. Rollback and roll-forward

A rollback plan exists before deployment. It distinguishes:

- code rollback to a previous immutable tag,
- data restore from a snapshot,
- roll-forward with a fix compatible with already changed data.

For an irreversible migration, “git checkout the old tag” is not a valid rollback. Release notes must say that recovery requires restore or roll-forward.

Rollback triggers include:

- health failure,
- login/authz/CSRF regression,
- SSOT corruption or loss,
- unreadable existing content,
- failed scheduler/worker for a critical flow,
- significant secret/log disclosure,
- unacceptable error rate or performance regression.

After rollback, execute smoke, consistency audit, and incident recording.

## 24. Hotfix process

A hotfix does not begin with editing the production server directly. Procedure:

1. reproduce the issue or document the emergency condition,
2. create a fix from the current supported tag/branch,
3. add a regression test,
4. run the relevant fast gate,
5. run all mandatory release steps,
6. manually review the log,
7. create a new immutable tag,
8. deploy, smoke, and monitor,
9. merge/cherry-pick back to the main development branch,
10. update `ISSUES.md`, `CHANGELOG.md`, and security records as needed.

Urgency may reduce optional tests, not silently disable auth, storage, or security invariants.

## 25. Security releases and vulnerabilities

For a security finding, first determine whether coordinated private remediation is required. A public issue must not disclose exploit details or secrets before a fix.

A security release includes:

- affected versions,
- fixed version,
- severity and exploitation conditions at an appropriate level,
- mitigations,
- upgrade urgency,
- credential rotation when exposure is possible,
- a regression test proving the fix,
- advisory/CVE/GHSA link when available.

If a secret appeared in a GitHub CI log:

1. delete workflow-run logs,
2. rotate or revoke the value,
3. inspect artifacts,
4. fix the output source and sanitizer,
5. rerun CI.

## 26. Release retention and evidence

Retain:

- tag and commit,
- GitHub Release,
- checksum and manifest,
- release notes,
- sanitized test summary,
- manual release decision,
- upgrade/rollback evidence,
- SBOM/signature according to policy.

The raw local diagnostic log remains outside the project and follows separate retention/access rules. Raw CI output containing secrets is not retained as an artifact.

## 27. Reusable checklist

### Identity

- [ ] version is monotonic and consistent,
- [ ] release commit is immutable and workspace clean,
- [ ] annotated tag targets the exact commit,
- [ ] app/API/manifest/artifact report the same version.

### Quality and security gate

- [ ] all current 21 steps or successors ran,
- [ ] mandatory steps have successful exits,
- [ ] complete-log manual review is complete,
- [ ] skipped tests have reasons,
- [ ] dependency findings have dispositions,
- [ ] independent CI is green,
- [ ] CI logs and artifacts contain no secrets.

### Artifact

- [ ] build came from a clean checkout,
- [ ] inventory contains no `.env`, content, backups, or raw logs,
- [ ] SHA-256 was generated and verified,
- [ ] manifest is `READY`,
- [ ] SBOM/provenance was generated according to policy.

### Operations

- [ ] verified backup exists,
- [ ] upgrade test passed,
- [ ] rollback/roll-forward is executable,
- [ ] deployment owner and target are named,
- [ ] smoke test passed,
- [ ] post-deployment monitoring shows no critical regression.

### Documentation

- [ ] `CHANGELOG.md` is updated,
- [ ] GitHub Release is truthful and complete,
- [ ] upgrade/configuration changes are documented,
- [ ] known limitations are published,
- [ ] relevant issues link to the fix/release.

## 28. Release decision record

Recommended template:

```markdown
# Release decision — v2.1.0-beta.24

- Commit: `...`
- Artifact SHA-256: `...`
- Local gate: `PASS_WITH_REVIEW`
- GitHub CI: `PASS`
- Manual log review: `APPROVED`
- Dependency findings: `documented`
- Upgrade baseline: `v2.1.0-beta.23`
- Backup/restore: `verified`
- Rollback: `verified`
- Decision: `READY`
- Approved by: `...`
- Date: `...`
```

## 29. Historical release continuity

The following index was derived from the original `docs/developer/RELEASE.md`. It preserves version identifiers and historical section titles. Detailed Added/Changed/Fixed history will be consolidated in the bilingual `CHANGELOG.md` during Iteration 14.

| Version | Original section title | Documentation status |
|---|---|---|
| `v2.1.0-beta.23` | It.58c Layout Switch + LayoutPreviewFrame | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.22` | It.66 security write-time gate + It.65 Phase 3 | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.21` | It.65 Feature gallery Phase 2 + SEO/logging | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.20` | It.65 Feature gallery Phase 1 + footer UX | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.19` | It.64 Footer social + Analytics SPA beacon | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.18` | It.61 Phase 5 + It.63 v2/v3 | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.17` | Newsletter Phase 4 + footer modal + cookie consent | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.16` | Newsletter v2 (It.61 Phases 1–3) + BE↔FE wiring | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.15` | Version check UX + security audit fixes (It.63 v2) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.14` | Docker admin deploy bootstrap (It.63) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.13` | AppRoot hotfix + system update UX (It.63) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.12` | Admin system update MVP (It.63) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.11` | Demo security polish (It.13 v4) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.10` | Demo sandbox full trial (It.13 v3) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.9` | Scheduler, newsletter, demo deploy (It.62 + It.61 + ISS-098) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.8` | It.58b color schemes (ISS-093) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.7` | Deps, Vitest, It.58 doc (ISS-089–092) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.6` | Security audit (ISS-086–088) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.5` | It.56 Rich navigation + session fix | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.4` | It.57 Auto tags & meta description | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.3` | Beta 1 patch (React Router GHSA + CMS info) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.2` | Beta 1 Testing (pre-push security gate) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `v2.1.0-beta.1` | Public Beta 1 (Wave 7) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.58` | Wave 6 Beta infra gate | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.57` | Wave 5f Docker onboarding + user docs | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.56` | Password confirmation (register + admin users) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.55` | Wave 5e API barrel + CONTRIBUTING (It.17 MVP) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.54` | Wave 5d hook emitters + extension policy (It.15d) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.53` | It.59 scheduled publish (editor + cron) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.52` | branding, ACL v nastaveniach, CI (ISS-072–074) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.51` | ops hotfix + maintenance + logs (ISS-063–071) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.50` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.49` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.48` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.47` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.46` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.45` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.44` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.43` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.42` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.41` | pred release kontrola (legacy commit label) | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.40` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.39` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.38` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.37` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.34` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.32` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.31` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |
| `2.0.30` | pred release kontrola | historical record in the original `RELEASE.md`; detail will be consolidated in `CHANGELOG.md` |

## 30. Definition of Done

The release lifecycle is implemented when:

- the runner reports every mandatory step and exit code,
- the current 21-step count is recorded as a snapshot rather than a permanent constant,
- the complete local log is stored outside the project and manually reviewed,
- GitHub CI performs an independent clean run,
- tests do not print TOTP, QR, tokens, or credentials,
- CI publishes only a sanitized log and redaction is fail-closed,
- dependency-audit findings have explicit disposition,
- skipped tests have reasons,
- the artifact is built from a clean checkout and has checksum/manifest,
- the tag is immutable,
- upgrade, backup, restore, and rollback are verified,
- release notes and changelog are consistent,
- `READY` is a separate, auditable decision record.

## Related documents

- [Testing and quality gates](TESTING.md)
- [Development security architecture](SECURITY.md)
- [Beta infrastructure and release readiness](BETA_INFRA.md)
- [Local development environment](LOCAL_SETUP.md)
- [Content versioning architecture](../architecture/VERSIONING.md)
- [Deployment modes](../architecture/DEPLOYMENT_MODES.md)
- [Incident register](../ISSUES.md)
- [Changelog](../../../CHANGELOG.md)
