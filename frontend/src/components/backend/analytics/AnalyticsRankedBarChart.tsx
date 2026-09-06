import React, { useMemo } from 'react';
import { useI18n } from '../../../context/I18nContext';
import { countryCodeToFlag } from '../../../utils/countryFlag';

export interface RankedChartItem {
  key: string;
  label: string;
  value: number;
  sublabel?: string;
  barClassName?: string;
  countryCode?: string | null;
}

interface AnalyticsRankedBarChartProps {
  items: RankedChartItem[];
  loading?: boolean;
  emptyMessage?: string;
  maxItems?: number;
  valueSuffix?: string;
}

const DEFAULT_BAR = 'bg-indigo-500 dark:bg-indigo-400';

export const AnalyticsRankedBarChart: React.FC<AnalyticsRankedBarChartProps> = ({
  items,
  loading = false,
  emptyMessage,
  maxItems = 10,
  valueSuffix = '',
}) => {
  const { t } = useI18n();
  const visibleItems = useMemo(
    () => items.filter((item) => item.value > 0).slice(0, maxItems),
    [items, maxItems]
  );
  const maxValue = useMemo(
    () => Math.max(1, ...visibleItems.map((item) => item.value)),
    [visibleItems]
  );

  if (loading) {
    return (
      <div className="flex justify-center py-10">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (visibleItems.length === 0) {
    return (
      <p className="text-sm text-slate-500 dark:text-slate-400 py-6 text-center">
        {emptyMessage ?? t('analytics.empty.noData')}
      </p>
    );
  }

  return (
    <div className="space-y-3">
      {visibleItems.map((item, index) => {
        const width = Math.max(4, Math.round((item.value / maxValue) * 100));
        return (
          <div key={item.key} className="space-y-1">
            <div className="flex items-start justify-between gap-3 text-sm">
              <div className="min-w-0">
                <div className="font-semibold text-slate-900 dark:text-white inline-flex items-center gap-2 min-w-0">
                  {item.countryCode ? (
                    <span aria-hidden className="shrink-0">{countryCodeToFlag(item.countryCode)}</span>
                  ) : null}
                  <span className="truncate">{item.label}</span>
                </div>
                {item.sublabel ? (
                  <div className="text-xs text-slate-500 dark:text-slate-400 truncate">{item.sublabel}</div>
                ) : null}
              </div>
              <div className="shrink-0 text-right">
                <span className="font-black text-indigo-600 dark:text-indigo-400">
                  {item.value.toLocaleString()}
                  {valueSuffix}
                </span>
                <span className="ml-1 text-xs text-slate-400">#{index + 1}</span>
              </div>
            </div>
            <div className="h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div
                className={`h-full rounded-full transition-all ${item.barClassName ?? DEFAULT_BAR}`}
                style={{ width: `${width}%` }}
                title={`${item.label}: ${item.value}`}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default AnalyticsRankedBarChart;
