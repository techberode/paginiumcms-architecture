// frontend/src/components/backend/AnalyticsView.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowRightLeft,
  BarChart3,
  Clock3,
  Download,
  Eye,
  FileQuestion,
  FileText,
  Link2,
  MapPin,
  MonitorSmartphone,
  MousePointerClick,
  RefreshCw,
  TrendingUp,
  Users,
} from 'lucide-react';
import {
  exportNotFoundCsv,
  getAnalyticsChart,
  getAnalyticsOverview,
  getNotFoundReport,
  type AnalyticsPayload,
  type ChartPoint,
  type NotFoundPathRow,
} from '../../api/analytics';
import { AnalyticsChart } from '../dashboard/AnalyticsChart';
import { AnalyticsRankedBarChart } from './analytics/AnalyticsRankedBarChart';
import { AnalyticsSegmentChart } from './analytics/AnalyticsSegmentChart';
import { aggregateGeoByCountry, referersToChartItems } from './analytics/analyticsChartData';
import { AdminPageSkeleton } from '../ui/AdminPageSkeleton';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { countryCodeToFlag } from '../../utils/countryFlag';

type AnalyticsTab = 'overview' | 'pages' | 'sources' | 'devices' | 'geo' | 'notFound';
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

function humanizeUri(uri: string, homeLabel: string): string {
  if (uri === '/' || uri === '') {
    return homeLabel;
  }
  const slug = uri.split('/').filter(Boolean).pop() ?? uri;
  return slug.replace(/[-_]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function refererTypeLabel(type: string | undefined, t: (key: string) => string): string {
  switch (type) {
    case 'direct':
      return t('analytics.sources.types.direct');
    case 'search':
      return t('analytics.sources.types.search');
    case 'social':
      return t('analytics.sources.types.social');
    case 'referral':
      return t('analytics.sources.types.referral');
    default:
      return type ?? '';
  }
}

export const AnalyticsView: React.FC = () => {
  const { t } = useI18n();
  const { error: toastError } = useToast();
  const [period, setPeriod] = useState<PeriodDays>(30);
  const [tab, setTab] = useState<AnalyticsTab>('overview');
  const [payload, setPayload] = useState<AnalyticsPayload | null>(null);
  const [chart, setChart] = useState<ChartPoint[]>([]);
  const [notFoundRows, setNotFoundRows] = useState<NotFoundPathRow[]>([]);
  const [notFoundLoading, setNotFoundLoading] = useState(false);
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

  const loadNotFound = useCallback(async () => {
    setNotFoundLoading(true);
    try {
      const report = await getNotFoundReport(period, 50);
      setNotFoundRows(report?.paths ?? []);
    } catch {
      toastError(t('analytics.notFound.toast.loadFailed'));
    } finally {
      setNotFoundLoading(false);
    }
  }, [period, t, toastError]);

  useEffect(() => {
    if (tab === 'notFound') {
      void loadNotFound();
    }
  }, [tab, loadNotFound]);

  const handleNotFoundExport = async () => {
    try {
      const blob = await exportNotFoundCsv(period);
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `not_found_${new Date().toISOString().slice(0, 10)}.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch {
      toastError(t('analytics.notFound.toast.exportFailed'));
    }
  };

  const overview = payload?.overview;
  const homeLabel = t('analytics.homeLabel');

  const tabs = useMemo(
    () =>
      [
        { id: 'overview' as const, label: t('analytics.tabs.overview'), icon: BarChart3 },
        { id: 'pages' as const, label: t('analytics.tabs.pages'), icon: FileText },
        { id: 'sources' as const, label: t('analytics.tabs.sources'), icon: Link2 },
        { id: 'devices' as const, label: t('analytics.tabs.devices'), icon: MonitorSmartphone },
        { id: 'geo' as const, label: t('analytics.tabs.geo'), icon: MapPin },
        { id: 'notFound' as const, label: t('analytics.tabs.notFound'), icon: FileQuestion },
      ] satisfies Array<{ id: AnalyticsTab; label: string; icon: typeof BarChart3 }>,
    [t]
  );

  const topPagesChart = useMemo(
    () =>
      (payload?.top_pages ?? []).map((page) => ({
        key: page.uri,
        label: humanizeUri(page.uri, homeLabel),
        sublabel: page.uri,
        value: page.views,
      })),
    [homeLabel, payload?.top_pages]
  );

  const topArticlesChart = useMemo(
    () =>
      (payload?.top_articles ?? []).map((article) => ({
        key: article.uri,
        label: article.title,
        sublabel: article.uri,
        value: article.views,
        barClassName: 'bg-emerald-500 dark:bg-emerald-400',
      })),
    [payload?.top_articles]
  );

  const sourcesChart = useMemo(
    () => referersToChartItems(payload?.top_referers ?? [], (type) => refererTypeLabel(type, t)),
    [payload?.top_referers, t]
  );

  const deviceSegments = useMemo(
    () => [
      { key: 'desktop', label: t('analytics.devices.desktop'), value: payload?.devices.desktop ?? 0, colorClassName: 'bg-indigo-500' },
      { key: 'mobile', label: t('analytics.devices.mobile'), value: payload?.devices.mobile ?? 0, colorClassName: 'bg-pink-500' },
      { key: 'tablet', label: t('analytics.devices.tablet'), value: payload?.devices.tablet ?? 0, colorClassName: 'bg-violet-400' },
      { key: 'unknown', label: t('analytics.devices.unknown'), value: payload?.devices.unknown ?? 0, colorClassName: 'bg-slate-400' },
    ],
    [payload?.devices, t]
  );

  const browsersChart = useMemo(
    () =>
      (payload?.browsers ?? []).map((browser) => ({
        key: browser.browser,
        label: browser.browser,
        value: browser.visits,
        barClassName: 'bg-sky-500 dark:bg-sky-400',
      })),
    [payload?.browsers]
  );

  const geoCountryChart = useMemo(
    () => aggregateGeoByCountry(payload?.geo ?? []),
    [payload?.geo]
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
          {tabs.map((item) => {
            const TabIcon = item.icon;
            return (
            <button
              key={item.id}
              type="button"
              onClick={() => setTab(item.id)}
              className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-bold border-b-2 transition ${
                tab === item.id
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
              }`}
            >
              <TabIcon className="h-4 w-4" />
              {item.label}
            </button>
            );
          })}
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
                  <AnalyticsRankedBarChart
                    items={topPagesChart}
                    loading={loading}
                    emptyMessage={t('analytics.empty.noPages')}
                    maxItems={5}
                  />
                </div>
                <div>
                  <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                    {t('analytics.sections.devices')}
                  </h3>
                  <AnalyticsSegmentChart items={deviceSegments} loading={loading} />
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
                <AnalyticsRankedBarChart
                  items={topPagesChart}
                  loading={loading}
                  emptyMessage={t('analytics.empty.noPages')}
                  maxItems={12}
                />
              </div>
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.topArticles')}
                </h3>
                <AnalyticsRankedBarChart
                  items={topArticlesChart}
                  loading={loading}
                  emptyMessage={t('analytics.empty.noPages')}
                  maxItems={12}
                />
              </div>
            </div>
          )}

          {tab === 'sources' && (
            <div>
              <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                {t('analytics.tabs.sources')}
              </h3>
              <AnalyticsRankedBarChart
                items={sourcesChart}
                loading={loading}
                emptyMessage={t('analytics.empty.noSources')}
                maxItems={15}
              />
            </div>
          )}

          {tab === 'devices' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.devices')}
                </h3>
                <AnalyticsSegmentChart items={deviceSegments} loading={loading} />
              </div>
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.browsers')}
                </h3>
                <AnalyticsRankedBarChart
                  items={browsersChart}
                  loading={loading}
                  emptyMessage={t('analytics.empty.noData')}
                  maxItems={10}
                />
              </div>
            </div>
          )}

          {tab === 'geo' && (
            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.geoSummary')}
                </h3>
                <AnalyticsRankedBarChart
                  items={geoCountryChart}
                  loading={loading}
                  emptyMessage={t('analytics.empty.noGeo')}
                  maxItems={12}
                />
              </div>
              <div className="space-y-2">
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.recentGeoVisits')}
                </h3>
                {(payload?.geo_visits ?? []).length === 0 ? (
                  <p className="text-sm text-slate-500 py-4">{t('analytics.empty.noGeo')}</p>
                ) : (
                  (payload?.geo_visits ?? []).map((visit, index) => (
                  <div key={`${visit.timestamp}-${index}`} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                    <div className="min-w-0">
                      <div className="inline-flex items-center gap-2 font-semibold">
                        <span aria-hidden>{countryCodeToFlag(visit.countryCode)}</span>
                        {visit.country}
                        {visit.city ? ` · ${visit.city}` : ''}
                      </div>
                      <div className="text-xs text-slate-500 truncate">{visit.requestUri}</div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-xs font-mono text-slate-500">{visit.ip_masked}</div>
                    </div>
                  </div>
                ))
                )}
              </div>
            </div>
          )}

          {tab === 'notFound' && (
            <div className="space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-slate-500 dark:text-slate-400">{t('analytics.notFound.subtitle')}</p>
                <div className="flex flex-wrap gap-2">
                  <button
                    type="button"
                    onClick={() => void loadNotFound()}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-bold"
                  >
                    <RefreshCw className="h-4 w-4" />
                    {t('analytics.refresh')}
                  </button>
                  <button
                    type="button"
                    onClick={() => void handleNotFoundExport()}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
                  >
                    <Download className="h-4 w-4" />
                    {t('analytics.notFound.exportCsv')}
                  </button>
                </div>
              </div>

              <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table className="min-w-full text-sm">
                  <thead className="bg-slate-50 dark:bg-slate-950/60 text-left">
                    <tr>
                      <th className="px-4 py-3 font-bold uppercase tracking-wider text-xs text-slate-500">
                        {t('analytics.notFound.columns.path')}
                      </th>
                      <th className="px-4 py-3 font-bold uppercase tracking-wider text-xs text-slate-500">
                        {t('analytics.notFound.columns.hits')}
                      </th>
                      <th className="px-4 py-3 font-bold uppercase tracking-wider text-xs text-slate-500">
                        {t('analytics.notFound.columns.lastSeen')}
                      </th>
                      <th className="px-4 py-3 font-bold uppercase tracking-wider text-xs text-slate-500">
                        {t('analytics.notFound.columns.referer')}
                      </th>
                      <th className="px-4 py-3 font-bold uppercase tracking-wider text-xs text-slate-500">
                        {t('analytics.notFound.columns.actions')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {notFoundLoading ? (
                      <tr>
                        <td colSpan={5} className="px-4 py-8 text-slate-500">
                          {t('analytics.notFound.loading')}
                        </td>
                      </tr>
                    ) : notFoundRows.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="px-4 py-8 text-slate-500">
                          {t('analytics.notFound.empty')}
                        </td>
                      </tr>
                    ) : (
                      notFoundRows.map((row) => (
                        <tr key={row.path} className="border-t border-slate-100 dark:border-slate-800">
                          <td className="px-4 py-3 font-mono text-xs">{row.path}</td>
                          <td className="px-4 py-3 font-bold">{row.hits}</td>
                          <td className="px-4 py-3 text-slate-500">
                            {new Date(row.lastSeen).toLocaleString()}
                          </td>
                          <td className="px-4 py-3 text-slate-500">{row.topReferer ?? '—'}</td>
                          <td className="px-4 py-3">
                            <Link
                              to={`/platform/redirects?from=${encodeURIComponent(row.path)}`}
                              className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-bold"
                            >
                              <ArrowRightLeft className="h-3.5 w-3.5" />
                              {t('analytics.notFound.createRedirect')}
                            </Link>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AnalyticsView;
