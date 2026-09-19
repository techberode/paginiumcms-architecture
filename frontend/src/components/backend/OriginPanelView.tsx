import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Activity,
  AlertCircle,
  CheckCircle2,
  CircleDashed,
  History,
  Layers,
  Newspaper,
  RefreshCw,
  Radar,
} from 'lucide-react';
import {
  originApi,
  type FeatureProbeStatus,
  type OriginCatalogIteration,
  type OriginChecklistSlice,
  type OriginDeployStatus,
  type OriginFeatureProbe,
  type OriginOverview,
} from '../../api/origin';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { useToast } from '../../hooks/useToast';
import { ProgressBar } from './ProgressBar';

const DEPLOY_STYLE: Record<string, string> = {
  live: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
  partial_live: 'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100',
  pending_deploy: 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
  unreleased: 'bg-violet-100 text-violet-900 dark:bg-violet-950/40 dark:text-violet-100',
  in_progress: 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100',
  planned: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
};

const CHECKLIST_ITEM_STYLE: Record<string, string> = {
  done: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
  partial: 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
  pending: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
};

const originLabel = (t: (key: string) => string, key: string, resolved?: string) =>
  resolved && resolved !== key ? resolved : t(key);

const STATUS_STYLE: Record<FeatureProbeStatus, string> = {
  implemented: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
  partial: 'bg-amber-100 text-amber-950 dark:bg-amber-950/40 dark:text-amber-100',
  missing: 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
  unknown: 'bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300',
};

