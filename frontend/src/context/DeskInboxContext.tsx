import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { authApi, type DeskItem } from '../api/auth';
import { useAuth } from '../hooks/useAuth';
import { useDeskPolling } from '../hooks/useDeskPolling';

export interface DeskInboxPayload {
  chatEnabled: boolean;
  online: boolean;
  lastSeen: number;
  inSupportTeam: boolean;
  canPublicChat: boolean;
  hasDesk: boolean;
  canReplyComments: boolean;
  deskCount: number;
  items: DeskItem[];
  deskBubbleEnabled?: boolean;
  deskBubbleAnchor?: string;
  deskBubbleX?: number;
  deskBubbleY?: number;
}

export interface DeskInboxState {
  ready: boolean;
  bubbleEnabled: boolean;
  hasAccess: boolean;
  canReplyComments: boolean;
  /** In-item comment/message composer — only when the Desk bubble is off. */
  inPageChatActive: boolean;
  items: DeskItem[];
  count: number;
  data: DeskInboxPayload | null;
  refresh: () => Promise<boolean>;
  /** Remove queue rows immediately after approve / delete / mark handled (no full page reload). */
  removeItemsByKey: (keys: Array<{ kind: string; id: string }>) => void;
}

const EMPTY: DeskInboxState = {
  ready: false,
  bubbleEnabled: true,
  hasAccess: false,
  canReplyComments: false,
  inPageChatActive: false,
  items: [],
  count: 0,
  data: null,
  refresh: async () => false,
  removeItemsByKey: () => undefined,
};

const DeskInboxContext = createContext<DeskInboxState | null>(null);

export const DeskInboxProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user } = useAuth();
  const [items, setItems] = useState<DeskItem[]>([]);
  const [hasAccess, setHasAccess] = useState(false);
  const [canReplyComments, setCanReplyComments] = useState(false);
  const [bubbleEnabled, setBubbleEnabled] = useState(true);
  const [ready, setReady] = useState(false);
  const [data, setData] = useState<DeskInboxPayload | null>(null);

  const loadDesk = useCallback(async (): Promise<boolean> => {
    if (!user) {
      setItems([]);
      setHasAccess(false);
      setCanReplyComments(false);
      setData(null);
      setReady(false);
      return false;
    }
    const res = await authApi.desk().catch(() => null);
    if (!res?.success || !res.data) {
      return false;
    }
    const payload = res.data as DeskInboxPayload;
    setData(payload);
    setItems(payload.items ?? []);
    setCanReplyComments(Boolean(payload.canReplyComments));
    setHasAccess(Boolean(payload.hasDesk || payload.inSupportTeam || payload.canReplyComments));
    setBubbleEnabled(
      typeof payload.deskBubbleEnabled === 'boolean'
        ? payload.deskBubbleEnabled
        : user.deskBubbleEnabled !== false
    );
    setReady(true);
    return true;
  }, [user]);

  useDeskPolling(Boolean(user), loadDesk);

  const removeItemsByKey = useCallback((keys: Array<{ kind: string; id: string }>) => {
    if (keys.length === 0) {
      return;
    }
    const drop = (list: DeskItem[]) =>
      list.filter((item) => !keys.some((key) => key.kind === item.kind && key.id === item.id));
    setItems((current) => drop(current));
    setData((current) => {
      if (!current) {
        return current;
      }
      const nextItems = drop(current.items ?? []);
      return {
        ...current,
        items: nextItems,
        deskCount: nextItems.length,
      };
    });
  }, []);

  const value = useMemo((): DeskInboxState => {
    return {
      ready,
      bubbleEnabled,
      hasAccess,
      canReplyComments,
      inPageChatActive: ready ? !bubbleEnabled : user?.deskBubbleEnabled === false,
      items,
      count: items.length,
      data,
      refresh: loadDesk,
      removeItemsByKey,
    };
  }, [ready, bubbleEnabled, hasAccess, canReplyComments, items, data, loadDesk, removeItemsByKey, user?.deskBubbleEnabled]);

  return <DeskInboxContext.Provider value={value}>{children}</DeskInboxContext.Provider>;
};

export function useDeskInbox(): DeskInboxState {
  const ctx = useContext(DeskInboxContext);
  return ctx ?? EMPTY;
}
