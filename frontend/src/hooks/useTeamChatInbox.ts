import { useContext, useEffect, useRef, useState } from 'react';
import { teamChatApi, type TeamChatInboxItem } from '../api/teamChat';
import { AuthContext } from '../context/AuthContext';
import { useI18n } from '../context/I18nContext';

export interface TeamChatInboxState {
  ready: boolean;
  hasAccess: boolean;
  items: TeamChatInboxItem[];
  count: number;
}

export function useTeamChatInbox(): TeamChatInboxState {
  const user = useContext(AuthContext)?.user ?? null;
  const { t } = useI18n();
  const previousCount = useRef<number | null>(null);
  const [items, setItems] = useState<TeamChatInboxItem[]>([]);
  const [count, setCount] = useState(0);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!user?.hasTeamChat) {
      setReady(false);
      setItems([]);
      setCount(0);
      return;
    }

    let cancelled = false;
    const load = async () => {
      const inbox = await teamChatApi.inbox().catch(() => null);
      if (cancelled || inbox === null) {
        return;
      }
      setItems(inbox.items);
      setCount(inbox.count);
      setReady(true);
      if (
        previousCount.current !== null
        && inbox.count > previousCount.current
        && typeof Notification !== 'undefined'
        && Notification.permission === 'granted'
      ) {
        const newest = inbox.items[0];
        new Notification(t('platform.teamChat.notifyGeneric'), {
          body: newest?.preview || newest?.teamName || '',
        });
      }
      previousCount.current = inbox.count;
    };

    void load();
    const id = window.setInterval(() => {
      void load();
    }, 20_000);
    return () => {
      cancelled = true;
      window.clearInterval(id);
    };
  }, [t, user?.hasTeamChat, user?.id]);

  return {
    ready,
    hasAccess: Boolean(user?.hasTeamChat),
    items,
    count,
  };
}
