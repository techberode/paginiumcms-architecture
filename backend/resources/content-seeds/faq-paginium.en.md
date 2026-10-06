---
title: PaginiumCMS FAQ
slug: faq
status: published
template: default
author: Paginium Team
createdAt: 2026-10-06T08:00:00+02:00
updatedAt: 2026-10-06T08:00:00+02:00
description: Answers about flat-file CMS, self-hosting, security, editor, and API — accordion FAQ widget.
locale: en
---

# Frequently asked questions

Quick answers about **PaginiumCMS** — what it is, how it runs without SQL, and how to operate it on your own server.

[widget type="faq" title="General" items="What is PaginiumCMS?::A hybrid flat-file content management system: React admin SPA, public site, and REST API over UTF-8 JSON/Markdown on disk — one source of truth without a SQL database. | Do I need MySQL or PostgreSQL?::No. Pages, articles, settings, redirects, and media metadata live in files. Optional index and cache layers improve listing speed but are not a separate database product. | Who is PaginiumCMS for?::Teams and individuals who want Git-friendly content, Docker self-hosting, granular admin permissions, and marketing pages built from shortcodes and widgets — not a drag-and-drop page builder lock-in. | Is PaginiumCMS open source?::Yes. You can inspect, fork, and deploy it on your infrastructure. Release notes and roadmap are tracked in the project repository."/]

[widget type="faq" title="Installation & operations" items="How do I install it?::Use Docker Compose or your own PHP 8.5 + Node build pipeline. Point the web server at the backend front controller and serve the built admin/public SPA assets — see deployment docs in the repo. | Can I use my own domain?::Yes. Configure site URL in Settings, TLS at the reverse proxy, and optional HSTS headers — same as any PHP/React stack behind Nginx or Caddy. | How do backups work?::Content and settings are files under storage — copy the tree or use the built-in backup/export tools in admin. Version control (Git) is a natural extra layer for text content. | What about performance without SQL?::Flat-file reads are fast at moderate scale; Paginium adds optional content index, HTTP cache drivers, and ETag support for public GET responses."/]

[widget type="faq" title="Editor, media & API" items="Which languages are supported in the UI?::Slovak and English for core admin and public chrome; content can be authored in any UTF-8 language. | How does the media library work?::Uploads land in allow-listed storage paths; public URLs are sanitized before render. Images, documents, and video follow unified upload policy settings. | Is there a headless API?::Yes. REST endpoints plus API keys and JWT for automation — publish, read content, and integrate outside the admin SPA. | Can I extend the CMS?::Themes and plugins pass CodePolicy scanning before install. Custom widgets and shortcodes let operators compose landing pages without shipping new PHP for every block."/]

[widget type="faq" title="Security" items="How is the admin protected?::Session auth with optional 2FA, CSRF tokens on mutating requests, rate limits on login, and role-based permissions per module action. | What about secrets and passwords?::Sensitive settings and user secrets are encrypted at rest when APP_KEY is configured; passwords use modern hashing — never stored in plaintext. | Are public pages safe from XSS?::User HTML is sanitized; media URLs are allow-listed; widgets and shortcodes expand through escaped templates on the server."/]

Need something else? Use the [contact](/contact) page or open an issue in the project repository.
