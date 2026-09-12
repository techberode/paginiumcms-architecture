# PaginiumCMS — kontext pre pokračovanie vývoja

> **Účel:** stručný handoff pre ďalšiu reláciu  
> **Checkpoint:** 12. september 2026 · **`v2.1.0-beta.71`**  
> **Aktívna fáza:** **plný vývoj naplánovaných iterácií** — stabilizačný freeze zrušený

Anglický master: [en/CONTINUATION.md](../en/CONTINUATION.md)

---

## Rozhodnutie (9. 9. 2026)

Stabilizačná fáza je **ukončená**. Pokračujeme v existujúcich špeckách; `./scripts/iteration-gate.sh` ostáva povinný.

## Stav (september 2026)

| Oblasť | Stav |
|--------|------|
| Najnovší tag | ✅ `v2.1.0-beta.71` — It.79 DAM video, stack bootstrap |
| Rozpracované | **It.91a** trusted HTML · **It.90** Editor Workbench (špec) |

## Fronta

1. **It.91** Trusted HTML + externé embedy (YouTube/Vimeo)  
2. **It.90** Editor Workbench (toolbar + wizardy)  
3. **It.72** S3 remainder  
4. **It.89** plugin capabilities  
5. **It.58f/g** layout · **It.70** · **It.76/77** · **It.75** · **It.48**

**Aktívny slice:** **It.91a** — permissions, `:::html-safe`, HTMLPurifier, FE modal.

## Dokumentácia

| Dokument | Obsah |
|----------|--------|
| [ITERATION_91.md](ITERATION_91.md) | Trusted HTML & embedy |
| [ITERATION_90.md](../ITERATION_90.md) | Editor Workbench |
| [ITERATION_79.md](../ITERATION_79.md) | DAM video (hotové) |
