// frontend/src/hooks/useToast.ts
import { useNotification } from '../context/NotificationContext';

export const useToast = () => {
  const notification = useNotification();
  
  return {
    toast: notification,
    success: notification.success,
    error: notification.error,
    warning: notification.warning,
    info: notification.info,
  };
};

export default useToast;
