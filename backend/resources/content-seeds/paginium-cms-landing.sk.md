---
title: PaginiumCMS
slug: paginium-cms
status: published
template: landing
layoutTemplate: landing
author: Paginium Team
createdAt: 2026-08-27T09:00:00+02:00
updatedAt: 2026-08-27T09:00:00+02:00
description: Hybrid flat-file CMS — admin SPA, headless API, bezpečnosť a shortcodes pre živú prezentáciu produktu.
locale: sk
---

[showcase-hero badge="HYBRID FLAT-FILE CMS" title="Obsah pod kontrolou. Bez SQL." subtitle="PaginiumCMS spája admin SPA, REST API a verejný web nad flat-file úložiskom. Index, cache a HTTP validátory pre výkon — rovnaká filozofia ako Grav, moderný stack." terminal="curl /api/health && open /admin" cta="Prehľad platformy" href="#platform-overview" cta2="Čítať blog" href="/blog"/]

[stats-row]
[stat-item value="Flat-file" label="Zdroj pravdy"/]
[stat-item value="2FA" label="Admin ochrana"/]
[stat-item value="85+" label="Iterácií vývoja"/]
[stat-item value="SK/EN" label="Lokalizácia"/]
[/stats-row]

[section-head anchor="platform-overview" eyebrow="PLATFORM OVERVIEW" title="Prehľad platformy" subtitle="Tri vrstvy — obsah na disku, API pre integrácie a React rozhranie pre editorov aj návštevníkov."/]

[feature-grid columns="3"]
[feature-card title="Flat-file SSOT"]Stránky, články a nastavenia v JSON/Markdown. Git-friendly, zálohovateľné, bez migrácií schémy.[/feature-card]
[feature-card title="Admin SPA"]Jeden panel pre obsah, médiá, role, firewall, zálohy, plánovač a monitoring.[/feature-card]
[feature-card title="Headless API"]REST + API kľúče a JWT pre automatizáciu a integrácie mimo adminu.[/feature-card]
[/feature-grid]

[section-head anchor="hybrid-engine" eyebrow="HYBRID ENGINE" title="Výkon bez databázy" subtitle="Voliteľné vrstvy okolo súborov — ako cache v Grav, ale s jednotným adminom a Performance Guard."/]

[feature-grid columns="3"]
[feature-card title="Content index"]Rýchle listingy bez skenovania celého stromu pri každom requeste.[/feature-card]
[feature-card title="Cache & ETag"]File/memory/auto driver, 304 odpovede na verejných GET.[/feature-card]
[feature-card title="Performance Guard"]APM, Server-Timing a diagnostika pomalých requestov v produkcii.[/feature-card]
[/feature-grid]

[section-head anchor="security" eyebrow="SECURITY BASELINE" title="Bezpečnosť nie je doplnok" subtitle="Code policy, CSRF, RBAC, audit a fail-closed import pipeline pre témy a pluginy."/]

[feature-grid columns="3"]
[feature-card title="Code policy"]Untrusted ZIP (témy, pluginy) prechádza skenom pred inštaláciou — žiadna čiastočná inštalácia.[/feature-card]
[feature-card title="Auth & RBAC"]2FA, granulárne oprávnenia, rate limit na login, hash_equals na tokenoch.[/feature-card]
[feature-card title="Observability"]Access log s size_bytes, session lock metriky, bezpečnostný audit export.[/feature-card]
[/feature-grid]

[section-head anchor="tech-stack" eyebrow="TECH STACK" title="Použité technológie" subtitle="Overený stack pre self-host a Docker nasadenie na vlastnej doméne."/]

[stack-grid]
[stack-tag label="PHP 8.5"/]
[stack-tag label="Slim 4"/]
[stack-tag label="React + Vite"/]
[stack-tag label="Flat-file JSON"/]
[stack-tag label="Docker"/]
[stack-tag label="Nginx"/]
[stack-tag label="Argon2id"/]
[stack-tag label="PHPUnit"/]
[stack-tag label="TypeScript"/]
[stack-tag label="Shortcodes"/]
[stack-tag label="Webhooks"/]
[stack-tag label="Git publish"/]
[/stack-grid]

[section-head anchor="editorial" eyebrow="EDITORIAL" title="Pre tvorcov obsahu" subtitle="Nielen statické stránky — workflow, verzie, kalendár a snippet knižnica pre opakovateľné bloky."/]

[feature-grid columns="2"]
[feature-card title="Editor & workflow"]Markdown/Tiptap, zámky, verzie s diffom, redakčný kalendár, bulk operácie.[/feature-card]
[feature-card title="Marketing bez buildera"]Shortcodes pre hero, stats, cenník a CTA — živá prezentácia produktu priamo v CMS.[/feature-card]
[/feature-grid]

[testimonial quote="Konečne vidíme produkt na prvom dojme — nie len blogové články v chronológii." author="Techberode" role="Self-hosted PaginiumCMS"/]

[cta-banner title="Chcete vidieť admin?" subtitle="Prihláste sa do administrácie, upravte túto stránku alebo vytvorte vlastnú landing sekciu cez shortcodes." cta="Kontakt" href="/contact" tone="primary"/]

[alert-box tone="info"]PaginiumCMS je open-source projekt pre self-host. Táto stránka je postavená výhradne z shortcodes a layout template `landing` — bez custom PHP a bez cudzieho JavaScriptu.[/alert-box]
