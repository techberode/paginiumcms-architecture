import React, { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { useNavigate } from 'react-router-dom';
import { GripVertical, MessageCircle, Minimize2, PictureInPicture2 } from 'lucide-react';
import { authApi, type DeskItem } from '../../api/auth';
import { claimComment, replyToComment } from '../../api/comments';
import { messagesApi } from '../../api/messages';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import {
  canOpenDocumentPip,
  deskAlwaysOnTopPreferred,
  openDocumentPipWindow,
  setDeskAlwaysOnTopPreferred,
} from '../../utils/documentPictureInPicture';
import {
  clampDeskBubblePercent,
  deskBubbleOpensDown,
  deskBubblePositionStyle,
  normalizeDeskBubbleAnchor,
  type DeskBubbleAnchor,
} from '../../utils/deskBubbleLayout';
import { DeskQueueItemMeta } from './DeskQueueItemMeta';

function emitCommentRefresh(articleSlug?: string): void {
  window.dispatchEvent(new CustomEvent('paginium:desk-comment', { detail: { articleSlug } }));
}

/**
 * Presence + desk queue for team members (It.93o-3 / 93o-5).
 * Opaque card, account-controlled visibility, preset or dragged placement.
 */
export const SupportChatPresenceBubble: React.FC<{ variant?: 'admin' | 'public' }> = ({
  variant = 'admin',
}) => {
  const { user, updateUser } = useAuth();
  const { t } = useI18n();
  const navigate = useNavigate();
  const [visible, setVisible] = useState(false);
  const [online, setOnline] = useState(false);
  const [chatEnabled, setChatEnabled] = useState(false);
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState<DeskItem[]>([]);
  const [selected, setSelected] = useState<DeskItem | null>(null);
  const [reply, setReply] = useState('');
  const [busy, setBusy] = useState(false);
  const [inSupportTeam, setInSupportTeam] = useState(false);
  const [pipWindow, setPipWindow] = useState<Window | null>(null);
  const [anchor, setAnchor] = useState<DeskBubbleAnchor>('right');
  const [posX, setPosX] = useState(92);
  const [posY, setPosY] = useState(50);
  const dragRef = useRef<{ pointerId: number; offsetX: number; offsetY: number } | null>(null);
  const wrapRef = useRef<HTMLDivElement | null>(null);
  const posRef = useRef({ x: 92, y: 50 });
  const canPip = canOpenDocumentPip();

  const refresh = useCallback(async () => {
    if (!user) {
      setVisible(false);
      return;
    }
    const res = await authApi.desk().catch(() => null);
    if (!res?.success || !res.data) {
      return;
    }
    const enabled =
      typeof res.data.deskBubbleEnabled === 'boolean'
        ? res.data.deskBubbleEnabled
        : user.deskBubbleEnabled !== false;
    setVisible(enabled && (res.data.hasDesk || res.data.inSupportTeam || res.data.canReplyComments));
    setOnline(res.data.online);
    setChatEnabled(res.data.chatEnabled);
    setInSupportTeam(res.data.inSupportTeam);
    setItems(res.data.items ?? []);
    setAnchor(normalizeDeskBubbleAnchor(res.data.deskBubbleAnchor ?? user.deskBubbleAnchor));
    const nextX = clampDeskBubblePercent(res.data.deskBubbleX ?? user.deskBubbleX ?? 92);
    const nextY = clampDeskBubblePercent(res.data.deskBubbleY ?? user.deskBubbleY ?? 50);
    setPosX(nextX);
    setPosY(nextY);
    posRef.current = { x: nextX, y: nextY };
  }, [user]);

  useEffect(() => {
    void refresh();
    const id = window.setInterval(() => {
      void refresh();
    }, 30_000);
    return () => window.clearInterval(id);
  }, [refresh]);

  useEffect(() => {
    if (!visible || !online || !inSupportTeam) {
      return;
    }
    const tick = () => {
      void authApi.updatePresence(true);
    };
    const id = window.setInterval(tick, 30_000);
    tick();
    return () => window.clearInterval(id);
  }, [visible, online, inSupportTeam]);

  useEffect(() => {
    if (!pipWindow) {
      return;
    }
    const onClose = () => setPipWindow(null);
    pipWindow.addEventListener('pagehide', onClose);
    return () => pipWindow.removeEventListener('pagehide', onClose);
  }, [pipWindow]);

  useEffect(() => {
    if (visible || !pipWindow) {
      return;
    }
    pipWindow.close();
    setPipWindow(null);
  }, [visible, pipWindow]);

  const dockPip = useCallback(() => {
    pipWindow?.close();
    setPipWindow(null);
    setDeskAlwaysOnTopPreferred(false);
  }, [pipWindow]);

  const popOut = async () => {
    if (!canPip) {
      return;
    }
    setBusy(true);
    const next = await openDocumentPipWindow();
    setBusy(false);
    if (!next) {
      return;
    }
    setPipWindow(next);
    setOpen(true);
    setDeskAlwaysOnTopPreferred(true);
  };

  const persistPlacement = async (nextAnchor: DeskBubbleAnchor, x: number, y: number) => {
    const res = await authApi.updateProfile({
      deskBubbleEnabled: true,
      deskBubbleAnchor: nextAnchor,
      deskBubbleX: x,
      deskBubbleY: y,
    });
    const nextUser = res.data?.user;
    if (res.success && nextUser) {
      updateUser(nextUser);
    }
  };

  const startDrag = (event: React.PointerEvent<HTMLButtonElement>) => {
    if (pipWindow) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    const wrap = wrapRef.current;
    if (!wrap) {
      return;
    }
    const rect = wrap.getBoundingClientRect();
    dragRef.current = {
      pointerId: event.pointerId,
      offsetX: event.clientX - rect.left,
      offsetY: event.clientY - rect.top,
    };
    event.currentTarget.setPointerCapture(event.pointerId);
  };

  const moveDrag = (event: React.PointerEvent<HTMLButtonElement>) => {
    const drag = dragRef.current;
    if (!drag || event.pointerId !== drag.pointerId) {
      return;
    }
    const left = event.clientX - drag.offsetX;
    const top = event.clientY - drag.offsetY;
    const nextX = clampDeskBubblePercent((left / Math.max(window.innerWidth, 1)) * 100);
    const nextY = clampDeskBubblePercent((top / Math.max(window.innerHeight, 1)) * 100);
    posRef.current = { x: nextX, y: nextY };
    setAnchor('custom');
    setPosX(nextX);
    setPosY(nextY);
  };

  const endDrag = (event: React.PointerEvent<HTMLButtonElement>) => {
    const drag = dragRef.current;
    if (!drag || event.pointerId !== drag.pointerId) {
      return;
    }
    dragRef.current = null;
    void persistPlacement('custom', posRef.current.x, posRef.current.y);
  };

  const toggleOpen = () => {
    if (dragRef.current) {
      return;
    }
    const nextOpen = !open;
    setOpen(nextOpen);
    if (nextOpen && !pipWindow && canPip && deskAlwaysOnTopPreferred()) {
      void popOut();
    }
  };

  if (!visible) {
    return null;
  }

  const count = items.length;
  const floated = pipWindow !== null;
  const opensDown = deskBubbleOpensDown(anchor, posY);
  const panelClass = 'desk-solid-panel';

  const togglePresence = async () => {
    setBusy(true);
    if (!chatEnabled) {
      const saved = await authApi.updateProfile({ chatEnabled: true });
      if (saved.success) {
        setChatEnabled(true);
      }
    }
    const res = await authApi.updatePresence(!online);
    setBusy(false);
    if (res.success && res.data) {
      setOnline(res.data.online);
      setChatEnabled(res.data.chatEnabled);
    }
  };

  const sendReply = async () => {
    if (!selected || reply.trim().length < 2) {
      return;
    }
    setBusy(true);
    if (selected.kind === 'comment') {
      const result = await replyToComment(selected.id, reply.trim());
      setBusy(false);
      if (result.ok) {
        setReply('');
        emitCommentRefresh(selected.articleSlug);
        void refresh();
      }
      return;
    }
    const next = await messagesApi.reply(selected.id, reply.trim());
    setBusy(false);
    if (next) {
      setReply('');
      void refresh();
    }
  };

  const takeItem = async (item: DeskItem) => {
    setBusy(true);
    if (item.kind === 'comment') {
      await claimComment(item.id);
    } else {
      await messagesApi.claim(item.id);
    }
    setBusy(false);
    void refresh();
  };

  const openOnPage = (item: DeskItem) => {
    navigate(item.href);
    if (!floated) {
      setOpen(false);
    }
  };

  const queue = open ? (
    <div
      className={`${opensDown ? 'mt-3' : 'mb-3'} w-[min(22rem,calc(100vw-2rem))] rounded-2xl ${panelClass}`}
      data-testid="desk-queue"
    >
      <div className={`flex items-center justify-between gap-2 px-4 py-3 border-b ${variant === 'public' ? 'border-theme-border' : 'border-admin-border'}`}>
        <p className="text-sm font-semibold">{t('platform.account.desk.queueTitle')}</p>
        <div className="flex items-center gap-2">
          {inSupportTeam ? (
            <button type="button" className="text-xs underline" disabled={busy} onClick={() => void togglePresence()}>
              {online ? t('platform.account.chat.goOffline') : t('platform.account.chat.goOnline')}
            </button>
          ) : null}
          {floated ? (
            <button
              type="button"
              className="inline-flex items-center gap-1 text-xs underline"
              data-testid="desk-dock"
              onClick={dockPip}
              title={t('platform.account.desk.dockHint')}
            >
              <Minimize2 className="w-3 h-3" />
              {t('platform.account.desk.dock')}
            </button>
          ) : (
            <button
              type="button"
              className="inline-flex items-center gap-1 text-xs underline disabled:opacity-50"
              data-testid="desk-pop-out"
              disabled={!canPip || busy}
              onClick={() => void popOut()}
              title={canPip ? t('platform.account.desk.popOutHint') : t('platform.account.desk.popOutUnsupported')}
            >
              <PictureInPicture2 className="w-3 h-3" />
              {t('platform.account.desk.popOut')}
            </button>
          )}
        </div>
      </div>
      <ul className={`max-h-48 overflow-y-auto divide-y ${variant === 'public' ? 'divide-theme-border' : 'divide-admin-border'}`}>
        {items.length === 0 ? (
          <li className="px-4 py-3 text-xs opacity-70">{t('platform.account.desk.empty')}</li>
        ) : (
          items.map((item) => (
            <li key={`${item.kind}-${item.id}`}>
              <button
                type="button"
                className={`w-full text-left px-4 py-2 text-sm hover:bg-black/5 ${
                  selected?.id === item.id ? 'bg-black/5' : ''
                }`}
                onClick={() => setSelected(item)}
              >
                <span className="block font-semibold truncate">{item.title}</span>
                <span className="block text-xs opacity-70 truncate">{item.preview}</span>
                <DeskQueueItemMeta item={item} />
              </button>
            </li>
          ))
        )}
      </ul>
      {selected ? (
        <div className={`px-4 py-3 space-y-2 border-t ${variant === 'public' ? 'border-theme-border' : 'border-admin-border'}`}>
          <DeskQueueItemMeta item={selected} />
          <div className="flex flex-wrap gap-2">
            <button type="button" className="text-xs underline" onClick={() => openOnPage(selected)}>
              {t('platform.account.desk.open')}
            </button>
            {!selected.claimedBy ? (
              <button type="button" className="text-xs underline" disabled={busy} onClick={() => void takeItem(selected)}>
                {t('messages.desk.claim')}
              </button>
            ) : null}
          </div>
          <textarea
            className="w-full rounded-lg px-2 py-1.5 text-sm"
            rows={2}
            placeholder={t('platform.account.desk.replyPlaceholder')}
            value={reply}
            onChange={(event) => setReply(event.target.value)}
          />
          <button type="button" className="btn btn-primary text-xs" disabled={busy} onClick={() => void sendReply()}>
            {t('platform.account.desk.send')}
          </button>
        </div>
      ) : null}
    </div>
  ) : null;

  const bubbleButton = (
    <div className="flex items-center gap-1">
      {!floated ? (
        <button
          type="button"
          data-testid="desk-drag"
          data-desk-drag="1"
          className="desk-solid-chip rounded-full p-2 shadow-lg cursor-grab active:cursor-grabbing"
          title={t('platform.account.desk.drag')}
          onPointerDown={startDrag}
          onPointerMove={moveDrag}
          onPointerUp={endDrag}
          onPointerCancel={endDrag}
        >
          <GripVertical className="w-4 h-4" />
        </button>
      ) : null}
      <button
        type="button"
        data-testid="support-chat-bubble"
        className={`relative flex items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold shadow-lg ${
          online ? 'bg-emerald-600 text-white border border-emerald-700' : 'desk-solid-chip'
        }`}
        onClick={toggleOpen}
        title={t('platform.account.desk.bubbleHint')}
      >
        <MessageCircle className="w-4 h-4" />
        {t('platform.account.desk.bubble')}
        {count > 0 ? (
          <span
            className="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] leading-5 text-center"
            data-testid="desk-count"
          >
            {count}
          </span>
        ) : null}
      </button>
    </div>
  );

  const node = (
    <div
      ref={wrapRef}
      className={floated ? 'flex h-full min-h-[100dvh] flex-col justify-end p-3' : 'fixed z-[300]'}
      style={floated ? undefined : deskBubblePositionStyle(anchor, posX, posY, variant)}
      data-testid="support-chat-bubble-wrap"
    >
      {opensDown ? (
        <>
          {bubbleButton}
          {queue}
        </>
      ) : (
        <>
          {queue}
          {bubbleButton}
        </>
      )}
    </div>
  );

  const host = pipWindow?.document.body ?? (typeof document !== 'undefined' ? document.body : null);
  return host ? createPortal(node, host) : node;
};
