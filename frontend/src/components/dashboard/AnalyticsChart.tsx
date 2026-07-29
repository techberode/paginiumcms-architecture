// frontend/src/components/dashboard/AnalyticsChart.tsx
import React, { useMemo } from 'react';
import { ChartPoint } from '../../api/analytics';
import { useI18n } from '../../context/I18nContext';

interface AnalyticsChartProps {
  data: ChartPoint[];
  loading?: boolean;
}

export const AnalyticsChart: React.FC<AnalyticsChartProps> = ({ data, loading }) => {
  const { t } = useI18n();
  const hasActivity = useMemo(() => data.some((point) => point.visits > 0), [data]);
  const maxVisits = useMemo(
    () => Math.max(1, ...data.map((point) => point.visits)),
    [data]
  );

  if (loading) {
    return (
      <div className="flex justify-center py-10">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (data.length === 0 || !hasActivity) {
    return (
      <p className="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">
        {t('analytics.empty.noData')}
      </p>
    );
  }

  return (
    <div className="space-y-3">
      <div className="flex items-end gap-1 h-40">
        {data.map((point) => {
          const height = Math.max(4, Math.round((point.visits / maxVisits) * 100));
          return (
            <div key={point.date} className="flex-1 flex flex-col items-center justify-end gap-1 min-w-0">
              <span className="text-[10px] text-gray-500 dark:text-gray-400">{point.visits}</span>
              <div
                className="w-full rounded-t bg-indigo-500/80 dark:bg-indigo-400/80"
                style={{ height: `${height}%` }}
                title={`${point.date}: ${point.visits} visits`}
              />
            </div>
          );
        })}
      </div>
      <div className="flex justify-between text-[10px] text-gray-500 dark:text-gray-400 px-1">
        <span>{data[0]?.date}</span>
        <span>{data[data.length - 1]?.date}</span>
      </div>
    </div>
  );
};

export default AnalyticsChart;
