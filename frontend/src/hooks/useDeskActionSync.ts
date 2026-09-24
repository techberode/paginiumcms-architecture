import { useCallback } from 'react';
import { useDeskInbox } from './useDeskInbox';
import { useAdminCounts } from './useAdminCounts';

export type DeskItemKey = { kind: string; id: string };

/**
 * Optimistically drops handled items from the notification modal / bubble, then refreshes nav counts.
 */
export function useDeskActionSync() {
  const { removeItemsByKey, refresh } = useDeskInbox();
  const { refresh: refreshCounts } = useAdminCounts();

  return useCallback(
    async (keys?: DeskItemKey[]) => {
      if (keys && keys.length > 0) {
        removeItemsByKey(keys);
      }
      refreshCounts();
      void refresh();
    },
    [removeItemsByKey, refresh, refreshCounts]
  );
}
