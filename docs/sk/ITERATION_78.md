# Iterácia 78 — jednotná upload security policy

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡 · bezpečnostná brána pred novými typmi médií  
> **Vlna:** Post-HE DAM & security (It.78–79)  
> **Závisí od:** [It.24](ITERATION_24.md) DAM · [It.67](ITERATION_67.md) untrusted surfaces  
> **Blokuje:** [It.79](ITERATION_79.md) DAM video

## Cieľ

Zaviesť **jednu vynútiteľnú upload policy** pre každý endpoint, ktorý prijíma user-controlled binárky alebo archívy. Dnes `UploadSecurityValidator` a `MediaFormats` čiastočne pokrývajú médiá; avatary, backup ZIP, import rozšírení, stock import a inline upload v editore majú prekrývajúce sa, ale nekonzistentné pravidlá.

It.78 **nepridáva** nové verejné typy súborov. Zjednocuje existujúce a budúce upload cesty tak, aby video (It.79) a ďalšie DAM rozšírenia nemohli obísť brány.

---

## Upload povrchy (inventár)

| Povrch | Endpoint / cesta | Súčasná brána | Cieľ It.78 |
|--------|------------------|---------------|------------|
| Media Library | `POST /api/media/upload` | `UploadSecurityValidator` + `MediaFormats` | profil `media` |
| Inline obrázok v editore | rovnaké upload API | rovnaké | profil `media` |
| Avatar používateľa | avatar upload | čiastočné pravidlá | profil `avatar` |
| Stock import | `POST /api/media/stock-import` | SSRF + import do médií | profil `stock-import` + `OutboundUrlGuard` |
| Backup restore | upload/import | Zip-Slip + allow-list | profil `backup-archive` |
| Extension / theme ZIP | import rozšírení | `CodePolicyEngine` + Zip-Slip | profil `extension-archive` |
| Budúce video (It.79) | media upload | — | profil `media-video` (rozšírenie `media`) |

Každý riadok → **pomenovaný profil**; žiadny ad-hoc upload bez policy.

---

## Kontrakt

| Komponent | Zodpovednosť |
|-----------|--------------|
| `UploadPolicyEngine` | profil → kontroly → výsledok/audit |
| `UploadPolicyProfile` | MIME/extensions allow-list, max bytes, magic bytes, quota |
| `UploadSurfaceRegistry` | mapovanie controller/service → profile id (grep v gate) |
| `UploadMagicByteInspector` | sniffing pre obrázky, PDF, video kontajnery, archívy |
| `UploadFilenameGuard` | double extension, executable, null bytes, traversal v mene |
| `UploadQuotaGuard` | voliteľná denná kvóta bytes (flat-file counter) |
| `UploadAuditLogger` | audit bez obsahu súboru a secretov |

---

## Povinné pravidlá (všetky profily)

1. **Len allow-list** — MIME, prípona a magic bytes musia sedieť.
2. **Prienik** — `uploadSecurity.allowedMimeTypes` ∩ doménové nastavenia (napr. `media.allowedMimeTypes`).
3. **Veľkosť** — minimum z globálneho, doménového a profilového limitu.
4. **Filename hardening** — double ext, executable, `..`, absolútne cesty.
5. **Cesta len zo servera** — klient neposiela storage key.
6. **Aktívny obsah** — SVG/HTML/XML: attachment + CSP sandbox.
7. **Archívy** — Zip-Slip, allow-list entries, zip bomb guard.
8. **Outbound fetch** — `OutboundUrlGuard`, redirect revalidácia, streaming size cap.
9. **Audit** — user, profil, veľkosť, mime, výsledok cez `LogSanitizer`.
10. **Fail closed** — chýbajúci profil = reject.

---

## Nastavenia

Pozri EN špecifikáciu — existujúce `uploadSecurity.*` kľúče z It.19b + `unifiedPolicyEnabled`, `auditUploads`, `dailyQuotaBytesPerUser`.

---

## Mimo rozsahu

- Video MIME typy (It.79).
- Externá antivírus SaaS (len hook).
- CDN/transcoding.

---

## Definition of Done

- [ ] `UploadPolicyEngine` je jediný vstup pre všetky uvedené povrchy.
- [ ] `UploadSurfaceRegistry` + wiring grep v gate.
- [ ] Prienik MIME/veľkosti otestovaný a zdokumentovaný.
- [ ] Archívy a outbound import zdieľajú Zip-Slip / SSRF guardy.
- [ ] SK/EN security docs aktualizované.
- [ ] It.79 môže pridať `media-video` bez one-off validátorov.

## Súvisiace

[It.24 DAM](ITERATION_24.md) · [It.67](ITERATION_67.md) · [It.79 video](ITERATION_79.md)
