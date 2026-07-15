// frontend/src/context/NotificationContext.tsx
import React, { createContext, useContext, useState, useCallback, useMemo } from 'react';
import { useSettingsContext } from './SettingsContext';

export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  duration?: number;
}

interface NotificationContextType {
  notifications: Notification[];
  addNotification: (notification: Omit<Notification, 'id'>) => string;
  removeNotification: (id: string) => void;
  clearNotifications: () => void;
  success: (message: string, duration?: number) => string;
  error: (message: string, duration?: number) => string;
  warning: (message: string, duration?: number) => string;
  info: (message: string, duration?: number) => string;
  toastEnabled: boolean;
  toastPosition: string;
  toastDebugMode: boolean;
}

export const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

const POSITION_CLASSES: Record<string, string> = {
  'top-right': 'top-4 right-4',
  'top-left': 'top-4 left-4',
  'bottom-right': 'bottom-4 right-4',
  'bottom-left': 'bottom-4 left-4',
};

export const NotificationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const { settings } = useSettingsContext();

  const toastEnabled = settings.notifications?.toastEnabled ?? true;
  const toastPosition = settings.notifications?.toastPosition ?? 'top-right';
  const defaultDuration = settings.notifications?.toastDuration ?? 3000;
  const toastDebugMode = settings.notifications?.toastDebugMode ?? false;

  const removeNotification = useCallback((id: string) => {
    setNotifications((prev) => prev.filter((n) => n.id !== id));
  }, []);

  const addNotification = useCallback(
    (notification: Omit<Notification, 'id'>): string => {
      if (!toastEnabled) {
        if (toastDebugMode) {
          console.debug('[PaginiumCMS toast suppressed]', notification);
        }
        return '';
      }

      const id = Math.random().toString(36).substring(2, 9);
      let duration = notification.duration ?? defaultDuration;
      if (toastDebugMode) {
        duration = Math.max(duration, 8000);
        console.debug('[PaginiumCMS toast]', notification);
      }

      const newNotification: Notification = { ...notification, id, duration };
      setNotifications((prev) => [...prev, newNotification]);

      if (duration > 0) {
        setTimeout(() => removeNotification(id), duration);
      }

      return id;
    },
    [toastEnabled, defaultDuration, toastDebugMode, removeNotification]
  );

  const clearNotifications = useCallback(() => {
    setNotifications([]);
  }, []);

  const success = useCallback(
    (message: string, duration?: number) => addNotification({ type: 'success', message, duration }),
    [addNotification]
  );
  const error = useCallback(
    (message: string, duration?: number) => addNotification({ type: 'error', message, duration }),
    [addNotification]
  );
  const warning = useCallback(
    (message: string, duration?: number) => addNotification({ type: 'warning', message, duration }),
    [addNotification]
  );
  const info = useCallback(
    (message: string, duration?: number) => addNotification({ type: 'info', message, duration }),
    [addNotification]
  );

  const positionClass = POSITION_CLASSES[toastPosition] ?? POSITION_CLASSES['top-right'];

  const value = useMemo(
    () => ({
      notifications,
      addNotification,
      removeNotification,
      clearNotifications,
      success,
      error,
      warning,
      info,
      toastEnabled,
      toastPosition,
      toastDebugMode,
    }),
    [
      notifications,
      addNotification,
      removeNotification,
      clearNotifications,
      success,
      error,
      warning,
      info,
      toastEnabled,
      toastPosition,
      toastDebugMode,
    ]
  );

  return (
    <NotificationContext.Provider value={value}>
      {children}
      {toastEnabled && (
        <div className={`fixed z-50 space-y-2 max-w-md w-full px-4 ${positionClass}`}>
          {notifications.map((notification) => (
            <div
              key={notification.id}
              className={`
                p-4 rounded-lg shadow-lg animate-fade-in flex items-start justify-between
                ${notification.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ''}
                ${notification.type === 'error' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : ''}
                ${notification.type === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : ''}
                ${notification.type === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : ''}
              `}
            >
              <div className="flex-1">
                <p
                  className={`
                    text-sm
                    ${notification.type === 'success' ? 'text-green-800 dark:text-green-200' : ''}
                    ${notification.type === 'error' ? 'text-red-800 dark:text-red-200' : ''}
                    ${notification.type === 'warning' ? 'text-yellow-800 dark:text-yellow-200' : ''}
                    ${notification.type === 'info' ? 'text-blue-800 dark:text-blue-200' : ''}
                  `}
                >
                  {notification.message}
                </p>
              </div>
              <button
                onClick={() => removeNotification(notification.id)}
                className="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                type="button"
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}
    </NotificationContext.Provider>
  );
};

export const useNotification = () => {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotification must be used within a NotificationProvider');
  }
  return context;
};

export default NotificationProvider;
