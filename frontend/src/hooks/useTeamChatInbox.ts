import { useContext, useEffect, useState } from 'react';
import { teamChatApi, type TeamChatInboxItem } from '../api/teamChat';
import { AuthContext } from '../context/AuthContext';

export interface TeamChatInboxState {
  ready: boolean;
  hasAccess: boolean;
  items: TeamChatInboxItem[];
  count: number;
}

export function useTeamChatInbox(): TeamChatInboxState {
  const user = useContext(AuthContext)?.user ?? null;
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
    };

    void load();
    const id = window.setInterval(() => {
      void load();
    }, 20_000);
    return () => {
      cancelled = true;
      window.clearInterval(id);
    };
  }, [user?.hasTeamChat, user?.id]);

  return {
    ready,
    hasAccess: Boolean(user?.hasTeamChat),
    items,
    count,
  };
}
