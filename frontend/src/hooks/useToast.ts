// frontend/src/hooks/useToast.ts
import { useMemo } from 'react';
import { useNotification } from '../context/NotificationContext';

export const useToast = () => {
  const notification = useNotification();

  return useMemo(
    () => ({
      toast: notification,
      success: notification.success,
      error: notification.error,
      warning: notification.warning,
      info: notification.info,
    }),
    [notification.success, notification.error, notification.warning, notification.info]
  );
};

export default useToast;
