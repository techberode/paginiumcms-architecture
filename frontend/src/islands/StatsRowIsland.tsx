import React, { useEffect, useRef } from 'react';
import { sanitizePublicHtml } from '../utils/sanitizeHtml';
import type { IslandProps } from './publicIslandDefinitions';

function parseStatValue(raw: string): { target: number; prefix: string; suffix: string } | null {
  const trimmed = raw.trim();
  const match = /^([^\d]*)([\d]+(?:[.,]\d+)?)(.*)$/.exec(trimmed);
  if (match === null) {
    return null;
  }

  const prefix = match[1] ?? '';
  const numeric = (match[2] ?? '').replace(',', '.');
  const suffix = match[3] ?? '';
  const target = Number.parseFloat(numeric);
  if (!Number.isFinite(target)) {
    return null;
  }

  return { target, prefix, suffix };
}

function formatAnimatedValue(value: number, decimals: number, prefix: string, suffix: string): string {
  const fixed = decimals > 0 ? value.toFixed(decimals) : String(Math.round(value));
  const localized = fixed.replace('.', ',');
  return `${prefix}${localized}${suffix}`;
}

function decimalPlaces(raw: string): number {
  const match = /[.,](\d+)/.exec(raw);
  return match?.[1]?.length ?? 0;
}

export function StatsRowIsland({
  attrs,
  innerHtml = '',
}: {
  attrs: IslandProps;
  innerHtml?: string;
}): React.ReactElement {
  const rootRef = useRef<HTMLElement>(null);
  const safeInner = innerHtml.trim() === '' ? '' : sanitizePublicHtml(innerHtml);

  useEffect(() => {
    if (attrs.animate !== 'count-up') {
      return;
    }

    const root = rootRef.current;
    if (root === null) {
      return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const values = root.querySelectorAll<HTMLElement>('.pg-stat-value');
    if (values.length === 0) {
      return;
    }

    const run = (): void => {
      values.forEach((el) => {
        const original = el.dataset.countOriginal ?? el.textContent ?? '';
        if (el.dataset.countOriginal === undefined) {
          el.dataset.countOriginal = original;
        }

        const parsed = parseStatValue(original);
        if (parsed === null || reducedMotion) {
          el.textContent = original;
          return;
        }

        const decimals = decimalPlaces(original);
        const durationMs = 1200;
        const start = performance.now();

        const tick = (now: number): void => {
          const t = Math.min(1, (now - start) / durationMs);
          const eased = 1 - (1 - t) ** 3;
          const current = parsed.target * eased;
          el.textContent = formatAnimatedValue(current, decimals, parsed.prefix, parsed.suffix);
          if (t < 1) {
            requestAnimationFrame(tick);
          } else {
            el.textContent = original;
          }
        };

        el.textContent = formatAnimatedValue(0, decimals, parsed.prefix, parsed.suffix);
        requestAnimationFrame(tick);
      });
    };

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          run();
          observer.disconnect();
        }
      },
      { threshold: 0.25 }
    );

    observer.observe(root);
    return () => observer.disconnect();
  }, [attrs.animate, safeInner]);

  return (
    <section
      ref={rootRef}
      className="pg-island pg-island--stats-row pg-stats pg-stats--count-up pg-reveal"
      data-island="stats-row"
      data-animate={attrs.animate !== '' ? attrs.animate : undefined}
    >
      {safeInner !== '' ? (
        <div className="pg-stats__inner contents" dangerouslySetInnerHTML={{ __html: safeInner }} />
      ) : null}
    </section>
  );
}
