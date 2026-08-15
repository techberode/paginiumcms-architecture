// frontend/src/components/backend/EditorialCalendarView.tsx
// === Editorial publication calendar (Iteration 81d) ===
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { CalendarDays, ChevronLeft, ChevronRight, RefreshCw } from 'lucide-react';
import { contentApi, EditorialCalendarEntry } from '../../api/content';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

const STATUS_BADGE_CLASS: Record<string, string> = {
  published: 'badge-success',
  draft: 'badge-warning',
  archived: 'badge-danger',
  scheduled: 'badge-info',
};

function pad2(value: number): string {
  return String(value).padStart(2, '0');
}

function toIsoDate(year: number, monthIndex: number, day: number): string {
  return `${year}-${pad2(monthIndex + 1)}-${pad2(day)}`;
}

function monthRange(year: number, monthIndex: number): { from: string; to: string } {
  const lastDay = new Date(year, monthIndex + 1, 0).getDate();
  return {
    from: toIsoDate(year, monthIndex, 1),
    to: toIsoDate(year, monthIndex, lastDay),
  };
}

function buildMonthGrid(year: number, monthIndex: number): Array<{ iso: string; inMonth: boolean }> {
  const firstWeekday = new Date(year, monthIndex, 1).getDay();
  const mondayOffset = (firstWeekday + 6) % 7;
  const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, monthIndex, 0).getDate();
  const cells: Array<{ iso: string; inMonth: boolean }> = [];

  for (let i = mondayOffset - 1; i >= 0; i -= 1) {
    const day = daysInPrevMonth - i;
    const prevMonth = monthIndex === 0 ? 11 : monthIndex - 1;
    const prevYear = monthIndex === 0 ? year - 1 : year;
    cells.push({ iso: toIsoDate(prevYear, prevMonth, day), inMonth: false });
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    cells.push({ iso: toIsoDate(year, monthIndex, day), inMonth: true });
  }

  while (cells.length % 7 !== 0) {
    const nextDay = cells.length - (mondayOffset + daysInMonth) + 1;
    const nextMonth = monthIndex === 11 ? 0 : monthIndex + 1;
    const nextYear = monthIndex === 11 ? year + 1 : year;
    cells.push({ iso: toIsoDate(nextYear, nextMonth, nextDay), inMonth: false });
  }

  return cells;
}

