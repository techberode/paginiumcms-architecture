import React, { useState, useEffect } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';

interface Backup {
  id: string;
  name: string;
  createdAt: string;
  sizeFormatted: string;
  status: string;
}

export const BackupManager: React.FC = () => {
  const [backups, setBackups] = useState<Backup[]>([]);
  const [loading, setLoading] = useState(false);
  const [backupName, setBackupName] = useState('');
  const { execute } = useApi();
  const { success, error, info } = useToast();

  useEffect(() => {
    loadBackups();
  }, []);

  const loadBackups = async () => {
    const data = await execute('/api/admin/backups');
    if (data) {
      setBackups(data.backups || []);
    }
  };

  const createBackup = async () => {
    if (!backupName) {
      error('Zadajte názov zálohy');
      return;
    }

    setLoading(true);
    try {
      const data = await execute('/api/admin/backups', {
        method: 'POST',
        body: JSON.stringify({ name: backupName }),
      });
      if (data?.success) {
        success('Záloha bola vytvorená');
        setBackupName('');
        loadBackups();
      }
    } catch (err) {
      error('Chyba pri vytváraní zálohy');
    } finally {
      setLoading(false);
    }
  };

  const restoreBackup = async (id: string) => {
    if (!confirm('Naozaj chcete obnoviť túto zálohu? Aktuálny obsah bude prepísaný.')) {
      return;
    }

    setLoading(true);
    try {
      const data = await execute(`/api/admin/backups/${id}/restore`, {
        method: 'POST',
      });
      if (data?.success) {
        success('Záloha bola obnovená');
        loadBackups();
      }
    } catch (err) {
      error('Chyba pri obnove zálohy');
    } finally {
      setLoading(false);
    }
  };

  const deleteBackup = async (id: string) => {
    if (!confirm('Naozaj chcete vymazať túto zálohu?')) {
      return;
    }

    setLoading(true);
    try {
      const data = await execute(`/api/admin/backups/${id}`, {
        method: 'DELETE',
      });
      if (data?.success) {
        success('Záloha bola vymazaná');
        loadBackups();
      }
    } catch (err) {
      error('Chyba pri mazaní zálohy');
    } finally {
      setLoading(false);
    }
  };

  const downloadBackup = async (id: string) => {
    window.open(`/api/admin/backups/${id}/download`, '_blank');
  };

  return (
    <div className="p-4">
      <h3 className="text-lg font-semibold mb-4">💾 Zálohovanie</h3>

      <div className="flex gap-2 mb-4">
        <input
          type="text"
          value={backupName}
          onChange={(e) => setBackupName(e.target.value)}
          placeholder="Názov zálohy"
          className="flex-1 px-3 py-2 border rounded dark:bg-gray-800 dark:border-gray-600"
        />
        <button
          onClick={createBackup}
          disabled={loading || !backupName}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
        >
          {loading ? 'Vytváranie...' : 'Vytvoriť zálohu'}
        </button>
      </div>

      <div className="space-y-2">
        {backups.length === 0 ? (
          <p className="text-gray-500 dark:text-gray-400">Žiadne zálohy</p>
        ) : (
          backups.map((backup) => (
            <div
              key={backup.id}
              className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
              <div>
                <div className="font-medium">{backup.name}</div>
                <div className="text-sm text-gray-500 dark:text-gray-400">
                  {new Date(backup.createdAt).toLocaleString()} • {backup.sizeFormatted}
                </div>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => downloadBackup(backup.id)}
                  className="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                >
                  📥
                </button>
                <button
                  onClick={() => restoreBackup(backup.id)}
                  className="px-3 py-1 text-sm bg-yellow-600 text-white rounded hover:bg-yellow-700"
                >
                  🔄
                </button>
                <button
                  onClick={() => deleteBackup(backup.id)}
                  className="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                >
                  🗑️
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};
