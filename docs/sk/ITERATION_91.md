# Iterácia 91 — Trusted HTML a externé embedy (role-based)

> **Stav:** ⏳ plánované (91a rozpracované)  
> **Priorita:** 🟡 P1  
> **Plná špecifikácia (EN):** [../en/ITERATION_91.md](../en/ITERATION_91.md)

## Cieľ

Overeným používateľom (ADMIN / SUPER_ADMIN, konfigurovateľné cez ACL) umožniť **kontrolované HTML bloky** a **externé embedy** (YouTube, Vimeo) bez otvorenia `<script>` / ľubovoľného iframe pre všetkých editorov.

Tlačidlo **„Vložiť HTML blok“** v Reacte obíde len WYSIWYG formátovanie — **nikdy** backendovú sanitizáciu.

---

## Oprávnenia

| Permission | Účel |
|------------|------|
| `content:trusted-html` | Blok `:::html-safe` |
| `content:embed-external` | Blok `:::embed` (YouTube/Vimeo) |

Master switch: `editor.trustedHtmlEnabled` (default `false`).

---

## Formáty obsahu

**Trusted HTML:**

```markdown
:::html-safe
<div class="grid-2"><p>Vlastný layout</p></div>
:::
```

**Externý embed:**

```markdown
:::embed
provider: youtube
id: dQw4w9WgXcQ
:::
```

---

## Fázy

| Fáza | Rozsah |
|------|--------|
| **91a** | Permissions, HTMLPurifier, `:::html-safe`, FE modal |
| **91b** | `:::embed` YouTube/Vimeo |
| **91c** | Tiptap + audit log |
| **91d** | Regresné testy, dokumentácia |

## Súvisiace

[It.90](ITERATION_90.md) · [It.79](ITERATION_79.md)
