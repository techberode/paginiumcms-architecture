import { useCallback } from 'react';
import { useI18n } from '../context/I18nContext';
import { useConfirm } from './useConfirm';

/**
 * Destructive admin confirmations with consistent title + a11y modal.
 */
export function useAdminConfirm() {
  const confirm = useConfirm();
  const { t } = useI18n();

  return useCallback(
    (message: string, titleKey: string = 'admin.confirm.proceedTitle') =>
      confirm({
        title: t(titleKey),
        message,
        variant: 'destructive',
      }),
    [confirm, t]
  );
}
