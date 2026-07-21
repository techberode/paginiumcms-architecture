// frontend/src/components/backend/AnalyticsView.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  BarChart3,
  Clock3,
  Eye,
  Globe2,
  Laptop,
  MousePointerClick,
  RefreshCw,
  Smartphone,
  Tablet,
  TrendingUp,
  Users,
} from 'lucide-react';
import {
  getAnalyticsChart,
  getAnalyticsOverview,
  type AnalyticsPayload,
  type ChartPoint,
} from '../../api/analytics';
import { AnalyticsChart } from '../dashboard/AnalyticsChart';
import { AdminPageSkeleton } from '../ui/AdminPageSkeleton';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

type AnalyticsTab = 'overview' | 'pages' | 'sources' | 'devices' | 'geo';
type PeriodDays = 7 | 14 | 30;

function formatDuration(seconds: number): string {
  if (seconds <= 0) {
    return '0s';
  }
  if (seconds < 60) {
    return `${seconds}s`;
  }
  const minutes = Math.floor(seconds / 60);
  const rest = seconds % 60;
  return rest > 0 ? `${minutes}m ${rest}s` : `${minutes}m`;
}

function humanizeUri(uri: string): string {
  if (uri === '/' || uri === '') {
    return 'Domov';
  }
  const slug = uri.split('/').filter(Boolean).pop() ?? uri;
  return slug.replace(/[-_]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function deviceTotal(devices: AnalyticsPayload['devices'] | undefined): number {
  if (!devices) {
    return 0;
  }
  return devices.desktop + devices.mobile + devices.tablet + devices.unknown;
}

function devicePercent(value: number, total: number): number {
  if (total <= 0) {
    return 0;
  }
  return Math.round((value / total) * 100);
}

export const AnalyticsView: React.FC = () => {
  const { t } = useI18n();
  const { error: toastError } = useToast();
  const [period, setPeriod] = useState<PeriodDays>(30);
  const [tab, setTab] = useState<AnalyticsTab>('overview');
  const [payload, setPayload] = useState<AnalyticsPayload | null>(null);
  const [chart, setChart] = useState<ChartPoint[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const periodKey = String(period);
      const [overview, chartData] = await Promise.all([
        getAnalyticsOverview(periodKey),
        getAnalyticsChart(period),
      ]);
      setPayload(overview);
      setChart(chartData);
    } catch {
      toastError(t('analytics.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [period, t, toastError]);

  useEffect(() => {
    void load();
  }, [load]);

  const overview = payload?.overview;
  const deviceTotalCount = deviceTotal(payload?.devices);

  const tabs = useMemo(
    () =>
      [
        { id: 'overview' as const, label: t('analytics.tabs.overview') },
        { id: 'pages' as const, label: t('analytics.tabs.pages') },
        { id: 'sources' as const, label: t('analytics.tabs.sources') },
        { id: 'devices' as const, label: t('analytics.tabs.devices') },
        { id: 'geo' as const, label: t('analytics.tabs.geo') },
      ] satisfies Array<{ id: AnalyticsTab; label: string }>,
    [t]
  );

  const kpiCards = [
    {
      label: t('analytics.kpi.pageViews'),
      value: overview?.page_views ?? 0,
      icon: Eye,
      accent: 'text-indigo-600',
    },
    {
      label: t('analytics.kpi.uniqueVisitors'),
      value: overview?.unique_visitors ?? 0,
      icon: Users,
      accent: 'text-violet-600',
    },
    {
      label: t('analytics.kpi.avgDuration'),
      value: formatDuration(overview?.avg_duration_seconds ?? 0),
      icon: Clock3,
      accent: 'text-emerald-600',
    },
    {
      label: t('analytics.kpi.bounceRate'),
      value: `${Math.round(overview?.bounce_rate ?? 0)}%`,
      icon: MousePointerClick,
      accent: 'text-rose-600',
    },
  ];

  if (loading && !payload) {
    return <AdminPageSkeleton />;
  }

  return (
    <div className="space-y-6 pb-16 animate-fadeIn">
      <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <div className="inline-flex items-center gap-2 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1.5 text-indigo-700 dark:text-indigo-300 text-sm font-bold mb-3">
            <BarChart3 className="h-4 w-4" />
            {t('analytics.badge')}
          </div>
          <h1 className="text-3xl font-black text-slate-900 dark:text-white">{t('analytics.title')}</h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">{t('analytics.subtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {[7, 14, 30].map((days) => (
            <button
              key={days}
              type="button"
              onClick={() => setPeriod(days as PeriodDays)}
              className={`px-4 py-2 rounded-xl text-sm font-bold transition ${
                period === days
                  ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                  : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300'
              }`}
            >
              {t(`analytics.period.${days}`)}
            </button>
          ))}
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-bold"
          >
            <RefreshCw className="h-4 w-4" />
            {t('analytics.refresh')}
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {kpiCards.map((card) => {
          const Icon = card.icon;
          return (
            <div
              key={card.label}
              className="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm"
            >
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-xs font-bold uppercase tracking-wider text-slate-500">{card.label}</p>
                  <p className="text-3xl font-black text-slate-900 dark:text-white mt-2">{card.value}</p>
                </div>
                <div className={`rounded-2xl bg-slate-50 dark:bg-slate-950 p-3 ${card.accent}`}>
                  <Icon className="h-5 w-5" />
                </div>
              </div>
              <div className="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-600">
                <TrendingUp className="h-3.5 w-3.5" />
                {t('analytics.trendPlaceholder')}
              </div>
            </div>
          );
        })}
      </div>

      <div className="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div className="flex flex-wrap gap-1 border-b border-slate-200 dark:border-slate-800 px-4 pt-4">
          {tabs.map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setTab(item.id)}
              className={`px-4 py-2 text-sm font-bold border-b-2 transition ${
                tab === item.id
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
              }`}
            >
              {item.label}
            </button>
          ))}
        </div>

        <div className="p-6">
          {tab === 'overview' && (
            <div className="grid grid-cols-1 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-6">
              <div>
                <h2 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-4">
                  {t('analytics.sections.dailyViews', { days: period })}
                </h2>
                <AnalyticsChart data={chart} loading={loading} />
              </div>
              <div className="space-y-6">
                <div>
                  <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                    {t('analytics.sections.topPages')}
                  </h3>
                  <div className="space-y-2">
                    {(payload?.top_pages ?? []).slice(0, 5).map((page, index) => (
                      <div
                        key={page.uri}
                        className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3"
                      >
                        <div className="min-w-0">
                          <div className="font-semibold text-slate-900 dark:text-white truncate">
                            {humanizeUri(page.uri)}
                          </div>
                          <div className="text-xs text-slate-500 truncate">{page.uri}</div>
                        </div>
                        <div className="text-sm font-black text-indigo-600 shrink-0">
                          {index + 1}. {page.views}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
                <div>
                  <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                    {t('analytics.sections.devices')}
                  </h3>
                  <div className="space-y-3">
                    {[
                      { key: 'desktop', label: t('analytics.devices.desktop'), value: payload?.devices.desktop ?? 0, icon: Laptop, color: 'bg-indigo-500' },
                      { key: 'mobile', label: t('analytics.devices.mobile'), value: payload?.devices.mobile ?? 0, icon: Smartphone, color: 'bg-pink-500' },
                      { key: 'tablet', label: t('analytics.devices.tablet'), value: payload?.devices.tablet ?? 0, icon: Tablet, color: 'bg-violet-400' },
                    ].map((item) => {
                      const Icon = item.icon;
                      const percent = devicePercent(item.value, deviceTotalCount);
                      return (
                        <div key={item.key}>
                          <div className="flex items-center justify-between text-sm font-semibold mb-1">
                            <span className="inline-flex items-center gap-2">
                              <Icon className="h-4 w-4 text-slate-500" />
                              {item.label}
                            </span>
                            <span>{percent}%</span>
                          </div>
                          <div className="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div className={`h-full ${item.color}`} style={{ width: `${percent}%` }} />
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>
            </div>
          )}

          {tab === 'pages' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.topPages')}
                </h3>
                <div className="space-y-2">
                  {(payload?.top_pages ?? []).map((page, index) => (
                    <div key={page.uri} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                      <div>
                        <div className="font-semibold">{humanizeUri(page.uri)}</div>
                        <div className="text-xs text-slate-500">{page.uri}</div>
                      </div>
                      <span className="font-black text-indigo-600">{index + 1}. {page.views}</span>
                    </div>
                  ))}
                </div>
              </div>
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.topArticles')}
                </h3>
                <div className="space-y-2">
                  {(payload?.top_articles ?? []).map((article, index) => (
                    <div key={article.uri} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                      <div>
                        <div className="font-semibold">{article.title}</div>
                        <div className="text-xs text-slate-500">{article.uri}</div>
                      </div>
                      <span className="font-black text-emerald-600">{index + 1}. {article.views}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {tab === 'sources' && (
            <div className="space-y-2">
              {(payload?.top_referers ?? []).map((source, index) => (
                <div key={`${source.referer}-${index}`} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                  <div className="font-semibold truncate">{source.referer}</div>
                  <span className="font-black text-indigo-600">{source.visits}</span>
                </div>
              ))}
            </div>
          )}

          {tab === 'devices' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="space-y-3">
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500">{t('analytics.sections.devices')}</h3>
                {[
                  { label: t('analytics.devices.desktop'), value: payload?.devices.desktop ?? 0 },
                  { label: t('analytics.devices.mobile'), value: payload?.devices.mobile ?? 0 },
                  { label: t('analytics.devices.tablet'), value: payload?.devices.tablet ?? 0 },
                  { label: t('analytics.devices.unknown'), value: payload?.devices.unknown ?? 0 },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                    <span>{item.label}</span>
                    <span className="font-black">{item.value}</span>
                  </div>
                ))}
              </div>
              <div className="space-y-2">
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500">{t('analytics.sections.browsers')}</h3>
                {(payload?.browsers ?? []).map((browser) => (
                  <div key={browser.browser} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                    <span>{browser.browser}</span>
                    <span className="font-black text-indigo-600">{browser.visits}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {tab === 'geo' && (
            <div className="space-y-2">
              {(payload?.geo ?? []).map((entry) => (
                <div key={entry.country} className="flex items-center justify-between rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                  <span className="inline-flex items-center gap-2 font-semibold">
                    <Globe2 className="h-4 w-4 text-indigo-500" />
                    {entry.country}
                  </span>
                  <span className="font-black text-indigo-600">{entry.visits}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AnalyticsView;
