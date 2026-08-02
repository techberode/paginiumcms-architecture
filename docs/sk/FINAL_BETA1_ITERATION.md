# FINAL_BETA1_ITERATION — záznam dodania Public Beta 1

> **Stav dokumentu:** ✅ historický míľnik, nie aktívny plán  
> **Pôvodný cieľ:** spojiť pre-beta vlny 5f, 6 a 7 do jedného kontrolovaného release  
> **Výsledok:** Public Beta 1 bola vydaná a následne pokračovala patch sériou až po `v2.1.0-beta.23`

Tento súbor zachováva rozhodnutia a release gate, ale odstraňuje zastarané pokyny typu „ďalší krok po 2.0.55“. Aktívny plán je v [CONTINUATION.md](CONTINUATION.md) a [ROADMAP.md](ROADMAP.md).

---

## 1. Pôvodný zámer

Pred verejnou betou bolo potrebné dokončiť:

1. reprodukovateľný Docker onboarding,
2. používateľskú dokumentáciu a first-run flow,
3. jednotný quality gate,
4. cron a diagnostické postupy,
5. release notes a tester checklist,
6. bezpečnostný baseline bez otvoreného kritického blockeru.

Zásada „jedna vlna = jeden tag = zelené CI = smoke test“ zostáva platná aj po bete.

---

## 2. Dodané fázy

### Fáza A — Docker a user docs ✅

| Výstup | Stav |
|--------|------|
| `docker-compose.yml` a čistý start | ✅ |
| `scripts/first-run.sh` / bootstrap admin | ✅ |
| `docs/developer/LOCAL_SETUP.md` | ✅ |
| `docs/user/INSTALLATION.md` | ✅ |
| `docs/user/FIRST_STEPS.md` | ✅ |
| README a dokumentačný index | ✅; ďalej konsolidované v dvojjazyčnej docs |

### Fáza B — Setup wizard rozhodnutie ✅

It.25 bol správne vyňatý z Beta blockerov. `first-run.sh` a používateľská príručka poskytli minimálny onboarding. It.25 zostáva **pre-Final UX iterácia**, nie nedokončená časť Public Beta 1.

### Fáza C — Beta infra gate ✅

- iteration gate,
- `content:diagnose` a troubleshooting,
- cron dokumentácia,
- CI workflow,
- security baseline review,
- release/deploy postupy.

### Fáza D — Public Beta release ✅

- `v2.1.0-beta.1` vytvoril verejný beta míľnik,
- `beta.2` a vyššie pridávali security a funkčné opravy,
- tester a security reporting path boli zdokumentované.

---

## 3. Release sled

| Release | Význam |
|---------|--------|
| `2.0.55` | API barrel + contributing gate |
| `2.0.56` | password confirmation |
| `2.0.57` | Docker onboarding a user docs |
| `2.0.58` | Beta infra gate |
| `v2.1.0-beta.1` | Public Beta 1 |
| `beta.2`–`beta.23` | kumulatívny hardening a post-beta funkcie |

Aktuálny detail release histórie patrí do [`CHANGELOG.md`](../CHANGELOG.md), nie do tohto historického plánu.

---

## 4. Čo bolo správne odložené

Nasledujúce schopnosti neboli podmienkou prvého verejného beta tagu:

- It.25 setup wizard,
- úplný theme runtime,
- ďalšie layout builder fázy,
- Redis a Hybrid Engine vrstvy,
- server metrics agent,
- statický/Jamstack publish.

Toto rozhodnutie zabránilo tomu, aby sa beta menila na nekonečný „ešte jedna feature“ sprint.

---

## 5. Quality gate, ktorý zostáva záväzný

```bash
composer gate
cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test
```

Podľa rozsahu zmeny sa pridáva:

- API smoke/Newman,
- Docker clean-install smoke,
- scheduler/worker smoke,
- backup/restore test,
- security pack,
- nginx/deploy kontrola.

---

## 6. Poučenia z beta dodania

1. **Dokumentácia je release artefakt.** Zastarané verzie v README a roadmapách dokážu zneistiť viac než chýbajúca feature.
2. **Cron je infra funkcia, nie iba kód.** Job môže byť implementovaný a napriek tomu na hoste nebežať.
3. **Permissions sú súčasť architektúry.** Docker používateľ, skupiny a storage mode musia byť testované.
4. **HTTP 200 neznamená business success.** Scheduler outcome musí rozlišovať completed/skipped/failed.
5. **Beta tag je začiatok testovania.** Neskoršie beta opravy nepatria späť do „pred-beta“ checklistu.
6. **Setup wizard nie je náhradou dokumentácie.** Aj po It.25 musí existovať reprodukovateľný manuálny postup.

---

## 7. Zostávajúci pre-Final gate

| Oblasť | Stav |
|--------|------|
| Externé komunitné testovanie | ⏳ |
| Kritické security/ops incidenty | musia byť 0 otvorené pre GA |
| It.25 onboarding/update UX | ⏳ |
| Rozsah Hybrid Engine pred 1.0 | otvorené release rozhodnutie |
| Finálna SK/EN dokumentácia | 🚧 |
| Clean install + update + rollback smoke | ⏳ pred GA |
| Backup restore na oddelenej inštancii | ⏳ pred GA |
| Final `1.0.0` release notes | ⏳ |

---

## 8. Archívny status

Tento dokument sa už nemá používať ako zoznam „čo spraviť teraz“. Upravuje sa iba vtedy, keď je potrebné:

- opraviť historickú nepresnosť,
- pridať odkaz na post-mortem,
- zachytiť poučenie využiteľné pre ďalší release train.
