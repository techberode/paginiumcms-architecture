import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Download, Hash, Loader2, Paperclip, RefreshCw, Search, Send, Trash2, Upload } from 'lucide-react';
import { teamChatApi, type TeamChatMessage, type TeamChatRoom } from '../../api/teamChat';
import type { TeamType } from '../../api/teams';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { markdownToHtml } from '../../utils/contentEditor';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import { ADMIN_INPUT, ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';

type ComposerKind = 'text' | 'code' | 'file';

export const TeamChatView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [searchParams] = useSearchParams();
  const roomFromUrl = searchParams.get('room') ?? '';
  const [rooms, setRooms] = useState<TeamChatRoom[]>([]);
  const [teamId, setTeamId] = useState<string>('');
  const [messages, setMessages] = useState<TeamChatMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [kind, setKind] = useState<ComposerKind>('text');
  const [body, setBody] = useState('');
  const [language, setLanguage] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<TeamChatMessage[] | null>(null);
  const [searching, setSearching] = useState(false);
  const [clearingHistory, setClearingHistory] = useState(false);

  const loadRooms = useCallback(async () => {
    setLoading(true);
    try {
      const next = await teamChatApi.rooms();
      setRooms(next);
      setTeamId((current) => {
        if (roomFromUrl !== '' && next.some((room) => room.id === roomFromUrl)) {
          return roomFromUrl;
        }
        return current || next[0]?.id || '';
      });
    } catch {
      toast.error(t('platform.teamChat.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [roomFromUrl, t, toast]);

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
    setSearchResults(null);
    setSearchQuery('');
    void loadMessages(teamId);
  }, [loadMessages, teamId]);

  const activeRoom = useMemo(
    () => rooms.find((room) => room.id === teamId) ?? null,
    [rooms, teamId]
  );

  const canManageHistory = activeRoom?.canManageHistory === true;

  const roomTypeLabel = (type: string | undefined) => {
    if (type === 'external') {
      return t('platform.teams.externalBadge');
    }
    const known = ['editorial', 'support', 'ops', 'custom'] as const;
    if (type !== undefined && (known as readonly string[]).includes(type)) {
      return t(`platform.teams.types.${type as TeamType}`);
    }

    return type ?? '';
  };

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

  const runSearch = async () => {
    if (teamId === '' || searchQuery.trim() === '') {
      setSearchResults(null);
      return;
    }
    setSearching(true);
    try {
      const hits = await teamChatApi.search(teamId, searchQuery);
      setSearchResults(hits);
    } catch {
      toast.error(t('platform.teamChat.toast.loadFailed'));
    } finally {
      setSearching(false);
    }
  };

  const handleClearHistory = async () => {
    if (teamId === '' || !canManageHistory) {
      return;
    }
    const confirmed = window.confirm(t('platform.teamChat.historyClearConfirm'));
    if (!confirmed) {
      return;
    }
    setClearingHistory(true);
    try {
      const response = await teamChatApi.clearHistory(teamId);
      if (!response.success || response.data === undefined) {
        toast.error(response.error || t('platform.teamChat.historyClearFailed'));
        return;
      }
      toast.success(t('platform.teamChat.historyCleared', { count: String(response.data.deleted) }));
      setSearchResults(null);
      setSearchQuery('');
      await loadMessages(teamId);
    } finally {
      setClearingHistory(false);
    }
  };

  const handleImportHistory = async (file: File | undefined) => {
    if (!file || teamId === '') {
      return;
    }
    try {
      const text = await file.text();
      const archive = JSON.parse(text) as unknown;
      const response = await teamChatApi.importArchive(teamId, archive);
      if (!response.success || response.data === undefined) {
        toast.error(response.error || t('platform.teamChat.historyImportFailed'));
        return;
      }
      toast.success(t('platform.teamChat.historyImported', { count: String(response.data.imported) }));
      setSearchResults(null);
      await loadMessages(teamId);
    } catch {
      toast.error(t('platform.teamChat.historyImportFailed'));
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
        <div className="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)] min-h-[min(70vh,40rem)] max-h-[calc(100vh-11rem)]">
          <aside className="rounded-2xl border border-admin-border bg-admin-card p-2 space-y-1 overflow-y-auto min-h-0">
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
                  {room.type ? (
                    <span className={`mail-tag mail-tag-3 ${room.id === teamId ? 'opacity-90' : ''}`}>
                      {roomTypeLabel(room.type)}
                    </span>
                  ) : null}
                  {room.shared ? (
                    <span className={`mail-tag mail-tag-2 ${room.id === teamId ? 'opacity-90' : ''}`}>
                      {t('platform.teamChat.sharedBadge')}
                    </span>
                  ) : null}
                </span>
              </button>
            ))}
          </aside>

          <section className="rounded-2xl border border-admin-border bg-admin-card flex flex-col min-h-0 h-full">
            <header className="px-4 py-3 border-b border-admin-border space-y-2 shrink-0">
              <div className="text-sm font-semibold text-admin-text">
                {activeRoom ? `#${activeRoom.name}` : t('platform.teamChat.selectRoom')}
              </div>
              {teamId !== '' && canManageHistory ? (
                <div className="flex flex-wrap items-center gap-2">
                  <label className="flex flex-1 min-w-[12rem] items-center gap-2 text-xs">
                    <Search className="w-3.5 h-3.5 text-admin-muted shrink-0" />
                    <input
                      data-testid="team-chat-history-search"
                      className={`${ADMIN_INPUT} text-xs py-1.5`}
                      value={searchQuery}
                      onChange={(event) => setSearchQuery(event.target.value)}
                      placeholder={t('platform.teamChat.historySearchPlaceholder')}
                      onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                          void runSearch();
                        }
                      }}
                    />
                  </label>
                  <button
                    type="button"
                    data-testid="team-chat-history-search-btn"
                    disabled={searching}
                    onClick={() => void runSearch()}
                    className="px-2 py-1.5 rounded-lg border border-admin-border text-xs text-admin-text"
                  >
                    {t('platform.teamChat.historySearch')}
                  </button>
                  <a
                    href={teamChatApi.exportUrl(teamId)}
                    data-testid="team-chat-history-export"
                    className="px-2 py-1.5 rounded-lg border border-admin-border text-xs text-admin-text hover:underline"
                  >
                    {t('platform.teamChat.historyExport')}
                  </a>
                  <label className="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg border border-admin-border text-xs text-admin-text cursor-pointer">
                    <Upload className="w-3.5 h-3.5" />
                    {t('platform.teamChat.historyImport')}
                    <input
                      type="file"
                      accept="application/json,.json"
                      className="sr-only"
                      data-testid="team-chat-history-import"
                      onChange={(event) => void handleImportHistory(event.target.files?.[0])}
                    />
                  </label>
                  <button
                    type="button"
                    data-testid="team-chat-history-clear"
                    disabled={clearingHistory}
                    onClick={() => void handleClearHistory()}
                    className="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg border border-rose-200 text-xs text-rose-700 dark:border-rose-900 dark:text-rose-300"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    {t('platform.teamChat.historyClear')}
                  </button>
                </div>
              ) : null}
            </header>
            <ol className="flex-1 min-h-0 overflow-y-auto p-4 space-y-3" data-testid="team-chat-messages">
              {(searchResults ?? messages).map((message) => (
                <li key={message.id} className="rounded-xl bg-admin-canvas px-3 py-2 text-admin-text">
                  <div className="text-xs font-medium text-admin-muted mb-1">
                    {message.authorName}
                  </div>
                  <TeamChatBody message={message} teamId={teamId} />
                </li>
              ))}
              {searchResults && searchResults.length === 0 ? (
                <li className="text-sm text-admin-muted">{t('platform.teamChat.historySearchEmpty')}</li>
              ) : null}
            </ol>
            <footer className="border-t border-admin-border p-3 space-y-2 shrink-0">
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
  return (
    <div
      className="prose prose-sm dark:prose-invert max-w-none text-admin-text [&_p]:text-admin-text [&_strong]:text-admin-text [&_a]:text-admin-primary"
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}

export default TeamChatView;
