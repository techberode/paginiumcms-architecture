import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useId,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useI18n } from './I18nContext';
import { setConfirmDialogImplementation } from '../utils/confirmDialog';

export type ConfirmVariant = 'default' | 'destructive';

export interface ConfirmOptions {
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: ConfirmVariant;
}

interface ConfirmRequest extends ConfirmOptions {
  resolve: (confirmed: boolean) => void;
}

interface ConfirmContextValue {
  confirm: (options: ConfirmOptions) => Promise<boolean>;
}

const ConfirmContext = createContext<ConfirmContextValue | undefined>(undefined);

export const ConfirmProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { t } = useI18n();
  const [request, setRequest] = useState<ConfirmRequest | null>(null);
  const titleId = useId();
  const pendingRef = useRef<ConfirmRequest | null>(null);

  const confirm = useCallback((options: ConfirmOptions): Promise<boolean> => {
    return new Promise((resolve) => {
      const entry: ConfirmRequest = { ...options, resolve };
      pendingRef.current = entry;
      setRequest(entry);
    });
  }, []);

  const close = useCallback((result: boolean) => {
    const current = pendingRef.current;
    pendingRef.current = null;
    setRequest(null);
    current?.resolve(result);
  }, []);

  const value = useMemo(() => ({ confirm }), [confirm]);

  useEffect(() => {
    setConfirmDialogImplementation(confirm);
    return () => setConfirmDialogImplementation(null);
  }, [confirm]);

  const variant = request?.variant ?? 'default';
  const confirmLabel =
    request?.confirmLabel ??
    (variant === 'destructive' ? t('admin.confirm.delete') : t('admin.confirm.ok'));
  const cancelLabel = request?.cancelLabel ?? t('admin.confirm.cancel');

  return (
    <ConfirmContext.Provider value={value}>
      {children}
      {request ? (
        <div className="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 p-4">
          <div
            role="alertdialog"
            aria-modal="true"
            aria-labelledby={titleId}
            className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
          >
            <h2 id={titleId} className="text-lg font-bold text-slate-900 dark:text-white">
              {request.title}
            </h2>
            <p className="mt-2 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap">
              {request.message}
            </p>
            <div className="mt-6 flex flex-wrap justify-end gap-2">
              <button
                type="button"
                className="px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium dark:border-slate-600"
                onClick={() => close(false)}
              >
                {cancelLabel}
              </button>
              <button
                type="button"
                autoFocus
                className={
                  variant === 'destructive'
                    ? 'px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700'
                    : 'px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700'
                }
                onClick={() => close(true)}
              >
                {confirmLabel}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </ConfirmContext.Provider>
  );
};

export function useConfirmContext(): ConfirmContextValue {
  const ctx = useContext(ConfirmContext);
  if (!ctx) {
    throw new Error('useConfirmContext must be used within ConfirmProvider');
  }
  return ctx;
}
