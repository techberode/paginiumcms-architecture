// frontend/src/components/backend/LogsManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Archive, ExternalLink, Loader2, ScrollText, Trash2 } from 'lucide-react';
import {
  logsApi,
  LOG_SEVERITY_COLORS,
  type LogArchivedFilter,
  type LogEntry,
  type LogSeverity,
  type LogStats,
} from '../../api/logs';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { BulkActionBar } from './BulkActionBar';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { formatApplicationLogMessage, shouldShowLogContext } from '../../utils/formatApplicationLog';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';

const SEVERITIES: LogSeverity[] = ['debug', 'info', 'warning', 'error', 'critical'];
const LOGS_PAGE_SIZE_KEY = 'paginium-admin-page-size-logs';

function clampLogsPageSize(value: number): number {
  return Math.max(1, Math.min(500, value));
}

function readStoredLogsPageSize(): number {
  if (typeof window === 'undefined') {
    return 50;
  }

  const raw = window.localStorage.getItem(LOGS_PAGE_SIZE_KEY);
  if (raw === null) {
    return 50;
  }

  const parsed = Number(raw);
  return Number.isFinite(parsed) ? clampLogsPageSize(parsed) : 50;
}

export const LogsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [searchParams, setSearchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState<LogStats | null>(null);
  const [items, setItems] = useState<LogEntry[]>([]);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [severity, setSeverity] = useState<LogSeverity | ''>(
    (searchParams.get('severity') as LogSeverity) || ''
  );
  const [source, setSource] = useState('');
  const [archivedFilter, setArchivedFilter] = useState<LogArchivedFilter>('active');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSizeState] = useState(readStoredLogsPageSize);
  const [purging, setPurging] = useState(false);
  const [deletingAll, setDeletingAll] = useState(false);

  const setPageSize = useCallback((value: number) => {
    const next = clampLogsPageSize(value);
    setPageSizeState(next);
    setPage(1);
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(LOGS_PAGE_SIZE_KEY, String(next));
    }
  }, []);

  const totalPages = Math.max(1, Math.ceil(total / pageSize));
  const offset = (page - 1) * pageSize;

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [statsData, listData] = await Promise.all([
        logsApi.stats(24),
        logsApi.list({
          limit: pageSize,
          offset,
          severity: severity || undefined,
          source: source || undefined,
          search: search || undefined,
          archived: archivedFilter,
        }),
      ]);
      setStats(statsData);
      setItems(listData?.items ?? []);
      setTotal(listData?.total ?? 0);
    } catch {
      toast.error(t('logs.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [archivedFilter, offset, pageSize, search, severity, source, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    const fromUrl = searchParams.get('severity');
    const nextSeverity =
      fromUrl && SEVERITIES.includes(fromUrl as LogSeverity) ? (fromUrl as LogSeverity) : '';
    setSeverity((prev) => (prev === nextSeverity ? prev : nextSeverity));
  }, [searchParams]);

  useEffect(() => {
    if (severity) {
      setSearchParams({ severity }, { replace: true });
    } else {
      setSearchParams({}, { replace: true });
    }
  }, [severity, setSearchParams]);

  useEffect(() => {
    setPage(1);
  }, [search, severity, source, archivedFilter, pageSize]);

  useEffect(() => {
    if (page > totalPages) {
      setPage(totalPages);
    }
  }, [page, totalPages]);

  const visibleIds = useMemo(() => items.map((entry) => entry.id), [items]);
  const bulkSelection = useBulkSelection(
    visibleIds,
    `${page}:${search}:${severity}:${source}:${archivedFilter}:${pageSize}`
  );

  const handlePurge = async () => {
    if (!confirm(t('logs.confirm.purge'))) {
      return;
    }
    setPurging(true);
    try {
      const removed = await logsApi.purge();
      if (removed !== null) {
        toast.success(t('logs.toast.purgeSuccess', { count: String(removed) }));
        bulkSelection.clear();
        await load();
      } else {
        toast.error(t('logs.toast.purgeFailed'));
      }
    } finally {
      setPurging(false);
    }
  };

  const handleDeleteAll = async () => {
    if (!confirm(t('logs.confirm.deleteAll'))) {
      return;
    }
    setDeletingAll(true);
    try {
      const result = await logsApi.deleteAll();
      if (result) {
        toast.success(
          t('logs.toast.deleteAllSuccess', {
            files: String(result.deleted_files),
            entries: String(result.deleted_entries),
          })
        );
        bulkSelection.clear();
        setPage(1);
        await load();
      } else {
        toast.error(t('logs.toast.deleteAllFailed'));
      }
    } finally {
      setDeletingAll(false);
    }
  };

  const handleBulk = async (action: 'delete' | 'archive') => {
    if (bulkSelection.count === 0) {
      return;
    }

    if (action === 'delete' && !confirm(t('logs.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }

    const result = await logsApi.bulkAction(bulkSelection.selectedIds, action);
    if (!result) {
      toast.error(t('logs.toast.bulkFailed'));
      return;
    }

    const summary = summarizeBulkResult(result, t);
    if (result.failed > 0) {
      toast.warning(summary);
    } else {
      toast.success(
        action === 'delete'
          ? t('logs.toast.bulkDeleteSuccess', { count: String(result.succeeded) })
          : t('logs.toast.bulkArchiveSuccess', { count: String(result.succeeded) })
      );
    }

    bulkSelection.clear();
    await load();
  };

  const sources = useMemo(() => stats?.sources ?? ['app', 'audit', 'event', 'user'], [stats]);

  return (
    <div className="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-black text-slate-900 flex items-center gap-2">
              <ScrollText className="w-7 h-7 text-indigo-600" />
              {t('logs.page.title')}
            </h1>
            <p className="text-sm text-slate-500 mt-1">{t('logs.page.subtitle')}</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link
              to={settingsGroupPath('logging')}
              className="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800"
            >
              {t('logs.actions.settings')}
              <ExternalLink className="w-4 h-4" />
            </Link>
            <button
              type="button"
              disabled={purging}
              onClick={() => void handlePurge()}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-300 disabled:opacity-50 cursor-pointer"
            >
              <Trash2 className="w-4 h-4" />
              {t('logs.actions.purge')}
            </button>
            <button
              type="button"
              disabled={deletingAll}
              onClick={() => void handleDeleteAll()}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-100 text-red-700 text-xs font-bold hover:bg-red-200 disabled:opacity-50 cursor-pointer"
            >
              <Trash2 className="w-4 h-4" />
              {t('logs.actions.deleteAll')}
            </button>
          </div>
        </div>

        {stats && (
          <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
            {SEVERITIES.map((level) => (
              <button
                key={level}
                type="button"
                onClick={() => setSeverity(severity === level ? '' : level)}
                className={`rounded-2xl border p-4 text-left transition-all cursor-pointer ${
                  severity === level
                    ? 'border-indigo-500 ring-2 ring-indigo-200'
                    : 'border-slate-200 bg-white hover:border-indigo-300'
                }`}
              >
                <div className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold ${LOG_SEVERITY_COLORS[level]}`}>
                  {t(`logs.severity.${level}`)}
                </div>
                <div className="text-2xl font-black text-slate-900 mt-2">
                  {stats.by_severity[level] ?? 0}
                </div>
                <div className="text-[10px] text-slate-400 uppercase">{t('logs.stats.window')}</div>
              </button>
            ))}
          </div>
        )}

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-4 border-b border-slate-100 space-y-3">
            <BulkActionBar
              count={bulkSelection.count}
              itemLabel={t('logs.bulk.itemLabel')}
              onClear={bulkSelection.clear}
              actions={[
                {
                  id: 'archive',
                  label: t('logs.bulk.archive'),
                  variant: 'secondary',
                  onClick: () => void handleBulk('archive'),
                },
                {
                  id: 'delete',
                  label: t('logs.bulk.delete'),
                  variant: 'danger',
                  onClick: () => void handleBulk('delete'),
                },
              ]}
            />

            <AdminListToolbar
              search={search}
              onSearchChange={setSearch}
              searchPlaceholder={t('logs.search.placeholder')}
              pageSize={pageSize}
              onPageSizeChange={setPageSize}
              pageSizeInputMode="number"
              pageSizeMin={1}
              pageSizeMax={500}
            />

            <div className="flex flex-wrap gap-2 items-center">
              <span className="text-xs text-slate-500 font-bold">{t('logs.source.label')}</span>
              <button
                type="button"
                onClick={() => setSource('')}
                className={`px-3 py-1 rounded-lg text-xs font-bold cursor-pointer ${
                  source === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                }`}
              >
                {t('logs.source.all')}
              </button>
              {sources.map((src) => (
                <button
                  key={src}
                  type="button"
                  onClick={() => setSource(src)}
                  className={`px-3 py-1 rounded-lg text-xs font-bold cursor-pointer ${
                    source === src ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                  }`}
                >
                  {src}
                </button>
              ))}

              <span className="text-xs text-slate-500 font-bold ml-2">{t('logs.archived.label')}</span>
              {(['active', 'archived', 'all'] as LogArchivedFilter[]).map((value) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setArchivedFilter(value)}
                  className={`px-3 py-1 rounded-lg text-xs font-bold cursor-pointer ${
                    archivedFilter === value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                  }`}
                >
                  {t(`logs.archived.${value}`)}
                </button>
              ))}
            </div>
          </div>

          {loading ? (
            <div className="p-12 flex justify-center text-slate-400">
              <Loader2 className="w-8 h-8 animate-spin" />
            </div>
          ) : (
            <>
              <div className="overflow-x-auto max-h-[70vh]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-slate-50 z-10">
                    <tr className="border-b border-slate-100">
                      <th className="px-4 py-3 text-left">
                        <input
                          type="checkbox"
                          checked={bulkSelection.allSelected && items.length > 0}
                          onChange={bulkSelection.toggleAll}
                          aria-label={t('logs.bulk.selectAll')}
                        />
                      </th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.time')}</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.level')}</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.source')}</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.category')}</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.ip')}</th>
                      <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">{t('logs.table.message')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((entry) => (
                      <tr key={entry.id} className="border-b border-slate-50 hover:bg-slate-50/80 align-top">
                        <td className="px-4 py-3">
                          <input
                            type="checkbox"
                            checked={bulkSelection.isSelected(entry.id)}
                            onChange={() => bulkSelection.toggle(entry.id)}
                            aria-label={t('logs.bulk.selectOne')}
                          />
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                          {entry.timestamp}
                        </td>
                        <td className="px-4 py-3">
                          <span
                            className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                              LOG_SEVERITY_COLORS[entry.severity] ?? LOG_SEVERITY_COLORS.info
                            }`}
                          >
                            {t(`logs.severity.${entry.severity}` as 'logs.severity.info')}
                          </span>
                          {entry.archived ? (
                            <span className="ml-2 inline-flex items-center gap-1 text-[10px] font-bold text-slate-500">
                              <Archive className="w-3 h-3" />
                              {t('logs.archived.badge')}
                            </span>
                          ) : null}
                        </td>
                        <td className="px-4 py-3 text-xs font-mono">{entry.source ?? 'app'}</td>
                        <td className="px-4 py-3 text-xs">{entry.category}</td>
                        <td className="px-4 py-3 text-xs font-mono">{entry.ip ?? '—'}</td>
                        <td className="px-4 py-3">
                          <div className="font-medium text-slate-800">{formatApplicationLogMessage(entry)}</div>
                          {shouldShowLogContext(entry) && entry.context && (
                            <pre className="mt-1 text-[10px] text-slate-500 whitespace-pre-wrap break-all max-w-xl">
                              {JSON.stringify(entry.context, null, 2)}
                            </pre>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {items.length === 0 && (
                  <div className="p-12 text-center text-slate-400 text-sm">{t('logs.empty.none')}</div>
                )}
              </div>

              <div className="border-t border-slate-100 p-4">
                <AdminListPagination
                  page={page}
                  totalPages={totalPages}
                  total={total}
                  pageSize={pageSize}
                  loading={loading}
                  onPageChange={setPage}
                  itemLabel={t('logs.pagination.records')}
                />
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default LogsManager;
