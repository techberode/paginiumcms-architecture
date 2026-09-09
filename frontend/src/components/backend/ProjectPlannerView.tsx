import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ListChecks, Plus, RefreshCw } from 'lucide-react';
import {
  projectPlannerApi,
  type ProjectPlanOverview,
  type ProjectPlanSummary,
} from '../../api/projectPlanner';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { useToast } from '../../hooks/useToast';
import { progressBarTone } from '../../utils/projectPlanProgress';
import {
  formatVarianceLabel,
  slugifyPlanId,
  VARIANCE_BADGE_CLASS,
} from '../../utils/projectPlanVariance';
import { ProgressBar } from './ProgressBar';

const PLAN_ID_PATTERN = /^[a-z0-9-]{1,64}$/;

export const ProjectPlannerView: React.FC = () => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const navigate = useNavigate();
  const { settings } = useSettings();
  const [loading, setLoading] = useState(true);
  const [disabled, setDisabled] = useState(false);
  const [forbidden, setForbidden] = useState(false);
  const [plans, setPlans] = useState<ProjectPlanSummary[]>([]);
  const [overview, setOverview] = useState<ProjectPlanOverview | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [creating, setCreating] = useState(false);
  const [title, setTitle] = useState('');
  const [planId, setPlanId] = useState('');
  const [idTouched, setIdTouched] = useState(false);
  const [description, setDescription] = useState('');
  const [timezone, setTimezone] = useState(
    () => Intl.DateTimeFormat().resolvedOptions().timeZone || 'Europe/Bratislava'
  );
  const [isDefault, setIsDefault] = useState(false);

  const settingEnabled = settings.projectPlanner?.enabled !== false;

  const resetForm = () => {
    setTitle('');
    setPlanId('');
    setIdTouched(false);
    setDescription('');
    setIsDefault(plans.length === 0);
  };

  const load = useCallback(async () => {
    if (!settingEnabled) {
      setDisabled(true);
      setForbidden(false);
      setPlans([]);
      setOverview(null);
      setLoading(false);
      return;
    }

    setLoading(true);
    setDisabled(false);
    setForbidden(false);
    try {
      const [listResponse, overviewResponse] = await Promise.all([
        projectPlannerApi.list(),
        projectPlannerApi.overview(),
      ]);

      if (listResponse.status === 404 || overviewResponse.status === 404) {
        setDisabled(true);
        setPlans([]);
        setOverview(null);
        return;
      }
      if (listResponse.status === 403 || overviewResponse.status === 403) {
        setForbidden(true);
        setPlans([]);
        setOverview(null);
        return;
      }
      if (!listResponse.success) {
        toast.error(listResponse.error || t('projectPlanner.loadFailed'));
        return;
      }

      setPlans(listResponse.data?.plans ?? []);
      setOverview(overviewResponse.success ? overviewResponse.data ?? null : null);
      if (listResponse.data?.plans?.length === 0) {
        setIsDefault(true);
      }
    } catch {
      toast.error(t('projectPlanner.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [settingEnabled, t, toast]);

  useEffect(() => {
    void load();
  }, [load]);

  const defaultPhases = useMemo(
    () => [
      { id: 'discovery', title: t('projectPlanner.phases.discovery'), sortOrder: 1 },
      { id: 'content', title: t('projectPlanner.phases.content'), sortOrder: 2 },
      { id: 'launch', title: t('projectPlanner.phases.launch'), sortOrder: 3 },
    ],
    [t]
  );

  const handleTitleChange = (value: string) => {
    setTitle(value);
    if (!idTouched) {
      setPlanId(slugifyPlanId(value));
    }
  };

  const handleCreate = async () => {
    const trimmedTitle = title.trim();
    const id = planId.trim();
    if (trimmedTitle === '') {
      toast.error(t('projectPlanner.toast.titleRequired'));
      return;
    }
    if (!PLAN_ID_PATTERN.test(id)) {
      toast.error(t('projectPlanner.toast.idRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await projectPlannerApi.create({
        id,
        title: trimmedTitle,
        description: description.trim(),
        timezone: timezone.trim() || 'UTC',
        isDefault,
        phases: defaultPhases,
        items: [],
      });
      if (!created.success || !created.data) {
        toast.error(created.error || t('projectPlanner.toast.createFailed'));
        return;
      }
      toast.success(t('projectPlanner.toast.created'));
      setShowCreate(false);
      resetForm();
      navigate(`/platform/project-planner/${created.data.id}`);
    } finally {
      setCreating(false);
    }
  };

  const formatDate = (iso: string) =>
    new Date(iso).toLocaleString(locale, { dateStyle: 'medium', timeStyle: 'short' });

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <ListChecks className="w-7 h-7 text-indigo-600" />
            {t('projectPlanner.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('projectPlanner.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            {t('projectPlanner.refresh')}
          </button>
          {!disabled && !forbidden ? (
            <button
              type="button"
              onClick={() => {
                resetForm();
                setShowCreate(true);
              }}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
            >
              <Plus className="w-4 h-4" />
              {t('projectPlanner.create')}
            </button>
          ) : null}
        </div>
      </div>

      {loading && plans.length === 0 && !disabled && !forbidden ? (
        <p className="text-sm text-slate-500">{t('projectPlanner.loading')}</p>
      ) : disabled ? (
        <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
          {t('projectPlanner.disabled')}
        </p>
      ) : forbidden ? (
        <p className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-100">
          {t('projectPlanner.forbidden')}
        </p>
      ) : (
        <>
          {overview ? (
            <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-3">
              <h2 className="text-sm font-bold">{t('projectPlanner.sections.summary')}</h2>
              <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-500">
                    {t('projectPlanner.summary.overall')}
                  </div>
                  <div className="text-3xl font-black text-indigo-600">{overview.percent}%</div>
                </div>
              </div>
              <ProgressBar percent={overview.percent} tone={progressBarTone(overview.percent)} />
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 text-sm">
                <div>
                  {t('projectPlanner.summary.plans')}: <strong>{overview.planCount}</strong>
                </div>
                <div>
                  {t('projectPlanner.summary.done')}: <strong>{overview.doneCount}</strong>
                </div>
                <div>
                  {t('projectPlanner.summary.overdue')}: <strong>{overview.overdueCount}</strong>
                </div>
                <div>
                  {t('projectPlanner.summary.dueSoon')}: <strong>{overview.dueSoonCount}</strong>
                </div>
                <div>
                  {t('projectPlanner.summary.items')}: <strong>{overview.countedItems}</strong>
                </div>
              </div>
            </section>
          ) : null}

          {overview && overview.nextDeadlines.length > 0 ? (
            <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-3">
              <h2 className="text-sm font-bold">{t('projectPlanner.sections.timeline')}</h2>
              <ul className="space-y-2">
                {overview.nextDeadlines.map((deadline) => (
                  <li key={`${deadline.planId}-${deadline.itemId}`}>
                    <Link
                      to={`/platform/project-planner/${deadline.planId}`}
                      className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 hover:border-indigo-300"
                    >
                      <div>
                        <div className="font-bold text-slate-900 dark:text-white">{deadline.title}</div>
                        <div className="text-xs text-slate-500">
                          {deadline.planTitle} · {t(`projectPlanner.templates.${deadline.contentType}`)} ·{' '}
                          {t('projectPlanner.nextDeadline', { date: formatDate(deadline.dueAt) })}
                        </div>
                      </div>
                      <span
                        className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${VARIANCE_BADGE_CLASS[deadline.badge]}`}
                      >
                        {formatVarianceLabel(deadline.badge, deadline.days, t)}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            </section>
          ) : null}

          {showCreate ? (
            <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-900">
              <h2 className="font-bold text-slate-900 dark:text-white">{t('projectPlanner.createTitle')}</h2>
              <div className="grid gap-3 md:grid-cols-2">
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.title')}</span>
                  <input
                    value={title}
                    onChange={(event) => handleTitleChange(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  />
                </label>
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.id')}</span>
                  <input
                    value={planId}
                    onChange={(event) => {
                      setIdTouched(true);
                      setPlanId(event.target.value.toLowerCase());
                    }}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950 font-mono text-xs"
                  />
                  <span className="text-[11px] text-slate-500">{t('projectPlanner.form.idHint')}</span>
                </label>
              </div>
              <label className="text-sm space-y-1 block">
                <span>{t('projectPlanner.form.description')}</span>
                <textarea
                  value={description}
                  onChange={(event) => setDescription(event.target.value)}
                  rows={2}
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                />
              </label>
              <div className="grid gap-3 md:grid-cols-2">
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.timezone')}</span>
                  <input
                    value={timezone}
                    onChange={(event) => setTimezone(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  />
                </label>
                <label className="text-sm flex items-center gap-2 pt-6">
                  <input
                    type="checkbox"
                    checked={isDefault}
                    onChange={(event) => setIsDefault(event.target.checked)}
                  />
                  <span>{t('projectPlanner.form.isDefault')}</span>
                </label>
              </div>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={creating}
                  onClick={() => void handleCreate()}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-60"
                >
                  {creating ? t('projectPlanner.actions.creating') : t('projectPlanner.actions.save')}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreate(false)}
                  className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
                >
                  {t('projectPlanner.actions.cancel')}
                </button>
              </div>
            </div>
          ) : null}

          <section className="space-y-3">
            <h2 className="text-sm font-bold">{t('projectPlanner.sections.plans')}</h2>
            {plans.length === 0 ? (
              <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                <p className="text-sm text-slate-600 dark:text-slate-300">{t('projectPlanner.empty')}</p>
                <button
                  type="button"
                  onClick={() => {
                    resetForm();
                    setShowCreate(true);
                  }}
                  className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
                >
                  <Plus className="w-4 h-4" />
                  {t('projectPlanner.emptyCta')}
                </button>
              </div>
            ) : (
              <ul className="grid gap-3 md:grid-cols-2">
                {plans.map((plan) => (
                  <li key={plan.id}>
                    <Link
                      to={`/platform/project-planner/${plan.id}`}
                      className="block rounded-xl border border-slate-200 p-4 dark:border-slate-700 hover:border-indigo-300 space-y-3"
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div>
                          <div className="font-bold text-slate-900 dark:text-white">{plan.title}</div>
                          <div className="text-xs font-mono text-slate-500">{plan.id}</div>
                        </div>
                        <div className="flex flex-col items-end gap-1">
                          {plan.isDefault ? (
                            <span className="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100">
                              {t('projectPlanner.defaultBadge')}
                            </span>
                          ) : null}
                          <div className="text-lg font-black text-indigo-600">{plan.progress.percent}%</div>
                        </div>
                      </div>
                      {plan.description ? (
                        <p className="text-xs text-slate-500 line-clamp-2">{plan.description}</p>
                      ) : null}
                      <ProgressBar
                        percent={plan.progress.percent}
                        tone={progressBarTone(plan.progress.percent)}
                      />
                      <div className="flex flex-wrap gap-3 text-[11px] text-slate-500">
                        <span>
                          {t('projectPlanner.summary.done')}: {plan.progress.doneCount}
                        </span>
                        <span>
                          {t('projectPlanner.summary.overdue')}: {plan.progress.overdueCount}
                        </span>
                        <span>
                          {t('projectPlanner.summary.dueSoon')}: {plan.progress.dueSoonCount}
                        </span>
                      </div>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </>
      )}
    </div>
  );
};
