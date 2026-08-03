# FINAL_BETA1_ITERATION — Public Beta 1 delivery record

> **Document status:** ✅ historical milestone, not an active plan  
> **Original goal:** combine pre-beta waves 5f, 6, and 7 into one controlled release  
> **Result:** Public Beta 1 shipped and continued through the patch series up to `v2.1.0-beta.23`

This file preserves the decisions and release gate while removing stale instructions such as “next step after 2.0.55.” The active plan is in [CONTINUATION.md](CONTINUATION.md) and [ROADMAP.md](ROADMAP.md).

---

## 1. Original intent

Before the public beta, the project needed:

1. reproducible Docker onboarding,
2. user documentation and first-run flow,
3. a unified quality gate,
4. cron and diagnostic procedures,
5. release notes and a tester checklist,
6. a security baseline with no open critical blocker.

The rule “one wave = one tag = green CI = smoke test” remains valid after the beta.

---

## 2. Delivered phases

### Phase A — Docker and user docs ✅

| Deliverable | Status |
|-------------|--------|
| `docker-compose.yml` and clean start | ✅ |
| `scripts/first-run.sh` / bootstrap admin | ✅ |
| `docs/developer/LOCAL_SETUP.md` | ✅ |
| `docs/user/INSTALLATION.md` | ✅ |
| `docs/user/FIRST_STEPS.md` | ✅ |
| README and documentation index | ✅; further consolidated in bilingual docs |

### Phase B — Setup wizard decision ✅

It.25 was correctly removed from the Beta blockers. `first-run.sh` and the user guide provided minimum onboarding. It.25 remains a **pre-Final UX iteration**, not an unfinished part of Public Beta 1.

### Phase C — Beta infrastructure gate ✅

- iteration gate,
- `content:diagnose` and troubleshooting,
- cron documentation,
- CI workflow,
- security baseline review,
- release/deployment procedures.

### Phase D — Public Beta release ✅

- `v2.1.0-beta.1` created the public beta milestone,
- `beta.2` and later added security and functional fixes,
- tester and security reporting paths were documented.

---

## 3. Release sequence

| Release | Meaning |
|---------|---------|
| `2.0.55` | API barrel + contributing gate |
| `2.0.56` | password confirmation |
| `2.0.57` | Docker onboarding and user docs |
| `2.0.58` | Beta infrastructure gate |
| `v2.1.0-beta.1` | Public Beta 1 |
| `beta.2`–`beta.23` | cumulative hardening and post-beta features |

Current release history belongs in [`CHANGELOG.md`](../../CHANGELOG.md), not in this historical plan.

---

## 4. Correctly deferred scope

The following capabilities were not requirements for the first public beta tag:

- It.25 setup wizard,
- complete theme runtime,
- later layout builder phases,
- Redis and Hybrid Engine layers,
- server metrics agent,
- static/Jamstack publishing.

This decision prevented the beta from becoming an endless “one more feature” sprint.

---

## 5. Quality gate that remains mandatory

```bash
composer gate
cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test
```

Depending on scope, add:

- API smoke/Newman,
- Docker clean-install smoke,
- scheduler/worker smoke,
- backup/restore test,
- security pack,
- nginx/deployment verification.

---

## 6. Lessons from the beta delivery

1. **Documentation is a release artifact.** Stale versions in README and roadmaps can confuse more than a missing feature.
2. **Cron is infrastructure, not only code.** A job may be implemented and still not run on the host.
3. **Permissions are part of architecture.** Docker users, groups, and storage modes must be tested.
4. **HTTP 200 is not business success.** Scheduler outcomes must distinguish completed/skipped/failed.
5. **A beta tag starts testing.** Later beta fixes do not belong back in the “pre-beta” checklist.
6. **A setup wizard does not replace documentation.** Even after It.25, a reproducible manual path must exist.

---

## 7. Remaining pre-Final gate

| Area | Status |
|------|--------|
| External community testing | ⏳ |
| Critical security/ops incidents | must be 0 open for GA |
| It.25 onboarding/update UX | ⏳ |
| Hybrid Engine scope before 1.0 | open release decision |
| Final SK/EN documentation | 🚧 |
| Clean install + update + rollback smoke | ⏳ before GA |
| Backup restoration on a separate instance | ⏳ before GA |
| Final `1.0.0` release notes | ⏳ |

---

## 8. Archive status

Do not use this document as the list of “what to do now.” Update it only to:

- correct a historical inaccuracy,
- add a post-mortem link,
- capture a lesson useful for a future release train.
