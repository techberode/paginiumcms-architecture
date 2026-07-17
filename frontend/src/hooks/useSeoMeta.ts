// frontend/src/hooks/useSeoMeta.ts
import { useEffect } from 'react';
import apiClient from '../api/client';

export interface SeoMeta {
  title: string;
  description: string;
  canonical: string;
  robots: string;
  openGraph: {
    title: string;
    description: string;
    url: string;
    type: string;
    image: string;
  };
  twitter: {
    card: string;
    title: string;
    description: string;
    image: string;
  };
  jsonLd: Record<string, unknown>;
}

function upsertMeta(attr: 'name' | 'property', key: string, content: string): void {
  if (!content) {
    return;
  }

  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.content = content;
}

function upsertLink(rel: string, href: string): void {
  if (!href) {
    return;
  }

  let el = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`);
  if (!el) {
    el = document.createElement('link');
    el.rel = rel;
    document.head.appendChild(el);
  }
  el.href = href;
}

function upsertJsonLd(id: string, data: Record<string, unknown>): void {
  let el = document.getElementById(id) as HTMLScriptElement | null;
  if (!el) {
    el = document.createElement('script');
    el.id = id;
    el.type = 'application/ld+json';
    document.head.appendChild(el);
  }
  el.textContent = JSON.stringify(data);
}

export function applySeoMeta(meta: SeoMeta): void {
  document.title = meta.title;
  upsertMeta('name', 'description', meta.description);
  upsertMeta('name', 'robots', meta.robots);
  upsertLink('canonical', meta.canonical);

  upsertMeta('property', 'og:title', meta.openGraph.title);
  upsertMeta('property', 'og:description', meta.openGraph.description);
  upsertMeta('property', 'og:url', meta.openGraph.url);
  upsertMeta('property', 'og:type', meta.openGraph.type);
  upsertMeta('property', 'og:image', meta.openGraph.image);

  upsertMeta('name', 'twitter:card', meta.twitter.card);
  upsertMeta('name', 'twitter:title', meta.twitter.title);
  upsertMeta('name', 'twitter:description', meta.twitter.description);
  upsertMeta('name', 'twitter:image', meta.twitter.image);

  upsertJsonLd('paginium-json-ld', meta.jsonLd);
}

/**
 * Loads SEO meta from backend and applies it to document head.
 */
export function useSeoMeta(type: 'page' | 'article' | null, slug: string | null): void {
  useEffect(() => {
    if (!type || !slug) {
      return;
    }

    let cancelled = false;

    void (async () => {
      const res = await apiClient.get<SeoMeta>(`/api/seo/${type}/${encodeURIComponent(slug)}`);
      if (!cancelled && res.success && res.data) {
        applySeoMeta(res.data);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [type, slug]);
}
