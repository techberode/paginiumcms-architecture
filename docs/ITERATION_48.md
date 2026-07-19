# Iteration 48 – PHP frontmatter templates & static/dynamic web

**Status:** ⏳ Planned  
**Version target:** TBD  
**Priority:** 🟡 — výkon + hosting na slabšom VPS bez PHP runtime pre verejný web

## Vízia

PaginiumCMS ostáva **flat-file first**. It.48 pridá:

1. **Vlastné šablóny** pre články a podstránky — **PHP + front matter**, nie Twig.
2. **Metadata formát** — YAML front matter (default) + voliteľný **JSON** alebo **INI** sidecar.
3. **Generovanie statického HTML** pri publish / on-demand rebuild.
4. **Prepínač v administrácii:** verejný web **dynamický** (SPA + API) alebo **statický** (servírované `.html` + assets).
5. **Obe režimy implementované** — admin vždy dynamický SPA; prepínač ovplyvňuje len verejný front.

## Prečo PHP, nie Twig

- Rovnaký runtime a DI ako zvyšok CMS — žiadna nová template engine závislosť.
- Šablóny sú **bezpečné PHP súbory** v allow-liste (Code Policy / SyntaxChecker pattern).
- Front matter parser (`FrontMatterParser`) už existuje — rozšíriť o JSON/INI.

## Architektúra

```
content/articles/foo.md          ← zdroj (YAML front matter + markdown body)
templates/public/article.php     ← PHP layout (dostane $meta, $html, $site)
storage/static/articles/foo.html ← vygenerovaný výstup (static mode)
```

### Režimy (`settings.site.renderMode`)

| Režim | Verejný web | Admin |
|-------|-------------|-------|
| `dynamic` | React SPA + `/api/content/*` | SPA |
| `static` | nginx servíruje `storage/static/**` | SPA (edit → trigger rebuild job) |
| `hybrid` | statické stránky + SPA pre interaktívne moduly (komentáre) | SPA |

## Backend

### Nové komponenty

| Súbor | Úloha |
|-------|--------|
| `Core/Rendering/Contracts/TemplateRendererInterface.php` | Render page/article → HTML string |
| `Core/Rendering/Services/PhpTemplateRenderer.php` | `include` šablóny v sandboxovanom scope |
| `Core/Rendering/Services/StaticSiteGenerator.php` | Batch: všetky published → HTML |
| `Core/Rendering/Services/MarkdownToHtmlPipeline.php` | reuse `MarkdownContentParser` |
| `Core/FlatFile/Services/MetadataFormatResolver.php` | `.md` YAML / `.json` / `.ini` sidecar |
| `Http/Controllers/Admin/TemplateController.php` | CRUD šablón, preview |
| `Http/Controllers/Admin/StaticBuildController.php` | POST rebuild, status |
| Settings `site.renderMode`, `site.staticOutputPath` | prepínač + cesta |

### Šablóna (príklad)

```php
<?php
/** @var array<string, mixed> $meta */
/** @var string $html */
/** @var array<string, mixed> $site */
?><!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($meta['lang'] ?? 'sk')) ?>">
<head>
  <title><?= htmlspecialchars((string) ($meta['title'] ?? '')) ?></title>
</head>
<body><?= $html ?></body>
</html>
```

### Metadata formáty

| Formát | Súbor | Poznámka |
|--------|-------|----------|
| YAML | `article.md` s `---` blokom | default, existujúci editor |
| JSON | `article.json` + `article.body.md` | strojovo čitateľné |
| INI | `article.ini` + body | legacy / jednoduché hostingy |

Validácia cez existujúci `Validator` + schema per content type.

### Job queue

- Publish / update → enqueue `static:rebuild-page` (It.29 scheduler)
- Full rebuild → `static:rebuild-all`

## Frontend (admin)

- **Nastavenia → Site → Render mode** — dynamic / static / hybrid + help text
- **Šablóny** — zoznam `templates/public/*.php`, Monaco edit (Developer Mode), preview s sample content
- **Static build** — tlačidlo „Rebuild static site“, progress z job queue
- Content editor — badge „Static HTML: fresh / stale“

## Verejný web (static mode)

- nginx `root` → `storage/static/` alebo symlink z `public/static`
- Fallback na SPA len pre `/admin`, `/api`, interaktívne routy (hybrid)
- Sitemap/RSS môžu čítať z static indexu alebo regenerovať pri build

## Testy

- PHPUnit: `PhpTemplateRendererTest`, `StaticSiteGeneratorTest`, metadata INI/JSON round-trip
- PHPUnit: publish flow → HTML file exists + obsahuje title
- Vitest: admin render mode toggle + stale badge

## Bezpečnosť

- Šablóny len v `templates/` allow-list; `PhpTemplateRenderer` bez `eval`
- Static output bez PHP executable bitov; CSP hlavičky na statických súboroch cez nginx
- Sanitized HTML z markdown pipeline (existujúce pravidlá)

## Súvisiace

- [ITERATION_29.md](ITERATION_29.md) — rebuild jobs
- [ITERATION_23.md](ITERATION_23.md) — SEO meta → inject do static `<head>`
- [ITERATION_32.md](ITERATION_BACKLOG.md) — code-splitting (dynamic mode)

## Out of scope

- Twig / Blade / Smarty
- Headless-only bez admin SPA
- CDN deploy automation (môže byť It.50+)
