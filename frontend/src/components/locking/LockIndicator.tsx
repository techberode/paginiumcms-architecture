// frontend/src/components/locking/LockIndicator.tsx
// === Komponent: LockIndicator (Iterácia 1) ===
// Zobrazuje aktuálny stav zámku dokumentu: kto ho upravuje a či je uzamknutý.
// Napojený na hook useContentLock (heartbeat + auto-release rieši hook/backend).
import React from 'react';
import { Lock, Unlock, Loader2, AlertTriangle, RefreshCw } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { formatRelativeTime } from '../../utils/contentDates';
import { useContentLock } from '../../hooks/useContentLock';

interface LockIndicatorProps {
  /** Identifikátor zdroja, napr. `page:o-nas`. */
  resourceId: string;
  /** Ak false, indikátor je neaktívny (napr. kým sa nenačíta obsah). */
  enabled?: boolean;
  /** Zavolá sa, keď sa zmení, či môže používateľ editovať (drží zámok). */
  onLockChange?: (canEdit: boolean) => void;
}

/**
 * Vizuálny badge stavu zámku. Loading / Success / Error stavy podľa .cursorrules.
 */
export const LockIndicator: React.FC<LockIndicatorProps> = ({ resourceId, enabled = true, onLockChange }) => {
  const { locale } = useI18n();
  const { status, lock, error, retry } = useContentLock(resourceId, enabled);

  // Oznámime rodičovi, či používateľ smie editovať (drží zámok).
  React.useEffect(() => {
    onLockChange?.(status === 'locked-by-me');
  }, [status, onLockChange]);

  const relative = (ts?: number): string =>
    ts ? formatRelativeTime(ts, locale) : '';

  // === Blok: Vykreslenie podľa stavu ===
  switch (status) {
    case 'loading':
      return (
        <span className="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-1.5 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <Loader2 className="h-4 w-4 animate-spin" />
          Overujem zámok…
        </span>
      );

    case 'locked-by-me':
      return (
        <span className="inline-flex items-center gap-2 rounded-md bg-emerald-100 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
          <Lock className="h-4 w-4" />
          Upravujete vy
          {lock?.lastHeartbeat ? (
            <span className="text-xs font-normal text-emerald-600/80 dark:text-emerald-400/80">
              · aktívne {relative(lock.lastHeartbeat)}
            </span>
          ) : null}
        </span>
      );

    case 'locked-by-other':
      return (
        <span className="inline-flex items-center gap-2 rounded-md bg-amber-100 px-3 py-1.5 text-sm font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
          <Lock className="h-4 w-4" />
          Upravuje: {lock?.lockedByName || 'iný používateľ'}
          {lock?.acquiredAt ? (
            <span className="text-xs font-normal text-amber-700/80 dark:text-amber-400/80">
              · od {relative(lock.acquiredAt)}
            </span>
          ) : null}
          <button
            type="button"
            onClick={() => void retry()}
            className="ml-1 inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs text-amber-800 hover:bg-amber-200/60 dark:text-amber-200 dark:hover:bg-amber-800/40"
            title="Skúsiť znova získať zámok"
          >
            <RefreshCw className="h-3 w-3" />
            Skúsiť znova
          </button>
        </span>
      );

    case 'error':
      return (
        <span className="inline-flex items-center gap-2 rounded-md bg-red-100 px-3 py-1.5 text-sm text-red-700 dark:bg-red-900/40 dark:text-red-300">
          <AlertTriangle className="h-4 w-4" />
          {error || 'Chyba zámku'}
          <button
            type="button"
            onClick={() => void retry()}
            className="ml-1 inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs hover:bg-red-200/60 dark:hover:bg-red-800/40"
          >
            <RefreshCw className="h-3 w-3" />
            Skúsiť znova
          </button>
        </span>
      );

    default:
      return (
        <span className="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-1.5 text-sm text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <Unlock className="h-4 w-4" />
          Voľné
        </span>
      );
  }
};

export default LockIndicator;
