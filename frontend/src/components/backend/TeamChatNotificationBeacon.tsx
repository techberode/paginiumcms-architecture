import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate } from 'react-router-dom';
import { MessageSquare } from 'lucide-react';
import { useTeamChatInbox } from '../../hooks/useTeamChatInbox';
import { useI18n } from '../../context/I18nContext';

interface TeamChatNotificationBeaconProps {
  variant: 'header' | 'sidebar' | 'topnav';
}

/**
 * Team-chat notifier beside the account menu (Desk uses a separate bell).
 * Labels: Chat · {team name}; multiple active rooms show a queue.
 */
export const TeamChatNotificationBeacon: React.FC<TeamChatNotificationBeaconProps> = ({ variant }) => {
  const { t } = useI18n();
  const navigate = useNavigate();
  const { ready, hasAccess, items, count } = useTeamChatInbox();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const [coords, setCoords] = useState<{ top: number; left: number; width: number } | null>(null);

  useLayoutEffect(() => {
    if (!open || !rootRef.current) {
      return;
    }
    const rect = rootRef.current.getBoundingClientRect();
    const width = Math.min(320, window.innerWidth - 16);
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

  if (!ready || !hasAccess || count < 1) {
    return null;
  }

  const roomCount = items.length;
  const ariaLabel =
    roomCount === 1
      ? t('platform.teamChat.beaconOne', { name: items[0]?.teamName ?? '' })
      : t('platform.teamChat.beaconMany', { count: String(roomCount) });
  const compact = variant !== 'header';

  const panel =
    open && coords ? (
      <div
        ref={panelRef}
        data-testid="team-chat-beacon-panel"
        className="desk-solid-panel fixed z-[400] rounded-xl overflow-hidden"
        style={{ top: coords.top, left: coords.left, width: coords.width }}
      >
        <p className="px-3 py-2 text-xs font-semibold border-b border-slate-200">
          {roomCount === 1
            ? t('platform.teamChat.beaconOne', { name: items[0]?.teamName ?? '' })
            : t('platform.teamChat.queueTitle')}
        </p>
        <ul className="max-h-64 overflow-y-auto">
          {items.map((item) => (
            <li key={item.teamId} className="border-t border-slate-200 first:border-t-0">
              <button
                type="button"
                className="w-full text-left px-3 py-2 text-sm hover:bg-black/5"
                onClick={() => {
                  setOpen(false);
                  navigate(`/team-chat?room=${encodeURIComponent(item.teamId)}`);
                }}
              >
                <span className="inline-flex flex-wrap items-center gap-1.5 font-semibold">
                  <span className="mail-tag mail-tag-2">{t('platform.teamChat.chatBadge')}</span>
                  <span className="truncate">{item.teamName}</span>
                  {item.unread > 1 ? (
                    <span className="text-xs font-normal text-admin-muted">×{item.unread}</span>
                  ) : null}
                </span>
                <span className="block text-xs opacity-70 truncate mt-0.5">
                  {item.preview || t('platform.teamChat.notifyGeneric')}
                </span>
              </button>
            </li>
          ))}
        </ul>
      </div>
    ) : null;

  return (
    <div ref={rootRef} className="relative shrink-0" data-testid="team-chat-beacon">
      <button
        type="button"
        data-testid="team-chat-beacon-button"
        aria-label={ariaLabel}
        title={t('platform.teamChat.beaconHint')}
        onClick={(event) => {
          event.preventDefault();
          event.stopPropagation();
          if (roomCount === 1 && items[0]) {
            navigate(`/team-chat?room=${encodeURIComponent(items[0].teamId)}`);
            return;
          }
          setOpen((current) => !current);
        }}
        className={`relative inline-flex items-center justify-center rounded-full ${
          compact ? 'h-8 w-8' : 'h-9 w-9'
        } bg-teal-600 text-white shadow-[0_0_0_3px_rgba(13,148,136,0.35)]`}
      >
        <span className="pointer-events-none absolute inset-0 rounded-full bg-teal-500 opacity-70 animate-ping" />
        <MessageSquare className={`relative ${compact ? 'h-3.5 w-3.5' : 'h-4 w-4'}`} />
        <span
          data-testid="team-chat-beacon-count"
          className="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-amber-300 text-teal-950 text-[11px] font-black leading-5 text-center ring-2 ring-white dark:ring-slate-900"
        >
          {count > 99 ? '99+' : count}
        </span>
      </button>
      {typeof document !== 'undefined' && panel ? createPortal(panel, document.body) : panel}
    </div>
  );
};
