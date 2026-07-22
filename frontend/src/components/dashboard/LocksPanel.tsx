// frontend/src/components/dashboard/LocksPanel.tsx
import React from 'react';
import { ContentLock, forceReleaseLock } from '../../api/locks';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

interface LocksPanelProps {
  locks: ContentLock[];
  loading?: boolean;
  onRefresh: () => void;
}

export const LocksPanel: React.FC<LocksPanelProps> = ({ locks, loading, onRefresh }) => {
  const toast = useToast();
  const { t, locale } = useI18n();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';

  const handleForceRelease = async (resourceId: string) => {
    const ok = await forceReleaseLock(resourceId);
    if (ok) {
      toast.success(t('dashboard.panels.locks.released'));
      onRefresh();
    } else {
      toast.error(t('dashboard.panels.locks.releaseFailed'));
    }
  };

  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('dashboard.panels.locks.title')}
          </h2>
          <span className="text-sm text-gray-500">
            {t('dashboard.panels.locks.activeCount', { count: String(locks.length) })}
          </span>
        </div>

        {loading ? (
          <div className="flex justify-center py-6">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600" />
          </div>
        ) : locks.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">{t('dashboard.panels.locks.empty')}</p>
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
                    {lock.lockedByName} · {t('dashboard.panels.locks.expires')}{' '}
                    {new Date(lock.expiresAt * 1000).toLocaleTimeString(dateLocale)}
                  </p>
                </div>
                <button
                  type="button"
                  className="text-xs text-red-600 hover:underline shrink-0"
                  onClick={() => void handleForceRelease(lock.resourceId)}
                >
                  {t('dashboard.panels.locks.release')}
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
