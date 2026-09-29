import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { GeoStat } from '../../../api/analytics';
import { countryCodeToFlag } from '../../../utils/countryFlag';
import { useI18n } from '../../../context/I18nContext';
import { geoMapPosition } from './analyticsGeoMapLayout';

type Props = {
  geo: GeoStat[];
  loading?: boolean;
  emptyMessage: string;
  title: string;
  hint: string;
};

type MapDot = {
  code: string;
  label: string;
  visits: number;
  city?: string | null;
  sampleIps: string[];
  x: number;
  y: number;
  r: number;
};

const MIN_SCALE = 1;
const MAX_SCALE = 5;

export const AnalyticsGeoDotMap: React.FC<Props> = ({ geo, loading, emptyMessage, title, hint }) => {
  const { t } = useI18n();
  const svgRef = useRef<SVGSVGElement>(null);
  const [landPath, setLandPath] = useState<string | null>(null);
  const [scale, setScale] = useState(1);
  const [pan, setPan] = useState({ x: 0, y: 0 });
  const [drag, setDrag] = useState<{ startX: number; startY: number; panX: number; panY: number } | null>(null);
  const [hovered, setHovered] = useState<MapDot | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  useEffect(() => {
    let cancelled = false;
    void import('./analyticsGeoWorldLandPath').then((mod) => {
      if (!cancelled) {
        setLandPath(mod.ANALYTICS_GEO_WORLD_LAND_PATH);
      }
    });
    return () => {
      cancelled = true;
    };
  }, []);

  const dots = useMemo(() => {
    const maxVisits = Math.max(1, ...geo.map((row) => row.visits));
    const merged = new Map<string, MapDot>();

    for (const row of geo) {
      const code = (row.countryCode ?? '').trim().toUpperCase();
      if (!code) {
        continue;
      }
      const pos = geoMapPosition(code, row.latitude, row.longitude);
      if (!pos) {
        continue;
      }

      const existing = merged.get(code);
      if (existing) {
        existing.visits += row.visits;
        if ((row.city ?? '') !== '' && !existing.city) {
          existing.city = row.city;
        }
        for (const ip of row.sample_ips ?? []) {
          if (existing.sampleIps.length < 3 && !existing.sampleIps.includes(ip)) {
            existing.sampleIps.push(ip);
          }
        }
      } else {
        merged.set(code, {
          code,
          label: row.country,
          visits: row.visits,
          city: row.city,
          sampleIps: [...(row.sample_ips ?? [])].slice(0, 3),
          ...pos,
          r: 0,
        });
      }
    }

    return [...merged.values()].map((entry) => ({
      ...entry,
      r: 5 + Math.round((entry.visits / maxVisits) * 16),
    }));
  }, [geo]);

  const resetView = useCallback(() => {
    setScale(1);
    setPan({ x: 0, y: 0 });
  }, []);

  const onWheel = useCallback((event: React.WheelEvent<SVGSVGElement>) => {
    event.preventDefault();
    const delta = event.deltaY > 0 ? 0.9 : 1.1;
    setScale((prev) => {
      const next = Math.min(MAX_SCALE, Math.max(MIN_SCALE, prev * delta));
      if (next <= MIN_SCALE) {
        setPan({ x: 0, y: 0 });
      }
      return next;
    });
  }, []);

  const onPointerDown = useCallback(
    (event: React.PointerEvent<SVGSVGElement>) => {
      if (scale <= MIN_SCALE) {
        return;
      }
      event.currentTarget.setPointerCapture(event.pointerId);
      setDrag({ startX: event.clientX, startY: event.clientY, panX: pan.x, panY: pan.y });
    },
    [pan.x, pan.y, scale]
  );

  const onPointerMove = useCallback(
    (event: React.PointerEvent<SVGSVGElement>) => {
      if (drag) {
        const dx = (event.clientX - drag.startX) / scale;
        const dy = (event.clientY - drag.startY) / scale;
        setPan({ x: drag.panX + dx, y: drag.panY + dy });
      }
    },
    [drag, scale]
  );

  const onPointerUp = useCallback((event: React.PointerEvent<SVGSVGElement>) => {
    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }
    setDrag(null);
  }, []);

  const showTooltip = useCallback((dot: MapDot, event: React.MouseEvent<SVGCircleElement>) => {
    const rect = svgRef.current?.getBoundingClientRect();
    if (!rect) {
      return;
    }
    setHovered(dot);
    setTooltipPos({
      x: event.clientX - rect.left,
      y: event.clientY - rect.top,
    });
  }, []);

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

  const transform = `translate(${180 + pan.x}, ${90 + pan.y}) scale(${scale}) translate(-180, -90)`;

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
      <div className="flex flex-wrap items-start justify-between gap-2 mb-1">
        <h3 className="text-sm font-black uppercase tracking-wider text-slate-500">{title}</h3>
        <div className="flex items-center gap-1">
          <button
            type="button"
            className="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
            onClick={() => setScale((s) => Math.min(MAX_SCALE, s * 1.25))}
            aria-label={t('analytics.geo.zoomIn')}
          >
            +
          </button>
          <button
            type="button"
            className="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
            onClick={() => setScale((s) => Math.max(MIN_SCALE, s / 1.25))}
            aria-label={t('analytics.geo.zoomOut')}
          >
            −
          </button>
          <button
            type="button"
            className="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
            onClick={resetView}
          >
            {t('analytics.geo.resetView')}
          </button>
        </div>
      </div>
      <p className="text-xs text-slate-500 mb-4">{hint}</p>

      <div className="relative aspect-[2/1] rounded-xl bg-gradient-to-b from-sky-100/80 to-sky-200/40 dark:from-slate-900 dark:to-slate-950 overflow-hidden touch-none">
        <svg
          ref={svgRef}
          viewBox="0 0 360 180"
          className="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing"
          role="img"
          aria-label={title}
          onWheel={onWheel}
          onPointerDown={onPointerDown}
          onPointerMove={onPointerMove}
          onPointerUp={onPointerUp}
          onPointerLeave={onPointerUp}
        >
          <defs>
            <pattern id="geo-grid" width="18" height="18" patternUnits="userSpaceOnUse">
              <path d="M 18 0 L 0 0 0 18" fill="none" stroke="currentColor" strokeOpacity="0.06" />
            </pattern>
          </defs>
          <g transform={transform}>
            <rect width="360" height="180" fill="url(#geo-grid)" className="text-slate-500" />
            {landPath ? (
              <path
                d={landPath}
                className="fill-slate-200/90 stroke-slate-400/70 dark:fill-slate-800/90 dark:stroke-slate-600/80"
                strokeWidth="0.35"
                vectorEffect="non-scaling-stroke"
              />
            ) : null}
            {dots.map((dot) => (
              <g key={dot.code}>
                <circle
                  cx={dot.x}
                  cy={dot.y}
                  r={dot.r + 4}
                  className="fill-transparent"
                  onMouseEnter={(e) => showTooltip(dot, e)}
                  onMouseMove={(e) => showTooltip(dot, e)}
                  onMouseLeave={() => setHovered(null)}
                  onFocus={(e) => showTooltip(dot, e as unknown as React.MouseEvent<SVGCircleElement>)}
                  onBlur={() => setHovered(null)}
                  tabIndex={0}
                  role="button"
                  aria-label={`${dot.label}: ${dot.visits}`}
                />
                <circle
                  cx={dot.x}
                  cy={dot.y}
                  r={dot.r}
                  className="fill-indigo-500/40 stroke-indigo-600 dark:stroke-indigo-400 pointer-events-none"
                  strokeWidth="1.5"
                />
              </g>
            ))}
          </g>
        </svg>

        {hovered ? (
          <div
            className="pointer-events-none absolute z-10 max-w-[220px] rounded-lg border border-slate-200 dark:border-slate-700 bg-white/95 dark:bg-slate-900/95 px-3 py-2 text-xs shadow-lg"
            style={{
              left: Math.min(tooltipPos.x + 12, 280),
              top: Math.max(tooltipPos.y - 8, 8),
              transform: 'translateY(-100%)',
            }}
          >
            <p className="font-bold text-slate-900 dark:text-white">
              {countryCodeToFlag(hovered.code)} {hovered.label}{' '}
              <span className="text-slate-500">({hovered.code})</span>
            </p>
            <p className="text-slate-600 dark:text-slate-300 mt-0.5">
              {t('analytics.geo.visits')}: <span className="font-semibold">{hovered.visits}</span>
            </p>
            {hovered.city ? (
              <p className="text-slate-500 truncate">{hovered.city}</p>
            ) : null}
            {hovered.sampleIps.length > 0 ? (
              <p className="text-slate-500 mt-1">
                {t('analytics.geo.sampleIps')}: {hovered.sampleIps.join(', ')}
              </p>
            ) : null}
          </div>
        ) : null}
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
