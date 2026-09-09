import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, ListChecks, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { contentApi } from '../../api/content';
import {
  projectPlannerApi,
  type ProjectPlanContentType,
  type ProjectPlanDetail,
  type ProjectPlanItem,
  type ProjectPlanStatus,
  type ProjectPlanVarianceBadge,
} from '../../api/projectPlanner';
import type { Page } from '../../api/types';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { useToast } from '../../hooks/useToast';
import { progressBarTone } from '../../utils/projectPlanProgress';
import {
  applyContentTypeTemplate,
  bulkItemTitles,
  clampBulkItemCount,
  CONTENT_TYPE_DUE_OFFSET_DAYS,
  MAX_BULK_PLAN_ITEMS,
} from '../../utils/projectPlanTemplates';
import {
  datetimeLocalToIso,
  formatVarianceLabel,
  PLAN_STATUS_CLASS,
  PROJECT_PLAN_CONTENT_TYPES,
  VARIANCE_BADGE_CLASS,
} from '../../utils/projectPlanVariance';
import { ProgressBar } from './ProgressBar';

const STATUSES: ProjectPlanStatus[] = ['planned', 'in_progress', 'done', 'skipped', 'blocked'];

export const ProjectPlanDetailView: React.FC = () => {
  const { planId = '' } = useParams<{ planId: string }>();
  const { t, locale } = useI18n();
  const toast = useToast();
  const { settings } = useSettings();
  const [loading, setLoading] = useState(true);
  const [disabled, setDisabled] = useState(false);
  const [forbidden, setForbidden] = useState(false);
  const [missing, setMissing] = useState(false);
  const [plan, setPlan] = useState<ProjectPlanDetail | null>(null);
  const [showAdd, setShowAdd] = useState(false);
  const [saving, setSaving] = useState(false);
  const [busyItemId, setBusyItemId] = useState<string | null>(null);
  const [itemTitle, setItemTitle] = useState('');
  const [contentType, setContentType] = useState<ProjectPlanContentType>('article');
  const [phaseId, setPhaseId] = useState('');
  const [dueAt, setDueAt] = useState('');
  const [notes, setNotes] = useState('');
  const [bulkCount, setBulkCount] = useState(1);
  const [linkedSlug, setLinkedSlug] = useState('');
  const [pageOptions, setPageOptions] = useState<Page[]>([]);
  const [articleOptions, setArticleOptions] = useState<Page[]>([]);

  const settingEnabled = settings.projectPlanner?.enabled !== false;

  const varianceByItem = useMemo(() => {
    const map = new Map<string, { badge: ProjectPlanVarianceBadge; days: number }>();
    for (const variance of plan?.progress.itemVariances ?? []) {
      map.set(variance.itemId, { badge: variance.badge, days: variance.days });
    }
    return map;
  }, [plan]);

  const load = useCallback(async () => {
    if (!settingEnabled) {
      setDisabled(true);
      setPlan(null);
      setLoading(false);
      return;
    }
    if (planId === '') {
      setMissing(true);
      setLoading(false);
      return;
    }

    setLoading(true);
    setDisabled(false);
    setForbidden(false);
    setMissing(false);
    try {
      const response = await projectPlannerApi.get(planId);
      if (response.status === 403) {
        setForbidden(true);
        setPlan(null);
        return;
      }
      if (response.status === 404) {
        if (settings.projectPlanner?.enabled === false) {
          setDisabled(true);
        } else {
          setMissing(true);
        }
        setPlan(null);
        return;
      }
      if (!response.success || !response.data) {
        toast.error(response.error || t('projectPlanner.loadFailed'));
        return;
      }
      setPlan(response.data);
    } catch {
      toast.error(t('projectPlanner.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [planId, settingEnabled, settings.projectPlanner?.enabled, t, toast]);

  useEffect(() => {
    void load();
  }, [load]);

  const resetAddForm = () => {
    setItemTitle('');
    setContentType('article');
    setPhaseId(plan?.phases[0]?.id ?? '');
    setDueAt(applyContentTypeTemplate('article').dueAt);
    setNotes('');
    setBulkCount(1);
    setLinkedSlug('');
  };

  const applyType = (type: ProjectPlanContentType) => {
    const template = applyContentTypeTemplate(type);
    setContentType(template.contentType);
    setDueAt(template.dueAt);
    setLinkedSlug('');
  };

  const linkedContentPlanId = plan?.id;
  useEffect(() => {
    if (linkedContentPlanId === undefined) {
      return;
    }
    let cancelled = false;
    void Promise.all([
      contentApi.list<Page>('pages', { per_page: 100 }),
      contentApi.list<Page>('articles', { per_page: 100 }),
    ]).then(([pages, articles]) => {
      if (!cancelled) {
        setPageOptions(pages.items);
        setArticleOptions(articles.items);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [linkedContentPlanId]);

  const handleAddItem = async () => {
    if (!plan) {
      return;
    }
    const count = clampBulkItemCount(bulkCount);
    const typeLabel = t(`projectPlanner.templates.${contentType}`);
    const titles = bulkItemTitles(itemTitle, typeLabel, count);
    if (count === 1 && itemTitle.trim() === '') {
      toast.error(t('projectPlanner.toast.titleRequired'));
      return;
    }

    setSaving(true);
    try {
      let latest = plan;
      for (const title of titles) {
        const response = await projectPlannerApi.addItem(plan.id, {
          title,
          contentType,
          phaseId: phaseId === '' ? null : phaseId,
          dueAt: datetimeLocalToIso(dueAt),
          notes: notes.trim(),
          status: 'planned',
          linkedContent:
            (contentType === 'page' || contentType === 'article') && linkedSlug !== ''
              ? { type: contentType, slug: linkedSlug }
              : undefined,
        });
        if (!response.success || !response.data) {
          toast.error(response.error || t('projectPlanner.toast.saveFailed'));
          if (response.data) {
            setPlan(response.data);
          } else if (latest !== plan) {
            setPlan(latest);
          }
          return;
        }
        latest = response.data;
      }
      toast.success(t('projectPlanner.toast.itemCreated'));
      setPlan(latest);
      setShowAdd(false);
      resetAddForm();
    } finally {
      setSaving(false);
    }
  };

  const handleLinkChange = async (item: ProjectPlanItem, slug: string) => {
    if (!plan) {
      return;
    }
    const type = item.contentType === 'article' ? 'article' : 'page';
    setBusyItemId(item.id);
    try {
      const response = await projectPlannerApi.updateItem(plan.id, item.id, {
        linkedContent: { type, slug: slug === '' ? null : slug },
      });
      if (!response.success || !response.data) {
        toast.error(response.error || t('projectPlanner.toast.saveFailed'));
        return;
      }
      setPlan(response.data);
    } finally {
      setBusyItemId(null);
    }
  };

  const handleStatusChange = async (item: ProjectPlanItem, status: ProjectPlanStatus) => {
    if (!plan) {
      return;
    }
    setBusyItemId(item.id);
    try {
      const response = await projectPlannerApi.updateItem(plan.id, item.id, { status });
      if (!response.success || !response.data) {
        toast.error(response.error || t('projectPlanner.toast.saveFailed'));
        return;
      }
      setPlan(response.data);
    } finally {
      setBusyItemId(null);
    }
  };

  const handleDeleteItem = async (item: ProjectPlanItem) => {
    if (!plan) {
      return;
    }
    if (!window.confirm(t('projectPlanner.confirm.deleteItem'))) {
      return;
    }
    setBusyItemId(item.id);
    try {
      const response = await projectPlannerApi.removeItem(plan.id, item.id);
      if (!response.success || !response.data) {
        toast.error(response.error || t('projectPlanner.toast.deleteFailed'));
        return;
      }
      toast.success(t('projectPlanner.toast.itemDeleted'));
      setPlan(response.data);
    } finally {
      setBusyItemId(null);
    }
  };

  const formatDate = (iso: string | null) => {
    if (!iso) {
      return '—';
    }
    return new Date(iso).toLocaleString(locale, { dateStyle: 'medium', timeStyle: 'short' });
  };

  const renderItemRow = (item: ProjectPlanItem) => {
    const variance = varianceByItem.get(item.id);
    const badge = variance?.badge ?? 'none';
    const days = variance?.days ?? 0;

    return (
      <tr key={item.id} className="border-t border-slate-200 dark:border-slate-800">
        <td className="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">{item.title}</td>
        <td className="px-3 py-2 text-xs text-slate-500">{t(`projectPlanner.templates.${item.contentType}`)}</td>
        <td className="px-3 py-2 text-xs text-slate-500 whitespace-nowrap">{formatDate(item.dueAt)}</td>
        <td className="px-3 py-2 text-xs">
          {item.contentType === 'page' || item.contentType === 'article' ? (
            <div className="flex flex-col gap-1 min-w-[10rem]">
              {item.linkedContent.slug ? (
                <Link
                  to={item.contentType === 'article' ? `/articles/${item.linkedContent.slug}` : `/pages/${item.linkedContent.slug}`}
                  className="text-indigo-600 hover:underline font-mono"
                >
                  {item.linkedContent.slug}
                </Link>
              ) : (
                <span className="text-slate-400">{t('projectPlanner.link.none')}</span>
              )}
              <select
                value={item.linkedContent.slug ?? ''}
                disabled={busyItemId === item.id}
                onChange={(event) => void handleLinkChange(item, event.target.value)}
                className="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1 bg-white dark:bg-slate-950 text-[11px]"
              >
                <option value="">{t('projectPlanner.link.choose')}</option>
                {(item.contentType === 'article' ? articleOptions : pageOptions).map((option) => (
                    <option key={option.slug} value={option.slug}>
                      {option.title}
                    </option>
                  ))}
              </select>
            </div>
          ) : (
            <span className="text-slate-400">—</span>
          )}
        </td>
        <td className="px-3 py-2">
          <select
            value={item.status}
            disabled={busyItemId === item.id}
            onChange={(event) => void handleStatusChange(item, event.target.value as ProjectPlanStatus)}
            className={`rounded-full px-2 py-1 text-[11px] font-bold ${PLAN_STATUS_CLASS[item.status] ?? PLAN_STATUS_CLASS.planned}`}
          >
            {STATUSES.map((status) => (
              <option key={status} value={status}>
                {t(`projectPlanner.status.${status}`)}
              </option>
            ))}
          </select>
        </td>
        <td className="px-3 py-2">
          <span className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${VARIANCE_BADGE_CLASS[badge]}`}>
            {formatVarianceLabel(badge, days, t)}
          </span>
        </td>
        <td className="px-3 py-2 text-right">
          <button
            type="button"
            disabled={busyItemId === item.id}
            onClick={() => void handleDeleteItem(item)}
            className="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800"
            title={t('projectPlanner.actions.deleteItem')}
          >
            <Trash2 className="w-3.5 h-3.5" />
          </button>
        </td>
      </tr>
    );
  };

  const itemsByPhase = useMemo(() => {
    const knownPhaseIds = new Set((plan?.phases ?? []).map((phase) => phase.id));
    const grouped = new Map<string | null, ProjectPlanItem[]>();
    for (const item of plan?.items ?? []) {
      const key = item.phaseId && knownPhaseIds.has(item.phaseId) ? item.phaseId : null;
      const list = grouped.get(key) ?? [];
      list.push(item);
      grouped.set(key, list);
    }
    return grouped;
  }, [plan]);

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <Link
            to="/platform/project-planner"
            className="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 mb-2"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            {t('projectPlanner.backToList')}
          </Link>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <ListChecks className="w-7 h-7 text-indigo-600" />
            {plan?.title ?? t('projectPlanner.title')}
          </h1>
          {plan ? (
            <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">
              <span className="font-mono text-xs">{plan.id}</span>
              {plan.description ? ` · ${plan.description}` : ''}
              {` · ${plan.timezone}`}
            </p>
          ) : (
            <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('projectPlanner.subtitle')}</p>
          )}
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
          {plan ? (
            <button
              type="button"
              onClick={() => {
                resetAddForm();
                setShowAdd(true);
              }}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
            >
              <Plus className="w-4 h-4" />
              {t('projectPlanner.actions.addItem')}
            </button>
          ) : null}
        </div>
      </div>

      {loading && !plan && !disabled && !forbidden && !missing ? (
        <p className="text-sm text-slate-500">{t('projectPlanner.loading')}</p>
      ) : disabled ? (
        <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
          {t('projectPlanner.disabled')}
        </p>
      ) : forbidden ? (
        <p className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-100">
          {t('projectPlanner.forbidden')}
        </p>
      ) : missing ? (
        <p className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-900">
          {t('projectPlanner.notFound')}
        </p>
      ) : plan ? (
        <>
          <section className="rounded-xl border border-slate-200 p-4 dark:border-slate-700 space-y-3">
            <div className="flex flex-wrap items-end justify-between gap-3">
              <div>
                <div className="text-xs uppercase tracking-wide text-slate-500">
                  {t('projectPlanner.summary.overall')}
                </div>
                <div className="text-3xl font-black text-indigo-600">{plan.progress.percent}%</div>
              </div>
              {plan.isDefault ? (
                <span className="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-bold text-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100">
                  {t('projectPlanner.defaultBadge')}
                </span>
              ) : null}
            </div>
            <ProgressBar percent={plan.progress.percent} tone={progressBarTone(plan.progress.percent)} />
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 text-sm">
              <div>
                {t('projectPlanner.summary.done')}: <strong>{plan.progress.doneCount}</strong>
              </div>
              <div>
                {t('projectPlanner.summary.overdue')}: <strong>{plan.progress.overdueCount}</strong>
              </div>
              <div>
                {t('projectPlanner.summary.dueSoon')}: <strong>{plan.progress.dueSoonCount}</strong>
              </div>
              <div>
                {t('projectPlanner.summary.items')}: <strong>{plan.progress.countedItems}</strong>
              </div>
            </div>
          </section>

          {showAdd ? (
            <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-900">
              <h2 className="font-bold text-slate-900 dark:text-white">{t('projectPlanner.actions.addItemTitle')}</h2>
              <div className="flex flex-wrap gap-2">
                {PROJECT_PLAN_CONTENT_TYPES.map((type) => (
                  <button
                    key={type}
                    type="button"
                    onClick={() => applyType(type)}
                    className={`rounded-full px-3 py-1 text-xs font-bold border ${
                      contentType === type
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'border-slate-300 text-slate-700 dark:border-slate-600 dark:text-slate-200'
                    }`}
                  >
                    {t(`projectPlanner.templates.${type}`)}
                  </button>
                ))}
              </div>
              <p className="text-[11px] text-slate-500">
                {CONTENT_TYPE_DUE_OFFSET_DAYS[contentType] === null
                  ? t('projectPlanner.templates.offsetNone')
                  : t('projectPlanner.templates.offsetHint', {
                      days: CONTENT_TYPE_DUE_OFFSET_DAYS[contentType] ?? 0,
                    })}
              </p>
              <label className="text-sm space-y-1 block">
                <span>{t('projectPlanner.form.itemTitle')}</span>
                <input
                  value={itemTitle}
                  onChange={(event) => setItemTitle(event.target.value)}
                  className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                />
              </label>
              <h3 className="text-sm font-bold text-slate-900 dark:text-white">{t('projectPlanner.bulk.heading')}</h3>
              <div className="grid gap-3 md:grid-cols-2">
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.phase')}</span>
                  <select
                    value={phaseId}
                    onChange={(event) => setPhaseId(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  >
                    <option value="">{t('projectPlanner.form.noPhase')}</option>
                    {plan.phases.map((phase) => (
                      <option key={phase.id} value={phase.id}>
                        {phase.title}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.bulk.count')}</span>
                  <input
                    type="number"
                    min={1}
                    max={MAX_BULK_PLAN_ITEMS}
                    value={bulkCount}
                    onChange={(event) => setBulkCount(clampBulkItemCount(Number(event.target.value)))}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  />
                  <span className="text-[11px] text-slate-500">{t('projectPlanner.bulk.help')}</span>
                </label>
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.dueAt')}</span>
                  <input
                    type="datetime-local"
                    value={dueAt}
                    onChange={(event) => setDueAt(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  />
                </label>
                <label className="text-sm space-y-1">
                  <span>{t('projectPlanner.form.notes')}</span>
                  <input
                    value={notes}
                    onChange={(event) => setNotes(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  />
                </label>
              </div>
              {(contentType === 'page' || contentType === 'article') && bulkCount === 1 ? (
                <label className="text-sm space-y-1 block">
                  <span>{t('projectPlanner.form.linkedContent')}</span>
                  <select
                    value={linkedSlug}
                    onChange={(event) => setLinkedSlug(event.target.value)}
                    className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
                  >
                    <option value="">{t('projectPlanner.link.choose')}</option>
                    {(contentType === 'article' ? articleOptions : pageOptions).map((option) => (
                      <option key={option.slug} value={option.slug}>
                        {option.title} ({option.slug})
                      </option>
                    ))}
                  </select>
                  <span className="text-[11px] text-slate-500">{t('projectPlanner.link.help')}</span>
                </label>
              ) : null}
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={saving}
                  onClick={() => void handleAddItem()}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-60"
                >
                  {saving ? t('projectPlanner.actions.saving') : t('projectPlanner.actions.save')}
                </button>
                <button
                  type="button"
                  onClick={() => setShowAdd(false)}
                  className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
                >
                  {t('projectPlanner.actions.cancel')}
                </button>
              </div>
            </div>
          ) : null}

          {plan.phases.length === 0 && (plan.items.length === 0) ? (
            <div className="rounded-xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
              <p className="text-sm text-slate-600 dark:text-slate-300">{t('projectPlanner.empty')}</p>
            </div>
          ) : (
            <div className="space-y-4">
              {plan.phases.map((phase) => {
                const rows = itemsByPhase.get(phase.id) ?? [];
                return (
                  <section
                    key={phase.id}
                    className="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                  >
                    <div className="px-4 py-3 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-between">
                      <h2 className="text-sm font-bold">{phase.title}</h2>
                      <span className="text-[11px] text-slate-500">{rows.length}</span>
                    </div>
                    {rows.length === 0 ? (
                      <p className="px-4 py-3 text-xs text-slate-500">{t('projectPlanner.noItems')}</p>
                    ) : (
                      <div className="overflow-x-auto">
                        <table className="w-full text-left">
                          <thead className="text-[11px] uppercase tracking-wide text-slate-500">
                            <tr>
                              <th className="px-3 py-2">{t('projectPlanner.form.itemTitle')}</th>
                              <th className="px-3 py-2">{t('projectPlanner.form.contentType')}</th>
                              <th className="px-3 py-2">{t('projectPlanner.form.dueAt')}</th>
                              <th className="px-3 py-2">{t('projectPlanner.form.linkedContent')}</th>
                              <th className="px-3 py-2">{t('projectPlanner.form.status')}</th>
                              <th className="px-3 py-2">{t('projectPlanner.sections.summary')}</th>
                              <th className="px-3 py-2" />
                            </tr>
                          </thead>
                          <tbody>{rows.map(renderItemRow)}</tbody>
                        </table>
                      </div>
                    )}
                  </section>
                );
              })}

              {(itemsByPhase.get(null) ?? []).length > 0 ? (
                <section className="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                  <div className="px-4 py-3 bg-slate-50 dark:bg-slate-900/60">
                    <h2 className="text-sm font-bold">{t('projectPlanner.sections.ungrouped')}</h2>
                  </div>
                  <div className="overflow-x-auto">
                    <table className="w-full text-left">
                      <thead className="text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                          <th className="px-3 py-2">{t('projectPlanner.form.itemTitle')}</th>
                          <th className="px-3 py-2">{t('projectPlanner.form.contentType')}</th>
                          <th className="px-3 py-2">{t('projectPlanner.form.dueAt')}</th>
                          <th className="px-3 py-2">{t('projectPlanner.form.linkedContent')}</th>
                          <th className="px-3 py-2">{t('projectPlanner.form.status')}</th>
                          <th className="px-3 py-2">{t('projectPlanner.sections.summary')}</th>
                          <th className="px-3 py-2" />
                        </tr>
                      </thead>
                      <tbody>{(itemsByPhase.get(null) ?? []).map(renderItemRow)}</tbody>
                    </table>
                  </div>
                </section>
              ) : null}
            </div>
          )}
        </>
      ) : null}
    </div>
  );
};
