// frontend/src/components/backend/BackupManager.tsx
import React, { useState, useEffect } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';

interface Backup {
  id: string;
  name: string;
  createdAt: string;
  size: number;
  sizeFormatted: string;
  status: 'in_progress' | 'completed' | 'failed';
  includes: string[];
}

export const BackupManager: React.FC = () => {
  const [backups, setBackups] = useState<Backup[]>([]);
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);
  const [backupName, setBackupName] = useState('');
  const { get, post, del } = useApi();
  const toast = useToast();

  useEffect(() => {
    loadBackups();
  }, []);

  const loadBackups = async () => {
    setLoading(true);
    try {
      const response = await get<Backup[]>('/api/admin/backups');
      if (response.success) {
        setBackups(response.data || []);
      }
    } catch (error) {
      toast.error('Failed to load backups');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateBackup = async () => {
    if (!backupName.trim()) {
      toast.warning('Please enter a backup name');
      return;
    }

    setCreating(true);
    try {
      const response = await post<Backup>('/api/admin/backups', {
        name: backupName,
        includes: ['content', 'config', 'data'],
      });
      
      if (response.success) {
        toast.success('Backup created successfully');
        setBackupName('');
        await loadBackups();
      } else {
        toast.error(response.error || 'Failed to create backup');
      }
    } catch (error) {
      toast.error('Failed to create backup');
    } finally {
      setCreating(false);
    }
  };

  const handleDeleteBackup = async (id: string) => {
    if (!confirm('Are you sure you want to delete this backup?')) {
      return;
    }

    try {
      const response = await del(`/api/admin/backups/${id}`);
      if (response.success) {
        toast.success('Backup deleted successfully');
        await loadBackups();
      } else {
        toast.error(response.error || 'Failed to delete backup');
      }
    } catch (error) {
      toast.error('Failed to delete backup');
    }
  };

  const handleDownloadBackup = async (id: string, name: string) => {
    try {
      const response = await get<Blob>(`/api/admin/backups/${id}/download`, {
        responseType: 'blob',
      });
      
      if (response.success && response.data) {
        const blob = response.data as Blob;
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${name}.zip`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        toast.success('Backup downloaded successfully');
      } else {
        toast.error('Failed to download backup');
      }
    } catch (error) {
      toast.error('Failed to download backup');
    }
  };

  const handleRestoreBackup = async (id: string) => {
    if (!confirm('Are you sure you want to restore this backup? This will overwrite current content.')) {
      return;
    }

    try {
      const response = await post(`/api/admin/backups/${id}/restore`);
      if (response.success) {
        toast.success('Backup restored successfully');
        await loadBackups();
      } else {
        toast.error(response.error || 'Failed to restore backup');
      }
    } catch (error) {
      toast.error('Failed to restore backup');
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
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Backup Manager</h1>
      </div>

      {/* Create Backup */}
      <div className="card">
        <div className="card-header">Create New Backup</div>
        <div className="card-body">
          <div className="flex gap-4">
            <input
              type="text"
              value={backupName}
              onChange={(e) => setBackupName(e.target.value)}
              placeholder="Enter backup name..."
              className="form-input flex-1"
            />
            <button
              onClick={handleCreateBackup}
              disabled={creating}
              className="btn btn-primary"
            >
              {creating ? 'Creating...' : 'Create Backup'}
            </button>
          </div>
        </div>
      </div>

      {/* Backup List */}
      <div className="card">
        <div className="card-header">
          <span>Backups</span>
          <span className="text-sm font-normal text-gray-500 dark:text-gray-400">
            {backups.length} backups
          </span>
        </div>
        <div className="card-body p-0">
          {backups.length === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              No backups found. Create your first backup!
            </div>
          ) : (
            <div className="table-container">
              <table className="table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Includes</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {backups.map((backup) => (
                    <tr key={backup.id}>
                      <td className="font-medium">{backup.name}</td>
                      <td>{new Date(backup.createdAt).toLocaleString()}</td>
                      <td>{backup.sizeFormatted}</td>
                      <td>
                        <span className={getStatusBadge(backup.status)}>
                          {backup.status}
                        </span>
                      </td>
                      <td>
                        <span className="text-sm text-gray-500 dark:text-gray-400">
                          {backup.includes.join(', ')}
                        </span>
                      </td>
                      <td>
                        <div className="flex gap-2">
                          <button
                            onClick={() => handleDownloadBackup(backup.id, backup.name)}
                            className="btn btn-secondary text-xs px-3 py-1"
                          >
                            Download
                          </button>
                          <button
                            onClick={() => handleRestoreBackup(backup.id)}
                            className="btn btn-success text-xs px-3 py-1"
                          >
                            Restore
                          </button>
                          <button
                            onClick={() => handleDeleteBackup(backup.id)}
                            className="btn btn-danger text-xs px-3 py-1"
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
          )}
        </div>
      </div>
    </div>
  );
};

export default BackupManager;
