import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Play, Square, Timer, Trash2 } from 'lucide-react';
import {
  timeEntriesApi,
  type TimeEntry,
  type TimeTarget,
  type TimeTargetEvent,
  type TimeTargetPlan,
} from '../../api/timeEntries';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { AdminHintCard } from './AdminHintCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminDataTable } from '../ui/AdminDataTable';
import { AdminTabs } from '../ui/AdminTabs';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { elapsedSeconds, formatDuration, startOfLocalDay, startOfLocalWeek } from '../../utils/timeTracker';
import { ComingSoonPanel } from './ComingSoonPanel';

type RangeFilter = 'today' | 'week';

export const TimeTrackerView: React.FC = () => {
  const { t } = useI18n();
  const confirmDestructive = useAdminConfirm();
  const toast = useToast();
  const [now, setNow] = useState(() => Math.floor(Date.now() / 1000));
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [entries, setEntries] = useState<TimeEntry[]>([]);
  const [running, setRunning] = useState<TimeEntry | null>(null);
  const [todaySeconds, setTodaySeconds] = useState(0);
  const [weekSeconds, setWeekSeconds] = useState(0);
  const [teamToday, setTeamToday] = useState<number | null>(null);
  const [canSeeTeam, setCanSeeTeam] = useState(false);
  const [events, setEvents] = useState<TimeTargetEvent[]>([]);
  const [plans, setPlans] = useState<TimeTargetPlan[]>([]);
  const [target, setTarget] = useState<TimeTarget>('planItem');
  const [planId, setPlanId] = useState('');
  const [planItemId, setPlanItemId] = useState('');
  const [eventId, setEventId] = useState('');
  const [note, setNote] = useState('');
  const [range, setRange] = useState<RangeFilter>('today');
  const [planFilter, setPlanFilter] = useState('');
  const [panel, setPanel] = useState<'timer' | 'comingSoon'>('timer');
  const [contentKind, setContentKind] = useState<'page' | 'article'>('page');
  const [contentSlug, setContentSlug] = useState('');
  const [pages, setPages] = useState<Array<{ slug: string; title: string }>>([]);
  const [articles, setArticles] = useState<Array<{ slug: string; title: string }>>([]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [index, catalog] = await Promise.all([
        timeEntriesApi.list(planFilter ? { planId: planFilter } : undefined),
        timeEntriesApi.targets(),
      ]);
      setEntries(index.entries);
      setRunning(index.running);
      setTodaySeconds(index.summary.todaySeconds);
      setWeekSeconds(index.summary.weekSeconds);
      setTeamToday(index.canSeeTeam ? (index.summary.teamTodaySeconds ?? 0) : null);
      setCanSeeTeam(index.canSeeTeam);
      setEvents(catalog.events);
      setPlans(catalog.plans);
      setPages(catalog.pages ?? []);
      setArticles(catalog.articles ?? []);
      if (planId === '' && catalog.plans[0]) {
        setPlanId(catalog.plans[0].id);
        setPlanItemId(catalog.plans[0].items[0]?.id ?? '');
      }
      if (eventId === '' && catalog.events[0]) {
        setEventId(catalog.events[0].id);
      }
      if (contentSlug === '' && catalog.pages[0]) {
        setContentSlug(catalog.pages[0].slug);
      }
    } catch {
      toast.error(t('platform.timeTracker.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [planFilter, toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (!running) {
      return undefined;
    }
    const timer = window.setInterval(() => setNow(Math.floor(Date.now() / 1000)), 1000);
    return () => window.clearInterval(timer);
  }, [running]);

  const selectedPlan = plans.find((plan) => plan.id === planId) ?? plans[0] ?? null;
  const liveSeconds = running ? elapsedSeconds(running.startedAt, running.endedAt, now) : 0;

  const visibleEntries = useMemo(() => {
    const start = range === 'today' ? startOfLocalDay(new Date()) : startOfLocalWeek(new Date());
    return entries.filter((entry) => entry.startedAt >= start);
  }, [entries, range]);

  const handleStart = async () => {
    const payload =
      target === 'event'
        ? { target, eventId, note: note.trim() }
        : target === 'content'
          ? { target, contentKind, contentSlug, note: note.trim() }
          : { target, planId, planItemId, note: note.trim() };
    if (target === 'event' && eventId === '') {
      toast.error(t('platform.timeTracker.toast.targetRequired'));
      return;
    }
    if (target === 'content' && contentSlug === '') {
      toast.error(t('platform.timeTracker.toast.targetRequired'));
      return;
    }
    if (target === 'planItem' && (planId === '' || planItemId === '')) {
      toast.error(t('platform.timeTracker.toast.targetRequired'));
      return;
    }
    setBusy(true);
    try {
      const response = await timeEntriesApi.start(payload);
      if (!response.success) {
        toast.error(response.error || response.message || t('platform.timeTracker.toast.startFailed'));
        return;
      }
      toast.success(t('platform.timeTracker.toast.started'));
      setNote('');
      await load();
    } finally {
      setBusy(false);
    }
  };

  const handleStop = async () => {
    setBusy(true);
    try {
      const response = await timeEntriesApi.stop(note.trim() || undefined);
      if (!response.success) {
        toast.error(response.error || response.message || t('platform.timeTracker.toast.stopFailed'));
        return;
      }
      toast.success(t('platform.timeTracker.toast.stopped'));
      setNote('');
      await load();
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async (entry: TimeEntry) => {
    if (!(await confirmDestructive(t('platform.timeTracker.confirmDelete')))) {
      return;
    }
    const ok = await timeEntriesApi.remove(entry.id);
    if (!ok) {
      toast.error(t('platform.timeTracker.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.timeTracker.toast.deleted'));
    await load();
  };

  return (
    <div className="p-6 space-y-6" data-testid="time-tracker">
      <div>
        <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
          <Timer className="w-7 h-7 text-admin-primary" />
          {t('platform.timeTracker.title')}
        </h1>
        <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.timeTracker.subtitle')}</p>
      </div>

      <AdminTabs
        className="mt-2"
        activeId={panel}
        onSelect={(id) => setPanel(id === 'comingSoon' ? 'comingSoon' : 'timer')}
        items={[
          { id: 'timer', label: t('platform.timeTracker.timer'), testId: 'time-tab-timer' },
          { id: 'comingSoon', label: t('platform.comingSoon.tab'), testId: 'time-tab-coming-soon' },
        ]}
      />

      {panel === 'comingSoon' ? <ComingSoonPanel /> : null}

      {panel === 'timer' ? (
      <>
      <AdminHintCard title={t('platform.timeTracker.hintTitle')}>{t('platform.timeTracker.hint')}</AdminHintCard>

      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
        <AdminWidgetCard title={t('platform.timeTracker.timer')}>
          <div className="space-y-4">
            <p className="font-mono text-4xl font-semibold tabular-nums text-admin-text" data-testid="time-tracker-clock">
              {formatDuration(running ? liveSeconds : 0)}
            </p>
            <p className="text-sm text-admin-muted">
              {running
                ? t('platform.timeTracker.runningOn', { label: running.label || t('platform.timeTracker.untitled') })
                : t('platform.timeTracker.idle')}
            </p>
            <label className="block space-y-1 text-sm">
              <span className="text-admin-muted">{t('platform.timeTracker.target')}</span>
              <select
                className={ADMIN_INPUT}
                data-testid="time-target"
                disabled={running !== null}
                value={target}
                onChange={(event) => setTarget(event.target.value as TimeTarget)}
              >
                <option value="planItem">{t('platform.timeTracker.targets.planItem')}</option>
                <option value="event">{t('platform.timeTracker.targets.event')}</option>
                <option value="content">{t('platform.timeTracker.targets.content')}</option>
              </select>
            </label>
            {target === 'planItem' ? (
              <>
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.timeTracker.plan')}</span>
                  <select
                    className={ADMIN_INPUT}
                    disabled={running !== null}
                    value={planId}
                    onChange={(event) => {
                      const next = event.target.value;
                      setPlanId(next);
                      const plan = plans.find((item) => item.id === next);
                      setPlanItemId(plan?.items[0]?.id ?? '');
                    }}
                  >
                    {plans.length === 0 ? <option value="">{t('platform.timeTracker.noPlans')}</option> : null}
                    {plans.map((plan) => (
                      <option key={plan.id} value={plan.id}>
                        {plan.title}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.timeTracker.planItem')}</span>
                  <select
                    className={ADMIN_INPUT}
                    data-testid="time-plan-item"
                    disabled={running !== null}
                    value={planItemId}
                    onChange={(event) => setPlanItemId(event.target.value)}
                  >
                    {(selectedPlan?.items ?? []).map((item) => (
                      <option key={item.id} value={item.id}>
                        {item.title}
                      </option>
                    ))}
                  </select>
                </label>
              </>
            ) : target === 'content' ? (
              <>
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.comingSoon.kind')}</span>
                  <select
                    className={ADMIN_INPUT}
                    disabled={running !== null}
                    value={contentKind}
                    onChange={(event) => {
                      const next = event.target.value as 'page' | 'article';
                      setContentKind(next);
                      const catalog = next === 'article' ? articles : pages;
                      setContentSlug(catalog[0]?.slug ?? '');
                    }}
                  >
                    <option value="page">{t('platform.comingSoon.kinds.page')}</option>
                    <option value="article">{t('platform.comingSoon.kinds.article')}</option>
                  </select>
                </label>
                <label className="block space-y-1 text-sm">
                  <span className="text-admin-muted">{t('platform.comingSoon.target')}</span>
                  <select
                    className={ADMIN_INPUT}
                    data-testid="time-content"
                    disabled={running !== null}
                    value={contentSlug}
                    onChange={(event) => setContentSlug(event.target.value)}
                  >
                    {(contentKind === 'article' ? articles : pages).map((item) => (
                      <option key={item.slug} value={item.slug}>
                        {item.title}
                      </option>
                    ))}
                  </select>
                </label>
              </>
            ) : (
              <label className="block space-y-1 text-sm">
                <span className="text-admin-muted">{t('platform.timeTracker.event')}</span>
                <select
                  className={ADMIN_INPUT}
                  data-testid="time-event"
                  disabled={running !== null}
                  value={eventId}
                  onChange={(event) => setEventId(event.target.value)}
                >
                  {events.length === 0 ? <option value="">{t('platform.timeTracker.noEvents')}</option> : null}
                  {events.map((event) => (
                    <option key={event.id} value={event.id}>
                      {event.title}
                    </option>
                  ))}
                </select>
              </label>
            )}
            <label className="block space-y-1 text-sm">
              <span className="text-admin-muted">{t('platform.timeTracker.note')}</span>
              <input
                className={ADMIN_INPUT}
                value={note}
                onChange={(event) => setNote(event.target.value)}
              />
            </label>
            {running ? (
              <button
                type="button"
                className="btn btn-primary inline-flex items-center gap-2"
                data-testid="time-stop"
                disabled={busy}
                onClick={() => void handleStop()}
              >
                <Square className="w-4 h-4" />
                {t('platform.timeTracker.stop')}
              </button>
            ) : (
              <button
                type="button"
                className="btn btn-primary inline-flex items-center gap-2"
                data-testid="time-start"
                disabled={busy}
                onClick={() => void handleStart()}
              >
                <Play className="w-4 h-4" />
                {t('platform.timeTracker.start')}
              </button>
            )}
          </div>
        </AdminWidgetCard>

        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-3">
            <AdminWidgetCard title={t('platform.timeTracker.today')}>
              <p className="font-mono text-2xl tabular-nums">{formatDuration(todaySeconds + (running ? liveSeconds : 0))}</p>
            </AdminWidgetCard>
            <AdminWidgetCard title={t('platform.timeTracker.week')}>
              <p className="font-mono text-2xl tabular-nums">{formatDuration(weekSeconds + (running ? liveSeconds : 0))}</p>
            </AdminWidgetCard>
          </div>
          {canSeeTeam && teamToday !== null ? (
            <AdminWidgetCard title={t('platform.timeTracker.teamToday')}>
              <p className="font-mono text-xl tabular-nums">{formatDuration(teamToday)}</p>
            </AdminWidgetCard>
          ) : null}

          <AdminTabs
            ariaLabel={t('platform.timeTracker.today')}
            activeId={range}
            onSelect={(id) => setRange(id as RangeFilter)}
            items={[
              { id: 'today', label: t('platform.timeTracker.today'), testId: 'time-range-today' },
              { id: 'week', label: t('platform.timeTracker.week'), testId: 'time-range-week' },
            ]}
          />
          <label className="block space-y-1 text-sm max-w-xs">
            <span className="text-admin-muted">{t('platform.timeTracker.filterPlan')}</span>
            <select
              className={ADMIN_INPUT}
              value={planFilter}
              onChange={(event) => setPlanFilter(event.target.value)}
            >
              <option value="">{t('platform.timeTracker.filterAllPlans')}</option>
              {plans.map((plan) => (
                <option key={plan.id} value={plan.id}>
                  {plan.title}
                </option>
              ))}
            </select>
          </label>

          {loading ? (
            <p className="text-sm text-admin-muted">{t('platform.timeTracker.loading')}</p>
          ) : (
            <AdminDataTable
              columns={[
                t('platform.timeTracker.when'),
                t('platform.timeTracker.target'),
                t('platform.timeTracker.duration'),
                t('platform.timeTracker.note'),
                t('platform.timeTracker.actions'),
              ]}
              empty={visibleEntries.length === 0 ? t('platform.timeTracker.empty') : undefined}
            >
              {visibleEntries.map((entry) => (
                <tr key={entry.id} data-testid={`time-row-${entry.id}`}>
                  <td className="px-4 py-3 text-admin-muted">
                    {new Date(entry.startedAt * 1000).toLocaleString()}
                  </td>
                  <td className="px-4 py-3">{entry.label}</td>
                  <td className="px-4 py-3 font-mono tabular-nums">
                    {formatDuration(elapsedSeconds(entry.startedAt, entry.endedAt, now))}
                  </td>
                  <td className="px-4 py-3 text-admin-muted">{entry.note || '—'}</td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      className="btn btn-danger text-xs"
                      aria-label={t('platform.timeTracker.delete')}
                      onClick={() => void handleDelete(entry)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </AdminDataTable>
          )}
        </div>
      </div>
      </>
      ) : null}
    </div>
  );
};

export default TimeTrackerView;
