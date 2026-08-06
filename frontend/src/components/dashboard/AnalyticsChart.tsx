// frontend/src/components/dashboard/AnalyticsChart.tsx
import React, { useMemo } from 'react';
import { ChartPoint } from '../../api/analytics';
import { useI18n } from '../../context/I18nContext';

interface AnalyticsChartProps {
  data: ChartPoint[];
  loading?: boolean;
  showPageViews?: boolean;
}

export const AnalyticsChart: React.FC<AnalyticsChartProps> = ({
  data,
  loading,
  showPageViews = true,
}) => {
  const { t } = useI18n();
  const hasActivity = useMemo(
    () => data.some((point) => point.visits > 0 || point.page_views > 0),
    [data]
  );
  const maxVisits = useMemo(
    () => Math.max(1, ...data.map((point) => Math.max(point.visits, point.page_views))),
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
      {showPageViews ? (
        <div className="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-500 dark:text-slate-400">
          <span className="inline-flex items-center gap-2">
            <span className="h-2.5 w-2.5 rounded-sm bg-indigo-500 dark:bg-indigo-400" />
            {t('analytics.chart.visits')}
          </span>
          <span className="inline-flex items-center gap-2">
            <span className="h-2.5 w-2.5 rounded-sm bg-violet-400/80" />
            {t('analytics.chart.pageViews')}
          </span>
        </div>
      ) : null}
      <div className="flex items-end gap-1 h-44">
        {data.map((point) => {
          const visitHeight = Math.max(4, Math.round((point.visits / maxVisits) * 100));
          const pageViewHeight = Math.max(4, Math.round((point.page_views / maxVisits) * 100));
          return (
            <div key={point.date} className="flex-1 flex flex-col items-center justify-end gap-1 min-w-0">
              <span className="text-[10px] text-gray-500 dark:text-gray-400">{point.visits}</span>
              <div className="flex w-full items-end justify-center gap-0.5 h-32">
                {showPageViews ? (
                  <div
                    className="w-[42%] rounded-t bg-violet-400/70 dark:bg-violet-400/60"
                    style={{ height: `${pageViewHeight}%` }}
                    title={`${point.date}: ${point.page_views} ${t('analytics.chart.pageViews')}`}
                  />
                ) : null}
                <div
                  className={`${showPageViews ? 'w-[42%]' : 'w-full'} rounded-t bg-indigo-500/90 dark:bg-indigo-400/80`}
                  style={{ height: `${visitHeight}%` }}
                  title={`${point.date}: ${point.visits} ${t('analytics.chart.visits')}`}
                />
              </div>
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
