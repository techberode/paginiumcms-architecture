// frontend/src/components/backend/BackupManager.tsx
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ShieldCheck, Upload } from 'lucide-react';
import { backupApi } from '../../api/backup';
import type { Backup } from '../../api/types';
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

export const BackupManager: React.FC = () => {
  const [backups, setBackups] = useState<Backup[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [importing, setImporting] = useState(false);
  const [verifyingId, setVerifyingId] = useState<string | null>(null);
  const [backupName, setBackupName] = useState('');
  const [importName, setImportName] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const toast = useToast();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('backups');
  const { sortField, sortDirection, handleSort } = useColumnSort('createdAt', 'desc');

  const loadBackups = useCallback(async () => {
    setLoading(true);
    try {
      setBackups(await backupApi.getAll());
    } catch {
      toast.error('Failed to load backups');
    } finally {
      setLoading(false);
    }
  }, [toast]);

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
          { value: 'name', label: 'Názov', getValue: (backup) => backup.name },
          { value: 'createdAt', label: 'Dátum', getValue: (backup) => backup.createdAt },
          { value: 'size', label: 'Veľkosť', getValue: (backup) => backup.size },
          { value: 'status', label: 'Stav', getValue: (backup) => backup.status },
        ],
        page,
        pageSize,
      }),
    [backups, page, pageSize, search, sortDirection, sortField]
  );

  const pagedBackups = listView.items;
  const bulkSelection = useBulkSelection(
    pagedBackups.filter((b) => b.status === 'completed').map((backup) => backup.id),
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const handleCreateBackup = async () => {
    if (!backupName.trim()) {
      toast.warning('Please enter a backup name');
      return;
    }

    setCreating(true);
    try {
      const created = await backupApi.create(backupName.trim());
      if (created) {
        toast.success('Backup created successfully');
        setBackupName('');
        await loadBackups();
      } else {
        toast.error('Failed to create backup');
      }
    } catch {
      toast.error('Failed to create backup');
    } finally {
      setCreating(false);
    }
  };

  const handleImportBackup = async (file: File) => {
    setImporting(true);
    try {
      const imported = await backupApi.importArchive(file, importName.trim() || undefined);
      if (imported) {
        toast.success('Backup imported into library');
        setImportName('');
        await loadBackups();
      } else {
        toast.error('Failed to import backup');
      }
    } catch {
      toast.error('Failed to import backup');
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
        toast.error('Hash verification failed');
        return;
      }
      if (result.reason === 'legacy_without_hash') {
        toast.info(`Legacy backup — current SHA-256: ${result.actual?.slice(0, 12)}…`);
        return;
      }
      if (result.valid) {
        toast.success('SHA-256 hash OK');
      } else {
        toast.error('SHA-256 mismatch — backup file may be corrupted');
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
          ? `Downloaded — SHA-256: ${result.sha256.slice(0, 12)}…`
          : 'Backup downloaded successfully'
      );
    } else {
      toast.error('Failed to download backup');
    }
  };

  const handleRestoreBackup = async (id: string) => {
    if (!confirm('Restore this backup? Current content will be overwritten.')) {
      return;
    }
    const ok = await backupApi.restore(id);
    if (ok) {
      toast.success('Backup restored successfully');
      await loadBackups();
    } else {
      toast.error('Failed to restore backup');
    }
  };

  const handleDeleteBackup = async (id: string) => {
    if (!confirm('Delete this backup?')) {
      return;
    }
    const ok = await backupApi.delete(id);
    if (ok) {
      toast.success('Backup deleted');
      await loadBackups();
    } else {
      toast.error('Failed to delete backup');
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(`Delete ${bulkSelection.count} selected backup(s)?`)) {
      return;
    }
    const result = await backupApi.bulkDelete(bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result));
      bulkSelection.clear();
      await loadBackups();
    } else {
      toast.error('Bulk delete failed');
    }
  };

  const handleBulkRestore = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(`Restore ${bulkSelection.count} backup(s)? Current content will be overwritten.`)) {
      return;
    }
    const result = await backupApi.bulkRestore(bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result));
      bulkSelection.clear();
      await loadBackups();
    } else {
      toast.error('Bulk restore failed');
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
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Backup Manager</h1>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="card">
          <div className="card-header">Create New Backup</div>
          <div className="card-body">
            <div className="flex gap-4 flex-wrap">
              <input
                type="text"
                value={backupName}
                onChange={(e) => setBackupName(e.target.value)}
                placeholder="Enter backup name..."
                className="form-input flex-1 min-w-[200px]"
              />
              <button
                type="button"
                onClick={() => void handleCreateBackup()}
                disabled={creating}
                className="btn btn-primary"
              >
                {creating ? 'Creating...' : 'Create Backup'}
              </button>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header">Import Backup ZIP</div>
          <div className="card-body space-y-3">
            <input
              type="text"
              value={importName}
              onChange={(e) => setImportName(e.target.value)}
              placeholder="Optional display name..."
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
                {importing ? 'Importing...' : 'Choose ZIP file'}
              </button>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                Registers archive in library — does not restore content until you click Restore.
              </p>
            </div>
          </div>
        </div>
      </div>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="backups selected"
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'restore',
            label: 'Restore selected',
            variant: 'primary',
            onClick: () => void handleBulkRestore(),
          },
          {
            id: 'delete',
            label: 'Delete selected',
            variant: 'danger',
            onClick: () => void handleBulkDelete(),
          },
        ]}
      />

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder="Hľadať zálohy podľa názvu alebo stavu…"
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
      />

      <div className="card w-full">
        <div className="card-header">
          <span>Backups</span>
          <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
            {listView.total} záloh
          </span>
        </div>
        <div className="card-body p-0">
          {listView.total === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              {backups.length === 0
                ? 'No backups found. Create or import your first backup.'
                : 'Nenašli sa žiadne zálohy pre filter.'}
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
                          aria-label="Select all completed backups on page"
                        />
                      </th>
                      <SortableTableHeader
                        label="Name"
                        field="name"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <SortableTableHeader
                        label="Created"
                        field="createdAt"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                        thClassName="hide-mobile"
                      />
                      <SortableTableHeader
                        label="Size"
                        field="size"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th className="hide-tablet">SHA-256</th>
                      <SortableTableHeader
                        label="Status"
                        field="status"
                        activeField={sortField}
                        direction={sortDirection}
                        onSort={handleSort}
                      />
                      <th>Actions</th>
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
                          aria-label={`Select ${backup.name}`}
                        />
                      </td>
                      <td className="font-medium">{backup.name}</td>
                      <td>{new Date(backup.createdAt).toLocaleString()}</td>
                      <td>{backup.sizeFormatted}</td>
                      <td className="font-mono text-xs max-w-[140px] truncate" title={backup.sha256 || '—'}>
                        {backup.sha256 ? `${backup.sha256.slice(0, 12)}…` : '—'}
                      </td>
                      <td>
                        <span className={getStatusBadge(backup.status)}>{backup.status}</span>
                      </td>
                      <td>
                        <div className="flex flex-wrap gap-1">
                          <button
                            type="button"
                            onClick={() => void handleDownloadBackup(backup)}
                            className="btn btn-secondary text-xs px-2 py-1"
                            disabled={backup.status !== 'completed'}
                          >
                            Download
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleVerifyBackup(backup)}
                            className="btn btn-secondary text-xs px-2 py-1"
                            disabled={backup.status !== 'completed' || verifyingId === backup.id}
                            title="Verify SHA-256 hash"
                          >
                            <ShieldCheck className="w-3 h-3" />
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleRestoreBackup(backup.id)}
                            className="btn btn-success text-xs px-2 py-1"
                            disabled={backup.status !== 'completed'}
                          >
                            Restore
                          </button>
                          <button
                            type="button"
                            onClick={() => void handleDeleteBackup(backup.id)}
                            className="btn btn-danger text-xs px-2 py-1"
                          >
                            Delete
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
                  itemLabel="záloh"
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
