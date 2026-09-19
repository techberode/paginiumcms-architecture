import React, { useState } from 'react';
import { messagesApi, type ContactMessage } from '../../api/messages';
import { registrationInvitesApi } from '../../api/registrationInvites';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

export const MessageMessengerThread: React.FC<{
  message: ContactMessage;
  composerEnabled?: boolean;
  onUpdated: (next: ContactMessage) => void;
}> = ({ message, composerEnabled = true, onUpdated }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [body, setBody] = useState('');
  const [busy, setBusy] = useState(false);
  const [inviteUrl, setInviteUrl] = useState('');

  const replies = [
    {
      id: `${message.id}-root`,
      authorType: 'visitor',
      authorName: message.name,
      body: message.message,
      createdAt: message.createdAt,
    },
    ...(message.thread ?? []),
  ];

  const send = async () => {
    if (body.trim().length < 2) {
      return;
    }
    setBusy(true);
    const next = await messagesApi.reply(message.id, body.trim());
    setBusy(false);
    if (next) {
      setBody('');
      onUpdated(next);
    }
  };

  const claim = async () => {
    setBusy(true);
    const next = await messagesApi.claim(message.id);
    setBusy(false);
    if (next) {
      onUpdated(next);
    }
  };

  const release = async () => {
    setBusy(true);
    const next = await messagesApi.release(message.id);
    setBusy(false);
    if (next) {
      onUpdated(next);
    }
  };

  return (
    <div className="rounded-xl border border-admin-border bg-admin-canvas p-3 space-y-3" data-testid={`message-thread-${message.id}`}>
      <div className="max-h-80 overflow-y-auto space-y-2">
        {replies.map((row) => {
          const staff = row.authorType === 'staff';
          return (
            <div key={row.id} className={`flex ${staff ? 'justify-end' : 'justify-start'}`}>
              <div
                className={`max-w-[80%] rounded-2xl px-3 py-2 text-sm ${
                  staff
                    ? 'bg-admin-primary text-white rounded-br-md'
                    : 'bg-white dark:bg-gray-800 border border-admin-border rounded-bl-md'
                }`}
              >
                <p className="text-[11px] opacity-70 mb-0.5">{row.authorName || (staff ? t('messages.thread.staff') : message.name)}</p>
                <p className="whitespace-pre-wrap">{row.body}</p>
              </div>
            </div>
          );
        })}
      </div>
      {message.handleStatus === 'in_progress' && message.claimedByName ? (
        <p className="text-xs text-admin-muted">{t('messages.desk.claimedBy', { name: message.claimedByName })}</p>
      ) : null}
      <div className="flex flex-wrap gap-2">
        {message.canClaim && !message.claimedBy ? (
          <button type="button" className="btn btn-secondary text-xs" disabled={busy} onClick={() => void claim()}>
            {t('messages.desk.claim')}
          </button>
        ) : null}
        {message.handleStatus === 'in_progress' ? (
          <button type="button" className="btn btn-secondary text-xs" disabled={busy} onClick={() => void release()}>
            {t('messages.desk.release')}
          </button>
        ) : null}
        {message.registrationRequest ? (
          <button
            type="button"
            className="btn btn-secondary text-xs"
            data-testid={`message-invite-${message.id}`}
            disabled={busy}
            onClick={() => {
              void (async () => {
                setBusy(true);
                const response = await registrationInvitesApi.create({
                  email: message.email,
                  sendMail: true,
                  source: 'contact',
                });
                setBusy(false);
                if (!response.success || !response.data) {
                  toast.error(response.error || t('messages.invite.failed'));
                  return;
                }
                setInviteUrl(response.data.url);
                toast.success(
                  response.data.mailed ? t('messages.invite.sent') : t('messages.invite.created')
                );
              })();
            }}
          >
            {t('messages.invite.send')}
          </button>
        ) : null}
      </div>
      {inviteUrl ? <p className="text-xs break-all text-admin-muted">{inviteUrl}</p> : null}
      {composerEnabled && message.canReply ? (
        <div className="flex gap-2">
          <textarea
            className="flex-1 rounded-lg border border-admin-border bg-admin-card px-3 py-2 text-sm"
            rows={2}
            placeholder={t('messages.thread.placeholder')}
            value={body}
            onChange={(event) => setBody(event.target.value)}
          />
          <button type="button" className="btn btn-primary self-end text-sm" disabled={busy} onClick={() => void send()}>
            {t('messages.thread.send')}
          </button>
        </div>
      ) : null}
    </div>
  );
};
