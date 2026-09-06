// frontend/src/components/backend/BackupManager.tsx
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { CalendarClock, ShieldCheck, Upload } from 'lucide-react';
import { backupApi } from '../../api/backup';
import type { Backup, ScheduleInfo } from '../../api/types';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { BulkActionBar } from './BulkActionBar';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { SortableTableHeader } from './SortableTableHeader';
import { applyClientListView } from '../../utils/clientListView';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';

export const BackupManager: React.FC = () => {
  const { t } = useI18n();
  const [backups, setBackups] = useState<Backup[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [importing, setImporting] = useState(false);
  const [verifyingId, setVerifyingId] = useState<string | null>(null);
  const [restoringId, setRestoringId] = useState<string | null>(null);
  const [backupName, setBackupName] = useState('');
  const [importName, setImportName] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const toast = useToast();
  const [schedule, setSchedule] = useState<ScheduleInfo | null>(null);
  const [scheduleEnabled, setScheduleEnabled] = useState(false);
  const [scheduleInterval, setScheduleInterval] = useState<'daily' | 'weekly' | 'monthly'>('daily');
  const [scheduleKeep, setScheduleKeep] = useState(7);
  const [savingSchedule, setSavingSchedule] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('backups');
  const { sortField, sortDirection, handleSort } = useColumnSort('createdAt', 'desc');

  const loadBackups = useCallback(async () => {
    setLoading(true);
    try {
      const [items, scheduleInfo] = await Promise.all([backupApi.getAll(), backupApi.getSchedule()]);
      setBackups(items);
      setSchedule(scheduleInfo);
      if (scheduleInfo?.enabled) {
        setScheduleEnabled(true);
        if (scheduleInfo.interval) {
          setScheduleInterval(scheduleInfo.interval);
        }
        if (typeof scheduleInfo.keep === 'number') {
          setScheduleKeep(scheduleInfo.keep);
        }
      } else {
        setScheduleEnabled(false);
      }
    } catch {
      toast.error(t('backups.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void loadBackups();
  }, [loadBackups]);

  useEffect(() => {
    setPage(1);
  }, [search, sortField, sortDirection, pageSize]);

  const listView = useMemo(
    () =>
      applyClientListView(backups, {
        search,
        searchText: (backup) => `${backup.name} ${backup.status} ${backup.sha256 ?? ''}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'name', label: t('backups.table.name'), getValue: (backup) => backup.name },
          { value: 'createdAt', label: t('backups.table.created'), getValue: (backup) => backup.createdAt },
          { value: 'size', label: t('backups.table.size'), getValue: (backup) => backup.size },
          { value: 'status', label: t('backups.table.status'), getValue: (backup) => backup.status },
        ],
        page,
        pageSize,
      }),
    [backups, page, pageSize, search, sortDirection, sortField, t]
  );

  const pagedBackups = listView.items;
  const bulkSelection = useBulkSelection(
    pagedBackups.filter((b) => b.status === 'completed').map((backup) => backup.id),
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const handleCreateBackup = async () => {
    if (!backupName.trim()) {
      toast.warning(t('backups.toast.nameRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await backupApi.create(backupName.trim());
      if (created) {
        toast.success(t('backups.toast.createSuccess'));
        setBackupName('');
        await loadBackups();
      } else {
        toast.error(t('backups.toast.createFailed'));
      }
    } catch {
      toast.error(t('backups.toast.createFailed'));
    } finally {
      setCreating(false);
    }
  };

  const handleImportBackup = async (file: File) => {
    setImporting(true);
    try {
      const result = await backupApi.importArchive(file, importName.trim() || undefined);
      if (result.ok && result.backup) {
        toast.success(
          t('backups.toast.importSuccessNamed', { name: result.backup.name })
        );
        setImportName('');
        await loadBackups();
      } else {
        toast.error(
          t('backups.toast.importFailedDetail', {
            reason: result.error ?? t('backups.toast.importFailed'),
          })
        );
      }
    } catch {
      toast.error(t('backups.toast.importFailed'));
    } finally {
      setImporting(false);
      if (importInputRef.current) {
        importInputRef.current.value = '';
      }
    }
  };

  const handleVerifyBackup = async (backup: Backup) => {
    setVerifyingId(backup.id);
    try {
      const result = await backupApi.verify(backup.id);
      if (!result) {
        toast.error(t('backups.toast.verifyFailed'));
        return;
      }
      if (result.reason === 'legacy_without_hash') {
        toast.info(t('backups.toast.verifyLegacy', { hash: result.actual?.slice(0, 12) ?? '' }));
        return;
      }
      if (result.valid) {
        toast.success(t('backups.toast.verifyOk'));
      } else {
        toast.error(t('backups.toast.verifyMismatch'));
      }
    } finally {
      setVerifyingId(null);
    }
  };

  const handleDownloadBackup = async (backup: Backup) => {
    const result = await backupApi.download(backup.id, backup.name);
    if (result.ok) {
      toast.success(
        result.sha256
          ? t('backups.toast.downloadSuccessHash', { hash: result.sha256.slice(0, 12) })
          : t('backups.toast.downloadSuccess')
      );
    } else {
      toast.error(t('backups.toast.downloadFailed'));
    }
  };

  const handleRestoreBackup = async (backup: Backup) => {
    if (!confirm(t('backups.confirm.restoreOne'))) {
      return;
    }

    setRestoringId(backup.id);
    toast.info(t('backups.toast.restoreInProgress', { name: backup.name }));
    try {
      const result = await backupApi.restore(backup.id);
      if (result.ok) {
        toast.success(t('backups.toast.restoreSuccessNamed', { name: backup.name }));
        await loadBackups();
      } else {
        toast.error(
          t('backups.toast.restoreFailedDetail', {
            reason: result.error ?? t('backups.toast.restoreFailed'),
          })
        );
      }
    } catch {
      toast.error(t('backups.toast.restoreFailed'));
    } finally {
      setRestoringId(null);
    }
  };

  const handleDeleteBackup = async (id: string) => {
    if (!confirm(t('backups.confirm.deleteOne'))) {
      return;
    }
    const ok = await backupApi.delete(id);
    if (ok) {
      toast.success(t('backups.toast.deleteSuccess'));
      await loadBackups();
    } else {
      toast.error(t('backups.toast.deleteFailed'));
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('backups.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }
    const result = await backupApi.bulkDelete(bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await loadBackups();
    } else {
      toast.error(t('backups.toast.bulkDeleteFailed'));
    }
  };

  const handleBulkRestore = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('backups.confirm.bulkRestore', { count: String(bulkSelection.count) }))) {
      return;
    }
    toast.info(t('backups.toast.bulkRestoreInProgress', { count: String(bulkSelection.count) }));
    const result = await backupApi.bulkRestore(bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await loadBackups();
    } else {
      toast.error(t('backups.toast.bulkRestoreFailed'));
    }
  };

  const handleSaveSchedule = async () => {
    setSavingSchedule(true);
    try {
      const saved = await backupApi.schedule(
        scheduleEnabled
          ? { enabled: true, interval: scheduleInterval, keep: scheduleKeep }
          : { enabled: false }
      );
      if (!saved) {
        toast.error(t('backups.toast.scheduleFailed'));
        return;
      }
      setSchedule(saved);
      toast.success(
        scheduleEnabled ? t('backups.toast.scheduleSaved') : t('backups.toast.scheduleDisabled')
      );
    } catch {
      toast.error(t('backups.toast.scheduleFailed'));
    } finally {
      setSavingSchedule(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const classes = {
      completed: 'badge-success',
      failed: 'badge-danger',
      in_progress: 'badge-warning',
    };
    return `badge ${classes[status as keyof typeof classes] || 'badge-info'}`;
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('backups.page.title')}</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="card">
          <div className="card-header">{t('backups.create.title')}</div>
          <div className="card-body">
            <div className="flex gap-4 flex-wrap">
              <input
                type="text"
                value={backupName}
                onChange={(e) => setBackupName(e.target.value)}
                placeholder={t('backups.create.placeholder')}
                className="form-input flex-1 min-w-[200px]"
              />
              <button
                type="button"
                onClick={() => void handleCreateBackup()}
                disabled={creating}
                className="btn btn-primary"
              >
                {creating ? t('backups.create.creating') : t('backups.create.button')}
              </button>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header">{t('backups.import.title')}</div>
          <div className="card-body space-y-3">
            <input
              type="text"
              value={importName}
              onChange={(e) => setImportName(e.target.value)}
              placeholder={t('backups.import.placeholder')}
              className="form-input w-full"
            />
            <div className="flex gap-3 flex-wrap items-center">
              <input
                ref={importInputRef}
                type="file"
                accept=".zip,application/zip"
                className="hidden"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) {
                    void handleImportBackup(file);
                  }
                }}
              />
              <button
                type="button"
                className="btn btn-secondary"
                disabled={importing}
                onClick={() => importInputRef.current?.click()}
              >
                <Upload className="w-4 h-4 inline mr-2" />
                {importing ? t('backups.import.importing') : t('backups.import.button')}
              </button>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {t('backups.import.hint')}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-header flex items-center gap-2">
          <CalendarClock className="w-5 h-5 text-indigo-500" />
          {t('backups.schedule.title')}
        </div>
        <div className="card-body space-y-4">
          <p className="text-sm text-gray-600 dark:text-gray-300">{t('backups.schedule.intro')}</p>
          <ol className="text-sm text-gray-600 dark:text-gray-300 list-decimal list-inside space-y-1">
            <li>{t('backups.schedule.stepScheduler')}</li>
            <li>{t('backups.schedule.stepCron')}</li>
            <li>{t('backups.schedule.stepHere')}</li>
          </ol>
          <label className="inline-flex items-center gap-2 text-sm font-semibold">
            <input
              type="checkbox"
              checked={scheduleEnabled}
              onChange={(e) => setScheduleEnabled(e.target.checked)}
              className="rounded"
            />
            {t('backups.schedule.enabled')}
          </label>
          {scheduleEnabled && (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <label className="block text-sm">
                <span className="font-semibold">{t('backups.schedule.interval')}</span>
                <select
                  value={scheduleInterval}
                  onChange={(e) => setScheduleInterval(e.target.value as 'daily' | 'weekly' | 'monthly')}
                  className="form-input mt-1 w-full"
                >
                  <option value="daily">{t('backups.schedule.intervals.daily')}</option>
                  <option value="weekly">{t('backups.schedule.intervals.weekly')}</option>
                  <option value="monthly">{t('backups.schedule.intervals.monthly')}</option>
                </select>
              </label>
              <label className="block text-sm">
                <span className="font-semibold">{t('backups.schedule.keep')}</span>
                <input
                  type="number"
                  min={1}
                  max={365}
                  value={scheduleKeep}
                  onChange={(e) => setScheduleKeep(Math.max(1, Math.min(365, Number(e.target.value) || 1)))}
                  className="form-input mt-1 w-full"
                />
              </label>
            </div>
          )}
          {schedule?.enabled && schedule.next_run && (
            <p className="text-xs text-gray-500 dark:text-gray-400">
              {t('backups.schedule.nextRun', { at: schedule.next_run })}
              {schedule.last_run ? ` · ${t('backups.schedule.lastRun', { at: schedule.last_run })}` : ''}
            </p>
          )}
          <button
            type="button"
            disabled={savingSchedule}
            onClick={() => void handleSaveSchedule()}
            className="btn btn-primary"
          >
            {savingSchedule ? t('backups.schedule.saving') : t('backups.schedule.save')}
          </button>
        </div>
      </div>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('backups.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'restore',
            label: t('backups.bulk.restore'),
            variant: 'primary',
            onClick: () => void handleBulkRestore(),
          },
          {
            id: 'delete',
            label: t('backups.bulk.delete'),
            variant: 'danger',
            onClick: () => void handleBulkDelete(),
          },
        ]}
      />

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('backups.search.placeholder')}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
      />

      <div className="card w-full">
        <div className="card-header">
          <span>{t('backups.page.listTitle')}</span>
          <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
            {t('backups.page.listCount', { count: String(listView.total) })}
          </span>
        </div>
        <div className="card-body p-0">
          {listView.total === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              {backups.length === 0
                ? t('backups.empty.none')
                : t('backups.empty.filter')}
            </div>
          ) : (
            <>
              <div className="table-container w-full">
                <table className="table w-full">
                  <thead>
                    <tr>
                      <th className="w-10">
                        <input
                          type="checkbox"
                          checked={
                            bulkSelection.allSelected &&
                            pagedBackups.filter((b) => b.status === 'completed').length > 0
                          }
                          onChange={bulkSelection.toggleAll}
                          aria-label={t('backups.table.selectAll')}
                        />
                      </th>
                      <SortableTableHeader
                        label={t('backups.table.name')}
                        field="name"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label={t('backups.table.created')}
                        field="createdAt"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                        thClassName="hide-mobile"
                      />
                      <SortableTableHeader
                        label={t('backups.table.size')}
                        field="size"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="hide-tablet">{t('backups.table.hash')}</th>
                      <SortableTableHeader
                        label={t('backups.table.status')}
                        field="status"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th>{t('backups.table.actions')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {pagedBackups.map((backup) => (
                    <tr key={backup.id}>
                      <td>
                        <input
                          type="checkbox"
                          checked={bulkSelection.isSelected(backup.id)}
                          disabled={backup.status !== 'completed'}
                          onChange={() => bulkSelection.toggle(backup.id)}
                          aria-label={t('backups.table.selectOne', { name: backup.name })}
                        />
                      </td>
                      <td className="font-medium">{backup.name}</td>
                      <td>{new Date(backup.createdAt).toLocaleString()}</td>
                      <td>{backup.sizeFormatted}</td>
                      <td className="font-mono text-xs max-w-[140px] truncate" title={backup.sha256 || '—'}>
                        {backup.sha256 ? `${backup.sha256.slice(0, 12)}…` : '—'}
                      </td>
                      <td>
                        <span className={getStatusBadge(backup.status)}>
                          {t(`backups.status.${backup.status}` as 'backups.status.completed')}
                        </span>
                      </td>
                      <td>
                        <div className="flex flex-wrap gap-1">
                          <button
                            type="button"
                            onClick={() => void handleDownloadBackup(backup)}
                            className="btn btn-secondary text-xs px-2 py-1"
                            disabled={backup.status !== 'completed'}
                          >
                            {t('backups.actions.download')}
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleVerifyBackup(backup)}
                            className="btn btn-secondary text-xs px-2 py-1"
                            disabled={backup.status !== 'completed' || verifyingId === backup.id}
                            title={t('backups.actions.verify')}
                          >
                            <ShieldCheck className="w-3 h-3" />
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleRestoreBackup(backup)}
                            className="btn btn-success text-xs px-2 py-1"
                            disabled={backup.status !== 'completed' || restoringId === backup.id}
                          >
                            {restoringId === backup.id
                              ? t('backups.actions.restoring')
                              : t('backups.actions.restore')}
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleDeleteBackup(backup.id)}
                            className="btn btn-danger text-xs px-2 py-1"
                          >
                            {t('backups.actions.delete')}
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
              <div className="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                <AdminListPagination
                  page={listView.page}
                  totalPages={listView.totalPages}
                  total={listView.total}
                  pageSize={pageSize}
                  loading={loading}
                  onPageChange={setPage}
                  itemLabel={t('backups.pagination.itemLabel')}
                />
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default BackupManager;
