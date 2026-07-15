// frontend/src/components/dashboard/LocksPanel.tsx
import React from 'react';
import { ContentLock, forceReleaseLock } from '../../api/locks';
import { useToast } from '../../hooks/useToast';

interface LocksPanelProps {
  locks: ContentLock[];
  loading?: boolean;
  onRefresh: () => void;
}

export const LocksPanel: React.FC<LocksPanelProps> = ({ locks, loading, onRefresh }) => {
  const toast = useToast();

  const handleForceRelease = async (resourceId: string) => {
    const ok = await forceReleaseLock(resourceId);
    if (ok) {
      toast.success('Lock released');
      onRefresh();
    } else {
      toast.error('Failed to release lock');
    }
  };

  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Active locks</h2>
          <span className="text-sm text-gray-500">{locks.length} active</span>
        </div>

        {loading ? (
          <div className="flex justify-center py-6">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600" />
          </div>
        ) : locks.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">No active content locks.</p>
        ) : (
          <ul className="space-y-2">
            {locks.map((lock) => (
              <li
                key={lock.resourceId}
                className="flex items-center justify-between gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40"
              >
                <div className="min-w-0">
                  <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{lock.resourceId}</p>
                  <p className="text-xs text-gray-500">
                    {lock.lockedByName} · expires {new Date(lock.expiresAt * 1000).toLocaleTimeString()}
                  </p>
                </div>
                <button
                  type="button"
                  className="text-xs text-red-600 hover:underline shrink-0"
                  onClick={() => void handleForceRelease(lock.resourceId)}
                >
                  Release
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
};

export default LocksPanel;