export const OriginPanelView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const { settings } = useSettings();
  const [loading, setLoading] = useState(true);
  const [overview, setOverview] = useState<OriginOverview | null>(null);
  const [disabled, setDisabled] = useState(false);

  const load = useCallback(async () => {
    if (settings.origin?.enabled !== true) {
      setDisabled(true);
      setOverview(null);
      setLoading(false);
      return;
    }

    setDisabled(false);
    setLoading(true);
    try {
      const response = await originApi.overview();
      if (response.status === 404) {
        setDisabled(true);
        setOverview(null);
        return;
      }
      if (!response.success || !response.data) {
        toast.error(response.error || t('origin.loadFailed'));
        return;
      }
      setOverview(response.data);
    } catch {
      toast.error(t('origin.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [settings.origin?.enabled, toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const groupedProbes = useMemo(() => {
    if (!overview) {
      return [];
    }

    const groups = new Map<string, OriginFeatureProbe[]>();
    for (const probe of overview.probes) {
      const list = groups.get(probe.group) ?? [];
      list.push(probe);
      groups.set(probe.group, list);
    }

    return Array.from(groups.entries()).sort(([a], [b]) => a.localeCompare(b));
  }, [overview]);

  const catalog = overview?.catalog;

  const { focusIterations, shippedIterations } = useMemo(() => {
    const rows = catalog?.iterations ?? [];
    const focus = rows.filter((iteration) => {
      if (iteration.id.startsWith('ops.')) {
        return true;
      }
      if (iteration.percentComplete < 100) {
        return true;
      }
      return iteration.deployStatus === 'unreleased'
        || iteration.deployStatus === 'pending_deploy'
        || iteration.deployStatus === 'in_progress';
    });
    const focusIds = new Set(focus.map((row) => row.id));
    return {
      focusIterations: focus,
      shippedIterations: rows.filter((row) => !focusIds.has(row.id)),
    };
  }, [catalog]);

  const deployLabel = (status: OriginDeployStatus | undefined) =>
    status ? t(`origin.deploy.${status}`) : t('origin.deploy.planned');

  const renderChecklistSlice = (slice: OriginChecklistSlice) => (
    <li key={slice.id} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 space-y-3">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <div className="text-sm font-bold font-mono">{slice.id}</div>
          {slice.catalogIterationIds.length > 0 ? (
            <div className="text-[11px] text-slate-500 mt-1">{slice.catalogIterationIds.join(' · ')}</div>
          ) : null}
          {slice.issues.length > 0 ? (
            <div className="text-[11px] text-slate-500 mt-1">{slice.issues.join(', ')}</div>
          ) : null}
        </div>
        <div className="flex flex-col items-end gap-1">
          <span className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${DEPLOY_STYLE[slice.deployStatus] ?? DEPLOY_STYLE.planned}`}>
            {deployLabel(slice.deployStatus)}
          </span>
          <div className="text-lg font-black text-indigo-600">{slice.percentComplete}%</div>
        </div>
      </div>
      <ProgressBar
        percent={slice.percentComplete}
        tone={slice.percentComplete >= 100 ? 'emerald' : slice.percentComplete > 0 ? 'amber' : 'indigo'}
      />
      <ul className="space-y-1.5">
        {slice.items.map((item) => (
          <li key={item.id} className="flex flex-wrap items-center justify-between gap-2 text-xs">
            <span>{originLabel(t, item.labelKey, item.labelLabel)}</span>
            <span className={`rounded-full px-2 py-0.5 font-bold ${CHECKLIST_ITEM_STYLE[item.status]}`}>
              {t(`origin.checklistItem.${item.status}`)}
            </span>
          </li>
        ))}
      </ul>
    </li>
  );

  const renderIteration = (iteration: OriginCatalogIteration) => (
    <li key={iteration.id} className="rounded-lg border border-slate-200 p-3 dark:border-slate-700 space-y-3">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <div className="text-sm font-bold">{originLabel(t, iteration.titleKey, iteration.titleLabel)}</div>
          <div className="text-xs text-slate-500 font-mono">{iteration.id}</div>
          {iteration.since ? (
            <div className="text-[11px] text-slate-500 mt-1">
              {t(`origin.phase.${iteration.phase}`)} · {iteration.since}
            </div>
          ) : (
            <div className="text-[11px] text-slate-500 mt-1">{t(`origin.phase.${iteration.phase}`)}</div>
          )}
        </div>
        <div className="flex flex-col items-end gap-1">
          {iteration.deployStatus ? (
            <span
              className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${DEPLOY_STYLE[iteration.deployStatus] ?? DEPLOY_STYLE.planned}`}
            >
              {deployLabel(iteration.deployStatus)}
            </span>
          ) : null}
          <div className="text-lg font-black text-indigo-600">{iteration.percentComplete}%</div>
        </div>
      </div>
      <ProgressBar
        percent={iteration.percentComplete}
        tone={iteration.percentComplete >= 100 ? 'emerald' : iteration.percentComplete > 0 ? 'amber' : 'indigo'}
      />
      <ul className="space-y-1.5">
        {iteration.items.map((item) => (
          <li key={item.id} className="flex flex-wrap items-center justify-between gap-2 text-xs">
            <span>{originLabel(t, item.titleKey, item.titleLabel)}</span>
            <span className={`rounded-full px-2 py-0.5 font-bold ${STATUS_STYLE[item.status]}`}>
              {item.percentComplete}%
            </span>
          </li>
        ))}
      </ul>
    </li>
  );

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Radar className="w-7 h-7 text-violet-600" />
            {t('origin.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('origin.subtitle')}</p>
        </div>
        <button
          type="button"
          onClick={() => void load()}
          className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
        >
          <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
          {t('origin.refresh')}
        </button>
      </div>

      {loading && !overview && !disabled ? (
        <p className="text-sm text-slate-500">{t('origin.loading')}</p>
      ) : disabled ? (
        <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
          {t('origin.disabled')}
        </p>
      ) : overview && catalog ? (
        <>
          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-3">
            <h2 className="text-sm font-bold">{t('origin.sections.progress')}</h2>
            {catalog.runtime ? (
              <div className="flex flex-wrap gap-4 text-xs text-slate-600 dark:text-slate-300">
                <div>
                  {t('origin.runtime.appVersion')}:{' '}
                  <strong className="font-mono">{catalog.runtime.appVersion}</strong>
                </div>
                <div>
                  {t('origin.runtime.environment')}: <strong>{catalog.runtime.environment}</strong>
                </div>
              </div>
            ) : null}
            <div className="flex flex-wrap items-end justify-between gap-3">
              <div>
                <div className="text-xs uppercase tracking-wide text-slate-500">{t('origin.summary.overall')}</div>
                <div className="text-3xl font-black text-indigo-600">{catalog.progress.percent}%</div>
              </div>
              <div className="text-xs text-slate-500">
                {t('origin.catalogUpdated')}: {catalog.updatedAt}
              </div>
            </div>
            <ProgressBar percent={catalog.progress.percent} />
            <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-5 text-sm">
              <div>
                {t('origin.summary.shippedIterations')}: <strong>{catalog.progress.shipped}</strong>
              </div>
              <div>
                {t('origin.summary.partialIterations')}: <strong>{catalog.progress.partial}</strong>
              </div>
              <div>
                {t('origin.summary.plannedIterations')}: <strong>{catalog.progress.planned}</strong>
              </div>
              {typeof catalog.progress.liveOnInstance === 'number' ? (
                <div>
                  {t('origin.runtime.liveIterations')}: <strong>{catalog.progress.liveOnInstance}</strong>
                </div>
              ) : null}
              {typeof catalog.progress.pendingDeploy === 'number' ? (
                <div>
                  {t('origin.runtime.pendingDeploy')}: <strong>{catalog.progress.pendingDeploy}</strong>
                </div>
              ) : null}
            </div>
          </section>

          {catalog.snapshot && catalog.snapshot.groups.length > 0 ? (
            <section className="rounded-xl border border-violet-200 bg-violet-50/60 p-4 dark:border-violet-900/50 dark:bg-violet-950/20 space-y-4">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 className="text-sm font-bold flex items-center gap-2">
                    <Newspaper className="h-4 w-4 text-violet-600" />
                    {t('origin.sections.snapshot')}
                  </h2>
                  <p className="mt-1 text-sm text-slate-700 dark:text-slate-200">
                    {originLabel(t, catalog.snapshot.headlineKey, catalog.snapshot.headlineLabel)}
                  </p>
                </div>
                <div className="text-xs text-slate-600 dark:text-slate-300 space-y-1 text-right">
                  <div>
                    {t('origin.snapshot.asOf')}: <strong>{catalog.snapshot.asOf}</strong>
                  </div>
                  {catalog.snapshot.latestTag ? (
                    <div>
                      {t('origin.snapshot.latestTag')}:{' '}
                      <strong className="font-mono">{catalog.snapshot.latestTag}</strong>
                    </div>
                  ) : null}
                </div>
              </div>
              <div className="grid gap-3 lg:grid-cols-3">
                {catalog.snapshot.groups.map((group) => {
                  const tone =
                    group.id === 'live'
                      ? 'border-emerald-200 dark:border-emerald-900/40'
                      : group.id === 'unreleased'
                        ? 'border-violet-200 dark:border-violet-900/40'
                        : 'border-slate-200 dark:border-slate-700';
                  return (
                    <div key={group.id || group.titleKey} className={`rounded-lg border bg-white/80 p-3 dark:bg-slate-950/40 ${tone}`}>
                      <h3 className="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                        {originLabel(t, group.titleKey, group.titleLabel)}
                      </h3>
                      <ul className="space-y-2">
                        {group.items.map((item) => (
                          <li key={`${group.id}-${item.titleKey}`} className="text-sm">
                            <div className="font-semibold text-slate-800 dark:text-slate-100">
                              {originLabel(t, item.titleKey, item.titleLabel)}
                            </div>
                            {item.noteKey || item.noteLabel ? (
                              <div className="text-[11px] text-slate-500 mt-0.5">
                                {originLabel(t, item.noteKey ?? '', item.noteLabel ?? undefined)}
                              </div>
                            ) : null}
                          </li>
                        ))}
                      </ul>
                    </div>
                  );
                })}
              </div>
            </section>
          ) : null}

          {catalog.checklist && catalog.checklist.slices.length > 0 ? (
            <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-4">
              <h2 className="text-sm font-bold flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4" />
                {t('origin.sections.operatorSlices')}
              </h2>
              <p className="text-xs text-slate-500">
                {t('origin.checklistUpdated')}: {catalog.checklist.updatedAt}
              </p>
              <ul className="space-y-3">{catalog.checklist.slices.map(renderChecklistSlice)}</ul>
            </section>
          ) : null}

          <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
              <div className="text-xs uppercase tracking-wide text-slate-500">{t('origin.summary.implemented')}</div>
              <div className="mt-2 text-2xl font-black text-emerald-600">{overview.summary.implemented}</div>
            </div>
            <div className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
              <div className="text-xs uppercase tracking-wide text-slate-500">{t('origin.summary.partial')}</div>
              <div className="mt-2 text-2xl font-black text-amber-600">{overview.summary.partial}</div>
            </div>
            <div className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
              <div className="text-xs uppercase tracking-wide text-slate-500">{t('origin.summary.missing')}</div>
              <div className="mt-2 text-2xl font-black text-slate-600">{overview.summary.missing}</div>
            </div>
            <div className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
              <div className="text-xs uppercase tracking-wide text-slate-500">{t('origin.summary.total')}</div>
              <div className="mt-2 text-2xl font-black text-indigo-600">{overview.summary.total}</div>
            </div>
          </section>

          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <h2 className="text-sm font-bold flex items-center gap-2">
              <Activity className="h-4 w-4" />
              {t('origin.sections.summary')}
            </h2>
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
              <div>
                {t('origin.summary.pages')}: <strong>{overview.counts.pages ?? 0}</strong>
              </div>
              <div>
                {t('origin.summary.articles')}: <strong>{overview.counts.articles ?? 0}</strong>
              </div>
              <div>
                {t('origin.summary.media')}: <strong>{overview.counts.media ?? 0}</strong>
              </div>
              <div>
                {t('origin.summary.comments')}: <strong>{overview.counts.comments ?? 0}</strong>
              </div>
            </div>
          </section>

          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-4">
            <h2 className="text-sm font-bold flex items-center gap-2">
              <Layers className="h-4 w-4" />
              {t('origin.sections.roadmap')}
            </h2>
            {focusIterations.length > 0 ? (
              <div className="space-y-3">
                <h3 className="text-xs font-bold uppercase tracking-wide text-slate-500">
                  {t('origin.sections.focus')}
                </h3>
                <ul className="space-y-3">{focusIterations.map(renderIteration)}</ul>
              </div>
            ) : null}
            {shippedIterations.length > 0 ? (
              <div className="space-y-3">
                <h3 className="text-xs font-bold uppercase tracking-wide text-slate-500">
                  {t('origin.sections.shipped')}
                </h3>
                <ul className="space-y-3">{shippedIterations.map(renderIteration)}</ul>
              </div>
            ) : null}
          </section>

          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-3">
            <h2 className="text-sm font-bold flex items-center gap-2">
              <History className="h-4 w-4" />
              {t('origin.sections.timeline')}
            </h2>
            <ul className="space-y-2">
              {catalog.timeline.map((entry) => (
                <li
                  key={`${entry.version}-${entry.date}`}
                  className="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                >
                  <div>
                    <div className="font-bold font-mono">{entry.version}</div>
                    <div className="text-xs text-slate-500">{entry.date}</div>
                  </div>
                  <div className="text-xs text-slate-600 dark:text-slate-300 max-w-md">
                    {originLabel(t, entry.summaryKey, entry.summaryLabel)}
                  </div>
                </li>
              ))}
            </ul>
          </section>

          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-4">
            <div>
              <h2 className="text-sm font-bold flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4" />
                {t('origin.sections.checklist')}
              </h2>
              <p className="mt-1 text-xs text-slate-500">{t('origin.readOnlyHint')}</p>
            </div>

            {groupedProbes.map(([group, probes]) => (
              <div key={group}>
                <h3 className="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">
                  {t(`origin.groups.${group}`) !== `origin.groups.${group}` ? t(`origin.groups.${group}`) : group}
                </h3>
                <ul className="space-y-2">
                  {probes.map((probe) => (
                    <li
                      key={probe.id}
                      className="rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700 flex flex-wrap items-start justify-between gap-2"
                    >
                      <div>
                        <div className="text-sm font-semibold">{originLabel(t, probe.labelKey, probe.labelLabel)}</div>
                        <div className="text-xs text-slate-500 font-mono">{probe.id}</div>
                        <div className="text-xs text-slate-600 dark:text-slate-300 mt-1">{probe.message}</div>
                      </div>
                      <span
                        className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold ${STATUS_STYLE[probe.status]}`}
                      >
                        {probe.status === 'implemented' ? <CheckCircle2 className="h-3 w-3" /> : null}
                        {probe.status === 'partial' ? <AlertCircle className="h-3 w-3" /> : null}
                        {probe.status === 'missing' ? <CircleDashed className="h-3 w-3" /> : null}
                        {t(`origin.status.${probe.status}`)}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            ))}
          </section>
        </>
      ) : null}
    </div>
  );
};
