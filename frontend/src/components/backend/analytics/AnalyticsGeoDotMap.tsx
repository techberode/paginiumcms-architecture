import React, { useMemo } from 'react';
import type { GeoStat } from '../../../api/analytics';
import { countryCodeToFlag } from '../../../utils/countryFlag';
import { geoMapPosition } from './analyticsGeoMapLayout';

type Props = {
  geo: GeoStat[];
  loading?: boolean;
  emptyMessage: string;
  title: string;
  hint: string;
};

export const AnalyticsGeoDotMap: React.FC<Props> = ({ geo, loading, emptyMessage, title, hint }) => {
  const dots = useMemo(() => {
    const maxVisits = Math.max(1, ...geo.map((row) => row.visits));
    const merged = new Map<string, { code: string; label: string; visits: number }>();
    for (const row of geo) {
      const code = (row.countryCode ?? '').trim().toUpperCase();
      if (!code) {
        continue;
      }
      const existing = merged.get(code);
      if (existing) {
        existing.visits += row.visits;
      } else {
        merged.set(code, { code, label: row.country, visits: row.visits });
      }
    }
    return [...merged.values()]
      .map((entry) => {
        const pos = geoMapPosition(entry.code);
        if (!pos) {
          return null;
        }
        const r = 6 + Math.round((entry.visits / maxVisits) * 14);
        return { ...entry, ...pos, r };
      })
      .filter(Boolean) as Array<{ code: string; label: string; visits: number; x: number; y: number; r: number }>;
  }, [geo]);

  if (loading) {
    return (
      <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-6 animate-pulse h-64 bg-slate-100 dark:bg-slate-900/40" />
    );
  }

  if (dots.length === 0) {
    return (
      <div className="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-6 text-sm text-slate-500">
        {emptyMessage}
      </div>
    );
  }

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
      <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-1">{title}</h3>
      <p className="text-xs text-slate-500 mb-4">{hint}</p>
      <div className="relative aspect-[2/1] rounded-xl bg-gradient-to-b from-slate-100 to-slate-200/80 dark:from-slate-900 dark:to-slate-950 overflow-hidden">
        <svg viewBox="0 0 360 180" className="absolute inset-0 w-full h-full" role="img" aria-label={title}>
          <defs>
            <pattern id="geo-grid" width="18" height="18" patternUnits="userSpaceOnUse">
              <path d="M 18 0 L 0 0 0 18" fill="none" stroke="currentColor" strokeOpacity="0.08" />
            </pattern>
          </defs>
          <rect width="360" height="180" fill="url(#geo-grid)" className="text-slate-500" />
          {dots.map((dot) => (
            <g key={dot.code}>
              <circle
                cx={dot.x}
                cy={dot.y}
                r={dot.r}
                className="fill-indigo-500/35 stroke-indigo-600 dark:stroke-indigo-400"
                strokeWidth="1.5"
              />
              <title>
                {dot.label} ({dot.code}): {dot.visits}
              </title>
            </g>
          ))}
        </svg>
      </div>
      <ul className="mt-3 flex flex-wrap gap-2 text-xs text-slate-600 dark:text-slate-300">
        {dots.slice(0, 8).map((dot) => (
          <li key={dot.code} className="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-1">
            <span aria-hidden>{countryCodeToFlag(dot.code)}</span>
            <span className="font-semibold">{dot.label}</span>
            <span className="text-slate-500">{dot.visits}</span>
          </li>
        ))}
      </ul>
    </div>
  );
};
