import type { GeoStat, TopReferer } from '../../../api/analytics';
import type { RankedChartItem } from './AnalyticsRankedBarChart';

export function refererBarClass(type?: string): string {
  switch (type) {
    case 'direct':
      return 'bg-slate-500 dark:bg-slate-400';
    case 'search':
      return 'bg-sky-500 dark:bg-sky-400';
    case 'social':
      return 'bg-pink-500 dark:bg-pink-400';
    case 'referral':
      return 'bg-indigo-500 dark:bg-indigo-400';
    default:
      return 'bg-indigo-500 dark:bg-indigo-400';
  }
}

export function aggregateGeoByCountry(geo: GeoStat[]): RankedChartItem[] {
  const totals = new Map<string, RankedChartItem>();

  for (const entry of geo) {
    const key = entry.countryCode ?? entry.country;
    const existing = totals.get(key);
    if (existing) {
      existing.value += entry.visits;
      continue;
    }
    totals.set(key, {
      key,
      label: entry.country,
      value: entry.visits,
      sublabel: entry.countryCode ? entry.countryCode.toUpperCase() : undefined,
    });
  }

  return [...totals.values()].sort((a, b) => b.value - a.value);
}

export function referersToChartItems(
  referers: TopReferer[],
  labelForType: (type: string | undefined) => string
): RankedChartItem[] {
  return referers.map((source, index) => ({
    key: `${source.referer}-${index}`,
    label: source.source ?? source.referer,
    sublabel:
      source.type && source.type !== 'direct'
        ? [labelForType(source.type), source.domain].filter(Boolean).join(' · ')
        : source.domain,
    value: source.visits,
    barClassName: refererBarClass(source.type),
  }));
}
