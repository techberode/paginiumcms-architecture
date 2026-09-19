import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Download, Hash, Loader2, Paperclip, RefreshCw, Send } from 'lucide-react';
import { teamChatApi, type TeamChatMessage, type TeamChatRoom } from '../../api/teamChat';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { markdownToHtml } from '../../utils/contentEditor';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';

type ComposerKind = 'text' | 'code' | 'file';

export const TeamChatView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [rooms, setRooms] = useState<TeamChatRoom[]>([]);
  const [teamId, setTeamId] = useState<string>('');
  const [messages, setMessages] = useState<TeamChatMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [kind, setKind] = useState<ComposerKind>('text');
  const [body, setBody] = useState('');
  const [language, setLanguage] = useState('');

  const loadRooms = useCallback(async () => {
    setLoading(true);
    try {
      const next = await teamChatApi.rooms();
      setRooms(next);
      setTeamId((current) => current || next[0]?.id || '');
    } catch {
      toast.error(t('platform.teamChat.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t, toast]);

  const loadMessages = useCallback(async (id: string) => {
    if (id === '') {
      setMessages([]);
      return;
    }
    try {
      setMessages(await teamChatApi.messages(id));
    } catch {
      toast.error(t('platform.teamChat.toast.loadFailed'));
    }
  }, [t, toast]);

  useEffect(() => {
    void loadRooms();
  }, [loadRooms]);

  useEffect(() => {
    void loadMessages(teamId);
  }, [loadMessages, teamId]);

  const activeRoom = useMemo(
    () => rooms.find((room) => room.id === teamId) ?? null,
    [rooms, teamId]
  );

  const handleSend = async () => {
    if (teamId === '') {
      return;
    }
    if (kind === 'file' || body.trim() === '') {
      toast.error(t('platform.teamChat.toast.bodyRequired'));
      return;
    }

    setSending(true);
    try {
      const response = await teamChatApi.post(teamId, {
        kind,
        body: body.trim(),
        language: kind === 'code' ? language : '',
      });
      if (!response.success) {
        toast.error(response.error || t('platform.teamChat.toast.sendFailed'));
        return;
      }
      setBody('');
      await loadMessages(teamId);
    } finally {
      setSending(false);
    }
  };

  const handleUpload = async (file: File | undefined) => {
    if (!file || teamId === '') {
      return;
    }
    setSending(true);
    try {
      const response = await teamChatApi.upload(teamId, file);
      if (!response.success) {
        toast.error(response.error || t('platform.teamChat.toast.uploadFailed'));
        return;
      }
      await loadMessages(teamId);
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="p-6 space-y-6" data-testid="team-chat">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
            <Hash className="w-7 h-7 text-admin-primary" />
            {t('platform.teamChat.title')}
          </h1>
          <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.teamChat.subtitle')}</p>
        </div>
        <button
          type="button"
          onClick={() => void loadRooms()}
          className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-admin-border text-sm text-admin-text"
        >
          <RefreshCw className="w-4 h-4" />
          {t('platform.teamChat.refresh')}
        </button>
      </div>

      {loading ? (
        <p className="text-sm text-admin-muted">{t('platform.teamChat.loading')}</p>
      ) : rooms.length === 0 ? (
        <p className="text-sm text-admin-muted" data-testid="team-chat-empty">
          {t('platform.teamChat.empty')}
        </p>
      ) : (
        <div className="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)] min-h-[32rem]">
          <aside className="rounded-2xl border border-admin-border bg-admin-card p-2 space-y-1">
            {rooms.map((room) => (
              <button
                key={room.id}
                type="button"
                data-testid={`team-chat-room-${room.id}`}
                onClick={() => setTeamId(room.id)}
                className={`w-full text-left px-3 py-2 rounded-xl text-sm ${
                  room.id === teamId
                    ? 'bg-admin-primary text-white'
                    : 'text-admin-text hover:bg-admin-canvas'
                }`}
              >
                <span className="flex items-center gap-1.5">
                  <span>#{room.name}</span>
                  <span className={`mail-tag mail-tag-3 ${room.id === teamId ? 'opacity-90' : ''}`}>
                    {t('platform.teams.externalBadge')}
                  </span>
                </span>
              </button>
            ))}
          </aside>

          <section className="rounded-2xl border border-admin-border bg-admin-card flex flex-col min-h-[32rem]">
            <header className="px-4 py-3 border-b border-admin-border text-sm font-semibold text-admin-text">
              {activeRoom ? `#${activeRoom.name}` : t('platform.teamChat.selectRoom')}
            </header>
            <ol className="flex-1 overflow-y-auto p-4 space-y-3" data-testid="team-chat-messages">
              {messages.map((message) => (
                <li key={message.id} className="rounded-xl bg-admin-canvas px-3 py-2">
                  <div className="text-xs text-admin-muted mb-1">
                    {message.authorName}
                  </div>
                  <TeamChatBody message={message} teamId={teamId} />
                </li>
              ))}
            </ol>
            <footer className="border-t border-admin-border p-3 space-y-2">
              <div className="flex flex-wrap gap-2 text-xs">
                {(['text', 'code', 'file'] as const).map((next) => (
                  <button
                    key={next}
                    type="button"
                    data-testid={`team-chat-kind-${next}`}
                    onClick={() => setKind(next)}
                    className={`px-2 py-1 rounded-lg border ${
                      kind === next
                        ? 'border-admin-primary text-admin-primary'
                        : 'border-admin-border text-admin-muted'
                    }`}
                  >
                    {t(`platform.teamChat.kinds.${next}`)}
                  </button>
                ))}
              </div>
              {kind === 'file' ? (
                <label className="flex items-center gap-2 text-sm text-admin-text">
                  <Paperclip className="w-4 h-4" />
                  <input
                    data-testid="team-chat-file"
                    type="file"
                    disabled={sending}
                    onChange={(event) => {
                      void handleUpload(event.target.files?.[0]);
                      event.target.value = '';
                    }}
                  />
                </label>
              ) : (
                <>
                  {kind === 'code' ? (
                    <input
                      data-testid="team-chat-language"
                      value={language}
                      onChange={(event) => setLanguage(event.target.value)}
                      placeholder={t('platform.teamChat.languagePlaceholder')}
                      className={ADMIN_INPUT}
                    />
                  ) : null}
                  <textarea
                    data-testid="team-chat-body"
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    rows={kind === 'code' ? 8 : 3}
                    placeholder={
                      kind === 'code'
                        ? t('platform.teamChat.codePlaceholder')
                        : t('platform.teamChat.textPlaceholder')
                    }
                    className={ADMIN_INPUT}
                  />
                  <button
                    type="button"
                    data-testid="team-chat-send"
                    disabled={sending}
                    onClick={() => void handleSend()}
                    className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-admin-primary text-white text-sm"
                  >
                    {sending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                    {t('platform.teamChat.send')}
                  </button>
                </>
              )}
            </footer>
          </section>
        </div>
      )}
    </div>
  );
};

function TeamChatBody({ message, teamId }: { message: TeamChatMessage; teamId: string }) {
  const { t } = useI18n();
  if (message.kind === 'file' && message.file) {
    return (
      <a
        href={teamChatApi.downloadUrl(teamId, message.file.id)}
        download={message.file.name}
        className="inline-flex items-center gap-2 text-sm text-admin-primary"
      >
        <Download className="w-4 h-4" />
        {t('platform.teamChat.download', { name: message.file.name })}
      </a>
    );
  }
  if (message.kind === 'code') {
    return (
      <pre className="overflow-x-auto text-xs bg-slate-950 text-slate-100 rounded-lg p-3">
        <code>{message.body}</code>
      </pre>
    );
  }
  const html = sanitizePublicHtml(markdownToHtml(message.body));
  return <div className="prose prose-sm dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: html }} />;
}

export default TeamChatView;
