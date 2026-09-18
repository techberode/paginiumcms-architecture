import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { CalendarDays, ChevronLeft, ChevronRight, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { eventsApi, type EventStatus, type SiteEvent } from '../../api/events';
import { projectPlannerApi, type ProjectPlanSummary } from '../../api/projectPlanner';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { AdminHintCard } from './AdminHintCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminDataTable } from '../ui/AdminDataTable';
import { AdminFormActions } from './AdminFormActions';
import { AdminTabs } from '../ui/AdminTabs';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import {
  datetimeLocalToUnix,
  eventTouchesDay,
  monthCells,
  nextHourDatetimeLocal,
  shiftMonth,
  unixToDatetimeLocal,
} from '../../utils/siteEvents';

type ViewMode = 'table' | 'calendar';
type StatusFilter = EventStatus | 'all';

const EMPTY_DRAFT = {
  title: '',
  slug: '',
  startsAt: '',
  endsAt: '',
  location: '',
  body: '',
  status: 'draft' as EventStatus,
  projectPlanId: '',
};

const WEEKDAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as const;

function formatWhen(unix: number, locale: string): string {
  return new Intl.DateTimeFormat(locale === 'sk' ? 'sk-SK' : 'en-GB', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(unix * 1000));
}

export const EventsManager: React.FC = () => {
  const { t, locale } = useI18n();
  const confirmDestructive = useAdminConfirm();
  const toast = useToast();
  const now = new Date();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [events, setEvents] = useState<SiteEvent[]>([]);
  const [plans, setPlans] = useState<ProjectPlanSummary[]>([]);
  const [viewMode, setViewMode] = useState<ViewMode>('table');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const [draft, setDraft] = useState(EMPTY_DRAFT);
  const [month, setMonth] = useState({ year: now.getFullYear(), monthIndex: now.getMonth() });

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [index, planResponse] = await Promise.all([eventsApi.list(), projectPlannerApi.list()]);
      setEvents(index.events);
      if (planResponse.success && planResponse.data) {
        setPlans(planResponse.data.plans ?? []);
      }
    } catch {
      toast.error(t('platform.events.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const visible = useMemo(
    () => (statusFilter === 'all' ? events : events.filter((event) => event.status === statusFilter)),
    [events, statusFilter]
  );

  const selected = events.find((event) => event.id === selectedId) ?? null;
  const editing = creating || selected !== null;
  const cells = useMemo(() => monthCells(month.year, month.monthIndex), [month]);
  const monthLabel = new Intl.DateTimeFormat(locale === 'sk' ? 'sk-SK' : 'en-GB', {
    month: 'long',
    year: 'numeric',
  }).format(new Date(month.year, month.monthIndex, 1));

  const startCreate = () => {
    setCreating(true);
    setSelectedId(null);
    setDraft({ ...EMPTY_DRAFT, startsAt: nextHourDatetimeLocal() });
  };

  const openEvent = (event: SiteEvent) => {
    setCreating(false);
    setSelectedId(event.id);
    setDraft({
      title: event.title,
      slug: event.slug,
      startsAt: unixToDatetimeLocal(event.startsAt),
      endsAt: unixToDatetimeLocal(event.endsAt),
      location: event.location,
      body: event.body,
      status: event.status,
      projectPlanId: event.projectPlanId ?? '',
    });
  };

  const handleSave = async () => {
    const title = draft.title.trim();
    const startsAt = datetimeLocalToUnix(draft.startsAt);
    if (title === '') {
      toast.error(t('platform.events.toast.titleRequired'));
      return;
    }
    if (startsAt === null) {
      toast.error(t('platform.events.toast.startRequired'));
      return;
    }

    const payload = {
      title,
      slug: draft.slug.trim(),
      startsAt,
      endsAt: datetimeLocalToUnix(draft.endsAt),
      location: draft.location.trim(),
      body: draft.body,
      status: draft.status,
      projectPlanId: draft.projectPlanId.trim() === '' ? null : draft.projectPlanId.trim(),
    };

    setSaving(true);
    try {
      const response = creating
        ? await eventsApi.create(payload)
        : selected
          ? await eventsApi.update(selected.id, payload)
          : null;
      if (!response?.success) {
        toast.error(response?.error || response?.message || t('platform.events.toast.saveFailed'));
        return;
      }
      toast.success(creating ? t('platform.events.toast.created') : t('platform.events.toast.updated'));
      setCreating(false);
      setSelectedId(response.data?.event.id ?? null);
      await load();
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (event: SiteEvent) => {
    if (!(await confirmDestructive(t('platform.events.confirmDelete', { title: event.title })))) {
      return;
    }
    const ok = await eventsApi.remove(event.id);
    if (!ok) {
      toast.error(t('platform.events.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.events.toast.deleted'));
    if (selectedId === event.id) {
      setSelectedId(null);
      setCreating(false);
    }
    await load();
  };

  const statusLabel = (status: EventStatus) => t(`platform.events.status.${status}`);
  const planTitle = (planId: string | null) =>
    planId ? (plans.find((plan) => plan.id === planId)?.title ?? planId) : t('platform.events.planNone');

  return (
    <div className="p-6 space-y-6" data-testid="events-manager">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
            <CalendarDays className="w-7 h-7 text-admin-primary" />
            {t('platform.events.title')}
          </h1>
          <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.events.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-admin-border text-sm text-admin-text"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.events.refresh')}
          </button>
          <button
            type="button"
            onClick={startCreate}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-admin-primary text-white text-sm"
          >
            <Plus className="w-4 h-4" />
            {t('platform.events.create')}
          </button>
        </div>
      </div>

      <AdminHintCard title={t('platform.events.hintTitle')}>{t('platform.events.hint')}</AdminHintCard>

      <div className="flex flex-wrap gap-4">
        <AdminTabs
          ariaLabel={t('platform.events.viewTable')}
          activeId={viewMode}
          onSelect={(id) => setViewMode(id as ViewMode)}
          items={[
            { id: 'table', label: t('platform.events.viewTable'), testId: 'events-view-table' },
            { id: 'calendar', label: t('platform.events.viewCalendar'), testId: 'events-view-calendar' },
          ]}
        />
        <AdminTabs
          ariaLabel={t('platform.events.filterAll')}
          activeId={statusFilter}
          onSelect={(id) => setStatusFilter(id as StatusFilter)}
          items={[
            { id: 'all', label: t('platform.events.filterAll') },
            { id: 'draft', label: statusLabel('draft') },
            { id: 'published', label: statusLabel('published') },
          ]}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div className="space-y-4">
          {loading ? (
            <p className="text-sm text-admin-muted">{t('platform.events.loading')}</p>
          ) : viewMode === 'table' ? (
            <AdminDataTable
              columns={[
                t('platform.events.titleField'),
                t('platform.events.when'),
                t('platform.events.location'),
                t('platform.events.statusLabel'),
                t('platform.events.plan'),
              ]}
              empty={visible.length === 0 ? t('platform.events.empty') : undefined}
            >
              {visible.map((event) => (
                <tr
                  key={event.id}
                  data-testid={`event-row-${event.id}`}
                  className={`admin-row-hover cursor-pointer ${selectedId === event.id ? 'bg-admin-sidebar-active' : ''}`}
                  onClick={() => openEvent(event)}
                >
                  <td className="px-4 py-3 font-medium">{event.title}</td>
                  <td className="px-4 py-3 text-admin-muted">{formatWhen(event.startsAt, locale)}</td>
                  <td className="px-4 py-3 text-admin-muted">{event.location || t('platform.events.locationEmpty')}</td>
                  <td className="px-4 py-3">{statusLabel(event.status)}</td>
                  <td className="px-4 py-3 text-admin-muted">{planTitle(event.projectPlanId)}</td>
                </tr>
              ))}
            </AdminDataTable>
          ) : (
            <div className="space-y-3" data-testid="events-calendar">
              <div className="flex items-center justify-between gap-3">
                <button
                  type="button"
                  className="p-2 rounded-lg border border-admin-border text-admin-text"
                  aria-label={t('platform.events.calendar.prev')}
                  onClick={() => setMonth((current) => shiftMonth(current.year, current.monthIndex, -1))}
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>
                <p className="text-sm font-semibold capitalize text-admin-text">{monthLabel}</p>
                <button
                  type="button"
                  className="p-2 rounded-lg border border-admin-border text-admin-text"
                  aria-label={t('platform.events.calendar.next')}
                  onClick={() => setMonth((current) => shiftMonth(current.year, current.monthIndex, 1))}
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
              <div className="grid grid-cols-7 gap-1 text-center text-[11px] uppercase tracking-wide text-admin-muted">
                {WEEKDAY_KEYS.map((key) => (
                  <div key={key}>{t(`platform.events.weekday.${key}`)}</div>
                ))}
              </div>
              <div className="grid grid-cols-7 gap-1">
                {cells.map((cell) => {
                  const dayEvents = visible.filter((event) =>
                    eventTouchesDay(event, cell.year, cell.monthIndex, cell.day)
                  );
                  return (
                    <div
                      key={`${cell.year}-${cell.monthIndex}-${cell.day}`}
                      className={`min-h-[5.5rem] rounded-lg border border-admin-border p-1.5 text-left ${
                        cell.inMonth ? 'bg-admin-card' : 'bg-admin-canvas opacity-60'
                      }`}
                    >
                      <span className="text-xs text-admin-muted">{cell.day}</span>
                      <div className="mt-1 space-y-1">
                        {dayEvents.map((event) => (
                          <button
                            key={event.id}
                            type="button"
                            className="block w-full truncate rounded px-1 py-0.5 text-left text-[11px] bg-admin-sidebar-active text-admin-sidebar-active-text"
                            onClick={() => openEvent(event)}
                          >
                            {event.title}
                          </button>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {editing ? (
          <AdminWidgetCard title={creating ? t('platform.events.create') : t('platform.events.edit')}>
            <div className="space-y-4">
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.titleField')}</span>
                <input
                  className={ADMIN_INPUT}
                  data-testid="event-title"
                  value={draft.title}
                  onChange={(event) => setDraft((current) => ({ ...current, title: event.target.value }))}
                />
              </label>
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.slug')}</span>
                <input
                  className={`${ADMIN_INPUT} font-mono`}
                  value={draft.slug}
                  onChange={(event) => setDraft((current) => ({ ...current, slug: event.target.value }))}
                  placeholder={t('platform.events.slugHint')}
                />
              </label>
              <div className="grid gap-3 sm:grid-cols-2">
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.events.startsAt')}</span>
                  <input
                    type="datetime-local"
                    className={ADMIN_INPUT}
                    data-testid="event-starts"
                    value={draft.startsAt}
                    onChange={(event) => setDraft((current) => ({ ...current, startsAt: event.target.value }))}
                  />
                </label>
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.events.endsAt')}</span>
                  <input
                    type="datetime-local"
                    className={ADMIN_INPUT}
                    value={draft.endsAt}
                    onChange={(event) => setDraft((current) => ({ ...current, endsAt: event.target.value }))}
                  />
                </label>
              </div>
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.location')}</span>
                <input
                  className={ADMIN_INPUT}
                  value={draft.location}
                  onChange={(event) => setDraft((current) => ({ ...current, location: event.target.value }))}
                />
              </label>
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.statusLabel')}</span>
                <select
                  className={ADMIN_INPUT}
                  data-testid="event-status"
                  value={draft.status}
                  onChange={(event) =>
                    setDraft((current) => ({ ...current, status: event.target.value as EventStatus }))
                  }
                >
                  <option value="draft">{statusLabel('draft')}</option>
                  <option value="published">{statusLabel('published')}</option>
                </select>
              </label>
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.plan')}</span>
                <select
                  className={ADMIN_INPUT}
                  value={draft.projectPlanId}
                  onChange={(event) => setDraft((current) => ({ ...current, projectPlanId: event.target.value }))}
                >
                  <option value="">{t('platform.events.planNone')}</option>
                  {plans.map((plan) => (
                    <option key={plan.id} value={plan.id}>
                      {plan.title}
                    </option>
                  ))}
                </select>
              </label>
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.events.body')}</span>
                <textarea
                  className={`${ADMIN_INPUT} min-h-[8rem] font-mono`}
                  value={draft.body}
                  onChange={(event) => setDraft((current) => ({ ...current, body: event.target.value }))}
                />
              </label>
              <AdminFormActions
                onSave={() => void handleSave()}
                saveLabel={saving ? t('platform.events.saving') : t('platform.events.save')}
                saveDisabled={saving}
                saveBusy={saving}
                saveTestId="event-save"
                extra={
                  <>
                    <button
                      type="button"
                      className="px-3 py-2 text-sm rounded-lg border border-admin-border text-admin-text"
                      onClick={() => {
                        setCreating(false);
                        setSelectedId(null);
                        setDraft(EMPTY_DRAFT);
                      }}
                    >
                      {t('platform.events.cancel')}
                    </button>
                    {selected ? (
                      <button
                        type="button"
                        className="px-3 py-2 text-sm rounded-lg border border-rose-200 text-rose-700"
                        onClick={() => void handleDelete(selected)}
                        aria-label={t('platform.events.delete')}
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    ) : null}
                  </>
                }
              />
            </div>
          </AdminWidgetCard>
        ) : (
          <p className="text-sm text-admin-muted">{t('platform.events.selectHint')}</p>
        )}
      </div>
    </div>
  );
};

export default EventsManager;
