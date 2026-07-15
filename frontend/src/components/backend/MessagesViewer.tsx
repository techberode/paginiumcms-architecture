// frontend/src/components/backend/MessagesViewer.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Mail, Trash2, Eye, EyeOff } from 'lucide-react';
import { ContactMessage, deleteMessage, listMessages, markMessageRead } from '../../api/messages';
import { useToast } from '../../hooks/useToast';

export const MessagesViewer: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<ContactMessage[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listMessages());
    } catch {
      showError('Failed to load messages.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const toggleRead = async (msg: ContactMessage) => {
    const ok = await markMessageRead(msg.id, !msg.isRead);
    if (ok) await load();
  };

  const remove = async (id: string) => {
    if (!confirm('Delete this message?')) return;
    const ok = await deleteMessage(id);
    if (ok) {
      showSuccess('Message deleted.');
      await load();
    }
  };

  const unread = items.filter((m) => !m.isRead).length;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Mail className="w-6 h-6 text-violet-500" />
          Contact Inbox ({items.length})
        </h1>
        <p className="text-sm text-gray-500 mt-1">Unread: {unread}</p>
      </div>

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
        </div>
      ) : items.length === 0 ? (
        <div className="card card-body text-center text-gray-500 py-12">No messages yet.</div>
      ) : (
        <div className="space-y-4">
          {items.map((msg) => (
            <div
              key={msg.id}
              className={`card ${!msg.isRead ? 'ring-2 ring-indigo-500/30' : ''}`}
            >
              <div className="card-body space-y-2">
                <div className="flex justify-between gap-4 flex-wrap">
                  <div>
                    <p className="font-medium">{msg.name}</p>
                    <p className="text-xs text-gray-500">{msg.email}</p>
                  </div>
                  <p className="text-xs text-gray-500">{new Date(msg.createdAt).toLocaleString()}</p>
                </div>
                <p className="font-semibold text-sm">{msg.subject}</p>
                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{msg.message}</p>
                <div className="flex gap-2">
                  <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void toggleRead(msg)}>
                    {msg.isRead ? <EyeOff className="w-3 h-3" /> : <Eye className="w-3 h-3" />}
                  </button>
                  <button type="button" className="btn btn-danger text-xs px-2 py-1 ml-auto" onClick={() => void remove(msg.id)}>
                    <Trash2 className="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default MessagesViewer;
