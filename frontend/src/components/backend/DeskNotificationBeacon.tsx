import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate } from 'react-router-dom';
import { Bell } from 'lucide-react';
import { useDeskInbox } from '../../hooks/useDeskInbox';
import { useI18n } from '../../context/I18nContext';
import { DeskQueueItemMeta } from './DeskQueueItemMeta';

interface DeskNotificationBeaconProps {
  variant: 'header' | 'sidebar' | 'topnav';
}

/**
 * When the floating desk bubble is off, show an unmissable count in admin chrome.
 * The queue portals to document.body so sidebar ink remaps cannot wash out the text.
 */
export const DeskNotificationBeacon: React.FC<DeskNotificationBeaconProps> = ({ variant }) => {
  const { t } = useI18n();
  const navigate = useNavigate();
  const { ready, bubbleEnabled, hasAccess, items, count } = useDeskInbox();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const [coords, setCoords] = useState<{ top: number; left: number; width: number } | null>(null);

  useLayoutEffect(() => {
    if (!open || !rootRef.current) {
      return;
    }
    const rect = rootRef.current.getBoundingClientRect();
    const width = Math.min(288, window.innerWidth - 16);
    let left = variant === 'header' ? rect.right - width : rect.left;
    left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
    let top = rect.bottom + 8;
    if (top + 320 > window.innerHeight) {
      top = Math.max(8, rect.top - 320);
    }
    setCoords({ top, left, width });
  }, [open, variant, count]);

  useEffect(() => {
    const onDoc = (event: MouseEvent) => {
      const target = event.target as Node;
      if (rootRef.current?.contains(target) || panelRef.current?.contains(target)) {
        return;
      }
      setOpen(false);
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onDoc);
      document.removeEventListener('keydown', onKey);
    };
  }, []);

  if (!ready || bubbleEnabled || !hasAccess || count < 1) {
    return null;
  }

  const label = `${t('platform.account.desk.beacon')} (${count})`;
  const compact = variant !== 'header';

  const panel =
    open && coords ? (
      <div
        ref={panelRef}
        data-testid="desk-beacon-panel"
        className="desk-solid-panel fixed z-[400] rounded-xl overflow-hidden"
        style={{ top: coords.top, left: coords.left, width: coords.width }}
      >
        <p className="px-3 py-2 text-xs font-semibold border-b border-slate-200">{t('platform.account.desk.queueTitle')}</p>
        <ul className="max-h-64 overflow-y-auto">
          {items.map((item) => (
            <li key={`${item.kind}-${item.id}`} className="border-t border-slate-200 first:border-t-0">
              <button
                type="button"
                className="w-full text-left px-3 py-2 text-sm hover:bg-black/5"
                onClick={() => {
                  setOpen(false);
                  navigate(item.href);
                }}
              >
                <span className="block font-semibold truncate">{item.title}</span>
                <span className="block text-xs opacity-70 truncate">{item.preview}</span>
                <DeskQueueItemMeta item={item} />
              </button>
            </li>
          ))}
        </ul>
      </div>
    ) : null;

  return (
    <div ref={rootRef} className="relative shrink-0" data-testid="desk-beacon">
      <button
        type="button"
        data-testid="desk-beacon-button"
        aria-label={label}
        title={t('platform.account.desk.beaconHint')}
        onClick={(event) => {
          event.preventDefault();
          event.stopPropagation();
          setOpen((current) => !current);
        }}
        className={`relative inline-flex items-center justify-center rounded-full ${
          compact ? 'h-8 w-8' : 'h-9 w-9'
        } bg-red-600 text-white shadow-[0_0_0_3px_rgba(220,38,38,0.35)]`}
      >
        <span className="pointer-events-none absolute inset-0 rounded-full bg-red-500 opacity-70 animate-ping" />
        <span className="pointer-events-none absolute -inset-1 rounded-full bg-red-500/30 animate-pulse" />
        <Bell className={`relative ${compact ? 'h-3.5 w-3.5' : 'h-4 w-4'}`} />
        <span
          data-testid="desk-beacon-count"
          className="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-amber-300 text-red-900 text-[11px] font-black leading-5 text-center ring-2 ring-white dark:ring-slate-900"
        >
          {count > 99 ? '99+' : count}
        </span>
      </button>
      {typeof document !== 'undefined' && panel ? createPortal(panel, document.body) : panel}
    </div>
  );
};
