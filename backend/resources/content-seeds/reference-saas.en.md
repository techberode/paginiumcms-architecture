---
title: Ledgerflow
slug: reference-saas
status: published
template: landing
layoutTemplate: landing
author: Paginium Team
createdAt: 2026-10-06T10:00:00+02:00
updatedAt: 2026-10-06T10:00:00+02:00
description: Reference landing — B2B SaaS pricing-led layout (Experience Phase C).
locale: en
---

[showcase-hero badge="B2B SAAS" title="Close the books without closing your stack" subtitle="Ledgerflow connects flat-file audit trails to the admin your finance team already uses — API keys, webhooks, and role gates included." terminal="curl -s /api/health | jq .version" cta="See pricing" href="#pricing" cta2="Read docs" href2="/about"/]

[stats-row animate="count-up"]
[stat-item value="99.9%" label="API uptime target"/]
[stat-item value="2FA" label="Admin default"/]
[stat-item value="JWT" label="Headless clients"/]
[stat-item value="ETag" label="Cache-friendly GET"/]
[/stats-row]

[section-head anchor="pricing" eyebrow="PLANS" title="Simple tiers" subtitle="Toggle billing in the public pricing island — numbers stay in Markdown, not hard-coded React."/]

[pricing-table columns="3" billing-toggle="monthly-yearly" label-monthly="Monthly" label-yearly="Yearly"]
[pricing-plan name="Starter" price-monthly="€0" price-yearly="€0" period-monthly="/mo" period-yearly="/yr" cta="Self-host" href="/contact" variant="default"]
[pricing-feature text="Flat-file SSOT"/]
[pricing-feature text="Admin + public SPA"/]
[/pricing-plan]
[pricing-plan name="Team" price-monthly="€49" price-yearly="€490" period-monthly="/mo" period-yearly="/yr" cta="Book demo" href="/contact" variant="featured"]
[pricing-feature text="Editorial workflow"/]
[pricing-feature text="Redis cache optional"/]
[pricing-feature text="Git publish"/]
[/pricing-plan]
[pricing-plan name="Scale" price-monthly="€129" price-yearly="€1290" period-monthly="/mo" period-yearly="/yr" cta="Talk to us" href="/contact" variant="default"]
[pricing-feature text="S3 media driver"/]
[pricing-feature text="API keys + scopes"/]
[/pricing-plan]
[/pricing-table]

[section-band reveal="scale-in" hover-effect="glow"]
[feature-grid columns="2"]
[feature-card title="Audit-ready"]Security baseline, exportable logs, and permissioned mutating routes.[/feature-card]
[feature-card title="Composer-friendly"]Landing sections as shortcodes — islands hydrate pricing and galleries on the public site.[/feature-card]
[/feature-grid]
[/section-band]

[cta-banner title="Ship your SaaS landing today" subtitle="Start from this seed or the SaaS starter pack in Outline, then replace product copy." cta="Get started" href="/contact" tone="primary"/]
