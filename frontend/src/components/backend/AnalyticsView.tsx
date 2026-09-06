// frontend/src/components/backend/AnalyticsView.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowRightLeft,
  BarChart3,
  Bot,
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
  ShieldBan,
  TrendingDown,
  TrendingUp,
  Users,
} from 'lucide-react';
import {
  banBotIp,
  exportNotFoundCsv,
  getAnalyticsChart,
  getAnalyticsOverview,
  getNotFoundReport,
  type AnalyticsPayload,
  type AnalyticsTrend,
  type AnalyticsTrendKey,
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

type AnalyticsTab = 'overview' | 'pages' | 'sources' | 'devices' | 'geo' | 'bots' | 'notFound';
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

function botKindLabel(kind: string, t: (key: string) => string): string {
  const key = `analytics.bots.kinds.${kind}`;
  const translated = t(key);
  return translated === key ? kind : translated;
}

function botKindClass(kind: string): string {
  switch (kind) {
    case 'search':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
    case 'social':
      return 'bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300';
    case 'monitor':
      return 'bg-violet-100 text-violet-800 dark:bg-violet-950/40 dark:text-violet-300';
    case 'tool':
    case 'malicious':
      return 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300';
    default:
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
  }
}

function formatTrendPercent(percent: number): string {
  const abs = Math.abs(percent);
  if (abs >= 100) {
    return `${Math.round(abs)}%`;
  }
  return `${abs.toFixed(1)}%`;
}

function trendTone(
  key: AnalyticsTrendKey,
  trend: AnalyticsTrend | undefined
): 'positive' | 'negative' | 'neutral' {
  if (!trend || (trend.percent === 0 && trend.delta === 0)) {
    return 'neutral';
  }
  const up = trend.direction === 'up';
  if (key === 'bounce_rate') {
    return up ? 'negative' : 'positive';
  }
  return up ? 'positive' : 'negative';
}

function trendClassName(tone: 'positive' | 'negative' | 'neutral'): string {
  switch (tone) {
    case 'positive':
      return 'text-emerald-600 dark:text-emerald-400';
    case 'negative':
      return 'text-rose-600 dark:text-rose-400';
    default:
      return 'text-slate-500 dark:text-slate-400';
  }
}

export const AnalyticsView: React.FC = () => {
  const { t } = useI18n();
  const { error: toastError, success: toastSuccess } = useToast();
  const [period, setPeriod] = useState<PeriodDays>(30);
  const [tab, setTab] = useState<AnalyticsTab>('overview');
  const [payload, setPayload] = useState<AnalyticsPayload | null>(null);
  const [chart, setChart] = useState<ChartPoint[]>([]);
  const [notFoundRows, setNotFoundRows] = useState<NotFoundPathRow[]>([]);
  const [notFoundLoading, setNotFoundLoading] = useState(false);
  const [loading, setLoading] = useState(true);
  const [banningIp, setBanningIp] = useState<string | null>(null);

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

  const handleBanBot = async (ip: string, botName: string, ipMasked: string) => {
    if (!window.confirm(t('analytics.bots.banConfirm', { ip: ipMasked }))) {
      return;
    }
    setBanningIp(ip);
    try {
      const result = await banBotIp(ip, botName);
      if (!result) {
        toastError(t('analytics.bots.banFailed'));
        return;
      }
      toastSuccess(t('analytics.bots.banSuccess'));
    } catch {
      toastError(t('analytics.bots.banFailed'));
    } finally {
      setBanningIp(null);
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
        { id: 'bots' as const, label: t('analytics.tabs.bots'), icon: Bot },
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

  const topBotsChart = useMemo(
    () =>
      (payload?.top_bots ?? []).map((bot) => ({
        key: `${bot.botKind}-${bot.botName}`,
        label: bot.botName,
        sublabel: botKindLabel(bot.botKind, t),
        value: bot.visits,
        barClassName:
          bot.botKind === 'tool' || bot.botKind === 'malicious'
            ? 'bg-rose-500 dark:bg-rose-400'
            : 'bg-amber-500 dark:bg-amber-400',
      })),
    [payload?.top_bots, t]
  );

  const platformsChart = useMemo(
    () =>
      (payload?.platforms ?? []).map((row) => ({
        key: row.platform,
        label: row.platform,
        value: row.visits,
        barClassName: 'bg-teal-500 dark:bg-teal-400',
      })),
    [payload?.platforms]
  );

  const kpiCards: Array<{
    label: string;
    value: string | number;
    icon: typeof Eye;
    accent: string;
    trendKey: AnalyticsTrendKey;
  }> = [
    {
      label: t('analytics.kpi.pageViews'),
      value: overview?.page_views ?? 0,
      icon: Eye,
      accent: 'text-indigo-600',
      trendKey: 'page_views',
    },
    {
      label: t('analytics.kpi.uniqueVisitors'),
      value: overview?.unique_visitors ?? 0,
      icon: Users,
      accent: 'text-violet-600',
      trendKey: 'unique_visitors',
    },
    {
      label: t('analytics.kpi.avgDuration'),
      value: formatDuration(overview?.avg_duration_seconds ?? 0),
      icon: Clock3,
      accent: 'text-emerald-600',
      trendKey: 'avg_duration_seconds',
    },
    {
      label: t('analytics.kpi.bounceRate'),
      value: `${Math.round(overview?.bounce_rate ?? 0)}%`,
      icon: MousePointerClick,
      accent: 'text-rose-600',
      trendKey: 'bounce_rate',
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
          const trend = overview?.trends?.[card.trendKey];
          const tone = trendTone(card.trendKey, trend);
          const TrendIcon = trend?.direction === 'down' ? TrendingDown : TrendingUp;
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
              <div
                className={`mt-3 inline-flex items-center gap-1 text-xs font-bold ${trendClassName(tone)}`}
                title={t('analytics.trendVsPrevious')}
              >
                {trend && (trend.percent !== 0 || trend.delta !== 0) ? (
                  <>
                    <TrendIcon className="h-3.5 w-3.5" />
                    {trend.direction === 'up' ? '+' : '−'}
                    {formatTrendPercent(trend.percent)}
                  </>
                ) : (
                  <span>{t('analytics.trendFlat')}</span>
                )}
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
            <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.devices')}
                </h3>
                <AnalyticsSegmentChart items={deviceSegments} loading={loading} />
              </div>
              <div>
                <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                  {t('analytics.sections.platforms')}
                </h3>
                <AnalyticsRankedBarChart
                  items={platformsChart}
                  loading={loading}
                  emptyMessage={t('analytics.empty.noPlatforms')}
                  maxItems={10}
                />
              </div>
              <div className="lg:col-span-2 xl:col-span-1">
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

          {tab === 'bots' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                  <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('analytics.bots.human')}</p>
                  <p className="mt-2 text-2xl font-black text-slate-900 dark:text-white">{payload?.bot_summary?.human ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                  <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('analytics.bots.botTraffic')}</p>
                  <p className="mt-2 text-2xl font-black text-amber-600">{payload?.bot_summary?.bot ?? 0}</p>
                </div>
                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                  <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{t('analytics.bots.botShare')}</p>
                  <p className="mt-2 text-2xl font-black text-slate-900 dark:text-white">{payload?.bot_summary?.bot_share ?? 0}%</p>
                </div>
              </div>

              <div className="rounded-xl border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/60 dark:bg-indigo-950/20 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p className="text-sm text-slate-600 dark:text-slate-300">{t('analytics.bots.wafHint')}</p>
                <Link
                  to="/settings?category=security&group=firewall"
                  className="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300 hover:underline shrink-0"
                >
                  {t('analytics.bots.wafSettings')}
                  <ArrowRightLeft className="h-4 w-4" aria-hidden="true" />
                </Link>
              </div>

              <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div>
                  <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                    {t('analytics.sections.topBots')}
                  </h3>
                  <AnalyticsRankedBarChart
                    items={topBotsChart}
                    loading={loading}
                    emptyMessage={t('analytics.empty.noBots')}
                    maxItems={12}
                  />
                </div>
                <div className="space-y-2">
                  <h3 className="text-sm font-black uppercase tracking-wider text-slate-500 mb-3">
                    {t('analytics.sections.recentBotVisits')}
                  </h3>
                  {(payload?.bot_visits ?? []).length === 0 ? (
                    <p className="text-sm text-slate-500 py-4">{t('analytics.empty.noBots')}</p>
                  ) : (
                    (payload?.bot_visits ?? []).map((visit, index) => (
                      <div key={`${visit.timestamp}-${index}`} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 px-4 py-3">
                        <div className="min-w-0">
                          <div className="inline-flex items-center gap-2 font-semibold flex-wrap">
                            <span>{visit.botName}</span>
                            <span className={`text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full ${botKindClass(visit.botKind)}`}>
                              {botKindLabel(visit.botKind, t)}
                            </span>
                            {visit.blockRecommended ? (
                              <span className="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                                {t('analytics.bots.blockRecommended')}
                              </span>
                            ) : null}
                          </div>
                          <div className="text-xs text-slate-500 truncate">{visit.requestUri}</div>
                        </div>
                        <div className="text-right shrink-0 flex flex-col items-end gap-2">
                          <div className="text-xs font-mono text-slate-500">{visit.ip_masked}</div>
                          {visit.ip ? (
                            <button
                              type="button"
                              disabled={banningIp === visit.ip}
                              onClick={() => void handleBanBot(visit.ip!, visit.botName, visit.ip_masked)}
                              className="inline-flex items-center gap-1 rounded-lg border border-rose-200 dark:border-rose-900/50 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/30 disabled:opacity-50"
                            >
                              <ShieldBan className="h-3.5 w-3.5" aria-hidden="true" />
                              {banningIp === visit.ip ? t('analytics.bots.banning') : t('analytics.bots.banIp')}
                            </button>
                          ) : null}
                        </div>
                      </div>
                    ))
                  )}
                </div>
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
