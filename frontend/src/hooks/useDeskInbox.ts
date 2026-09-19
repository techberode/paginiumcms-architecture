import { useContext, useEffect, useState } from 'react';
import { authApi, type DeskItem } from '../api/auth';
import { AuthContext } from '../context/AuthContext';

export interface DeskInboxState {
  ready: boolean;
  bubbleEnabled: boolean;
  hasAccess: boolean;
  canReplyComments: boolean;
  /** In-item comment/message composer — only when the Desk bubble is off. */
  inPageChatActive: boolean;
  items: DeskItem[];
  count: number;
}

export function useDeskInbox(): DeskInboxState {
  const user = useContext(AuthContext)?.user ?? null;
  const [items, setItems] = useState<DeskItem[]>([]);
  const [hasAccess, setHasAccess] = useState(false);
  const [canReplyComments, setCanReplyComments] = useState(false);
  const [bubbleEnabled, setBubbleEnabled] = useState(true);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!user) {
      setReady(false);
      setHasAccess(false);
      setCanReplyComments(false);
      setItems([]);
      setBubbleEnabled(true);
      return;
    }

    let cancelled = false;
    const load = async () => {
      const res = await authApi.desk().catch(() => null);
      if (cancelled || !res?.success || !res.data) {
        return;
      }
      setItems(res.data.items ?? []);
      setCanReplyComments(Boolean(res.data.canReplyComments));
      setHasAccess(Boolean(res.data.hasDesk || res.data.inSupportTeam || res.data.canReplyComments));
      setBubbleEnabled(
        typeof res.data.deskBubbleEnabled === 'boolean'
          ? res.data.deskBubbleEnabled
          : user.deskBubbleEnabled !== false
      );
      setReady(true);
    };

    void load();
    const id = window.setInterval(() => {
      void load();
    }, 30_000);
    return () => {
      cancelled = true;
      window.clearInterval(id);
    };
  }, [user]);

  return {
    ready,
    bubbleEnabled,
    hasAccess,
    canReplyComments,
    inPageChatActive: ready ? !bubbleEnabled : user?.deskBubbleEnabled === false,
    items,
    count: items.length,
  };
}
