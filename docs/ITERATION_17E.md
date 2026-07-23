# Iteration 17e – API barrel + CONTRIBUTING (Wave 5e)

> **Release:** **2.0.55**  
> **Stav:** ✅ Shipped  
> **Naväzuje na:** It.17 partial (2.0.9 barrel fix), Wave 5d (2.0.54)

---

## Cieľ

MVP pre **ZÁKON API↔FE**: dokumentovaný contributing workflow, kompletný `api/index.ts` barrel, CI lint.

---

## Delivered

| Item | Súbor |
|------|--------|
| **CONTRIBUTING.md** | `docs/developer/CONTRIBUTING.md` — checklist endpoint → api → component → API.md |
| **API barrel** | `frontend/src/api/index.ts` — 39 modulov, 16 `api.*` klientov |
| **Lint script** | `frontend/scripts/lint-api-barrel.mjs` |
| **npm script** | `npm run lint:api-barrel` |
| **CI** | `.github/workflows/ci.yml` — frontend job step |

---

## Zámerne mimo MVP (It.17 zvyšok)

- Code Editor wizard „Nový doplnok“
- Migrácia všetkých raw `apiClient.get` v komponentoch
- Full refresh `API.md` inventára

---

## Overenie

```bash
cd frontend
npm run type-check
npm run lint:api-barrel
npm test -- --run
```

---

## Súvisiace

- [ITERATION_17.md](ITERATION_17.md)
- [CONTRIBUTING.md](developer/CONTRIBUTING.md)
