import React, { useMemo } from 'react';
import { useI18n } from '../../../context/I18nContext';

export interface SegmentChartItem {
  key: string;
  label: string;
  value: number;
  colorClassName: string;
}

interface AnalyticsSegmentChartProps {
  items: SegmentChartItem[];
  loading?: boolean;
  emptyMessage?: string;
}

export const AnalyticsSegmentChart: React.FC<AnalyticsSegmentChartProps> = ({
  items,
  loading = false,
  emptyMessage,
}) => {
  const { t } = useI18n();
  const visibleItems = useMemo(() => items.filter((item) => item.value > 0), [items]);
  const total = useMemo(
    () => visibleItems.reduce((sum, item) => sum + item.value, 0),
    [visibleItems]
  );

  if (loading) {
    return (
      <div className="flex justify-center py-10">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (visibleItems.length === 0 || total <= 0) {
    return (
      <p className="text-sm text-slate-500 dark:text-slate-400 py-6 text-center">
        {emptyMessage ?? t('analytics.empty.noData')}
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex h-4 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        {visibleItems.map((item) => {
          const width = Math.max(0, (item.value / total) * 100);
          if (width <= 0) {
            return null;
          }
          return (
            <div
              key={item.key}
              className={`${item.colorClassName} transition-all`}
              style={{ width: `${width}%` }}
              title={`${item.label}: ${item.value}`}
            />
          );
        })}
      </div>
      <div className="grid gap-2 sm:grid-cols-2">
        {visibleItems.map((item) => {
          const percent = Math.round((item.value / total) * 100);
          return (
            <div
              key={item.key}
              className="flex items-center justify-between gap-2 rounded-xl border border-slate-200 dark:border-slate-800 px-3 py-2 text-sm"
            >
              <span className="inline-flex items-center gap-2 min-w-0">
                <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${item.colorClassName}`} />
                <span className="truncate font-medium text-slate-800 dark:text-slate-100">{item.label}</span>
              </span>
              <span className="shrink-0 font-black text-slate-900 dark:text-white">
                {percent}% <span className="text-xs font-normal text-slate-500">({item.value})</span>
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default AnalyticsSegmentChart;