export const EditorialCalendarView: React.FC = () => {
  const { t, locale } = useI18n();
  const { error: toastError } = useToast();
  const today = new Date();
  const [cursor, setCursor] = useState(() => ({
    year: today.getFullYear(),
    month: today.getMonth(),
  }));
  const [typeFilter, setTypeFilter] = useState<'all' | 'page' | 'article'>('all');
  const [authorFilter, setAuthorFilter] = useState('');
  const [tagFilter, setTagFilter] = useState('');
  const [items, setItems] = useState<EditorialCalendarEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const range = useMemo(() => monthRange(cursor.year, cursor.month), [cursor]);
  const monthLabel = useMemo(
    () => new Date(cursor.year, cursor.month, 1).toLocaleDateString(locale, { month: 'long', year: 'numeric' }),
    [cursor.month, cursor.year, locale]
  );
  const grid = useMemo(() => buildMonthGrid(cursor.year, cursor.month), [cursor.month, cursor.year]);

  const itemsByDate = useMemo(() => {
    const map = new Map<string, EditorialCalendarEntry[]>();
    for (const item of items) {
      const bucket = map.get(item.calendarDate) ?? [];
      bucket.push(item);
      map.set(item.calendarDate, bucket);
    }
    return map;
  }, [items]);

  const statusLabel = (status: string): string => {
    const key = `list.status.${status}`;
    const translated = t(key);
    return translated !== key ? translated : status;
  };

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await contentApi.editorialCalendar({
        from: range.from,
        to: range.to,
        type: typeFilter,
        author: authorFilter.trim() || undefined,
        tag: tagFilter.trim() || undefined,
      });
      setItems(result.items);
      if (result.error) {
        setError(result.error);
      }
    } catch (loadError) {
      console.error(loadError);
      setError(t('content.calendar.loadError'));
      toastError(t('content.calendar.loadError'));
    } finally {
      setLoading(false);
    }
  }, [authorFilter, range.from, range.to, tagFilter, t, toastError, typeFilter]);

  useEffect(() => {
    void load();
  }, [load]);

  const goPrevMonth = () => {
    setCursor((current) => {
      if (current.month === 0) {
        return { year: current.year - 1, month: 11 };
      }
      return { year: current.year, month: current.month - 1 };
    });
  };

  const goNextMonth = () => {
    setCursor((current) => {
      if (current.month === 11) {
        return { year: current.year + 1, month: 0 };
      }
      return { year: current.year, month: current.month + 1 };
    });
  };

  const goToday = () => {
    const now = new Date();
    setCursor({ year: now.getFullYear(), month: now.getMonth() });
  };

  const weekdayLabels = useMemo(() => {
    const base = new Date(2026, 0, 5);
    return Array.from({ length: 7 }, (_, index) =>
      new Date(base.getFullYear(), base.getMonth(), base.getDate() + index).toLocaleDateString(locale, {
        weekday: 'short',
      })
    );
  }, [locale]);

  return (
    <div className="p-4 sm:p-8 space-y-6">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <div className="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
            <CalendarDays className="w-5 h-5" />
            <span className="text-xs font-extrabold uppercase tracking-wider">{t('content.calendar.kicker')}</span>
          </div>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-2xl">{t('content.calendar.help')}</p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <button type="button" onClick={goPrevMonth} className="btn-secondary px-3 py-2" aria-label={t('content.calendar.prevMonth')}>
            <ChevronLeft className="w-4 h-4" />
          </button>
          <button type="button" onClick={goToday} className="btn-secondary px-4 py-2 text-xs font-bold">
            {t('content.calendar.today')}
          </button>
          <button type="button" onClick={goNextMonth} className="btn-secondary px-3 py-2" aria-label={t('content.calendar.nextMonth')}>
            <ChevronRight className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={() => void load()}
            disabled={loading}
            className="btn-secondary px-3 py-2"
            aria-label={t('content.calendar.refresh')}
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      <div className="grid gap-3 md:grid-cols-4">
        <label className="block">
          <span className="form-label">{t('content.calendar.filters.type')}</span>
          <select
            className="form-input"
            value={typeFilter}
            onChange={(event) => setTypeFilter(event.target.value as 'all' | 'page' | 'article')}
          >
            <option value="all">{t('content.calendar.filters.allTypes')}</option>
            <option value="page">{t('content.pages.itemAccusative')}</option>
            <option value="article">{t('content.articles.itemAccusative')}</option>
          </select>
        </label>
        <label className="block">
          <span className="form-label">{t('content.calendar.filters.author')}</span>
          <input
            className="form-input"
            value={authorFilter}
            onChange={(event) => setAuthorFilter(event.target.value)}
            placeholder={t('content.calendar.filters.authorPlaceholder')}
          />
        </label>
        <label className="block md:col-span-2">
          <span className="form-label">{t('content.calendar.filters.tag')}</span>
          <input
            className="form-input"
            value={tagFilter}
            onChange={(event) => setTagFilter(event.target.value)}
            placeholder={t('content.calendar.filters.tagPlaceholder')}
          />
        </label>
      </div>

      <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
        <div className="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800">
          <h2 className="text-lg sm:text-xl font-black text-slate-900 dark:text-white">{monthLabel}</h2>
          <span className="text-xs font-bold text-slate-500">
            {loading ? t('content.calendar.loading') : t('content.calendar.itemCount', { count: items.length })}
          </span>
        </div>

        {error && !loading ? (
          <div className="p-8 text-center text-sm text-rose-600 dark:text-rose-400">{error}</div>
        ) : (
          <>
            <div className="grid grid-cols-7 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60">
              {weekdayLabels.map((label) => (
                <div key={label} className="px-2 py-2 text-[11px] font-extrabold uppercase tracking-wide text-slate-500 text-center">
                  {label}
                </div>
              ))}
            </div>

            <div className="grid grid-cols-7 auto-rows-fr min-h-[28rem]">
              {grid.map((cell) => {
                const dayItems = itemsByDate.get(cell.iso) ?? [];
                const isToday = cell.iso === toIsoDate(today.getFullYear(), today.getMonth(), today.getDate());

                return (
                  <div
                    key={cell.iso}
                    className={`min-h-32 border-b border-r border-slate-200 dark:border-slate-800 p-2 ${
                      cell.inMonth ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/70 dark:bg-slate-950/40'
                    }`}
                  >
                    <div className="flex items-center justify-between gap-2 mb-2">
                      <span
                        className={`inline-flex h-7 min-w-7 items-center justify-center rounded-full text-xs font-bold ${
                          isToday
                            ? 'bg-indigo-600 text-white'
                            : cell.inMonth
                              ? 'text-slate-700 dark:text-slate-200'
                              : 'text-slate-400'
                        }`}
                      >
                        {Number(cell.iso.slice(-2))}
                      </span>
                      {dayItems.length > 0 && (
                        <span className="text-[10px] font-bold text-slate-400">{dayItems.length}</span>
                      )}
                    </div>

                    <div className="space-y-1">
                      {dayItems.slice(0, 3).map((item) => {
                        const href = item.type === 'article' ? `/articles/${item.slug}` : `/pages/${item.slug}`;
                        return (
                          <Link
                            key={`${item.type}:${item.slug}`}
                            to={href}
                            className="block rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/30 transition-colors"
                            title={item.title}
                          >
                            <div className="truncate text-xs font-bold text-slate-800 dark:text-slate-100">{item.title}</div>
                            <div className="mt-1 flex items-center gap-1.5">
                              <span className={`badge ${STATUS_BADGE_CLASS[item.status] ?? 'badge-info'} text-[10px] px-1.5 py-0.5`}>
                                {statusLabel(item.status)}
                              </span>
                              <span className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                {item.type === 'article' ? t('content.articles.itemAccusative') : t('content.pages.itemAccusative')}
                              </span>
                            </div>
                          </Link>
                        );
                      })}
                      {dayItems.length > 3 && (
                        <div className="text-[10px] font-bold text-slate-400 px-1">
                          {t('content.calendar.moreItems', { count: dayItems.length - 3 })}
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}

        {!loading && !error && items.length === 0 && (
          <div className="border-t border-slate-200 dark:border-slate-800 p-8 text-center text-sm text-slate-500">
            {t('content.calendar.empty')}
          </div>
        )}
      </div>
    </div>
  );
};

export default EditorialCalendarView;
