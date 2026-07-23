import { useCallback, useState } from 'react';
import { purgeCache, type CachePurgeScope } from '../api/cache';
import { useToast } from './useToast';
import { useI18n } from '../context/I18nContext';

export function useCachePurge() {
  const { t } = useI18n();
  const toast = useToast();
  const [purging, setPurging] = useState<CachePurgeScope | null>(null);

  const purge = useCallback(
    async (scope: CachePurgeScope): Promise<boolean> => {
      const confirmMessage =
        scope === 'all' ? t('settings.cache.confirmAll') : t('settings.cache.confirmContent');
      if (!window.confirm(confirmMessage)) {
        return false;
      }

      setPurging(scope);
      try {
        const res = await purgeCache(scope);
        if (res.success && res.data) {
          toast.success(res.message || t('settings.cache.purged'));
          return true;
        }

        toast.error(res.error || t('settings.cache.purgeFailed'));
        return false;
      } finally {
        setPurging(null);
      }
    },
    [t, toast]
  );

  return {
    purge,
    purging,
    isPurging: purging !== null,
  };
}
