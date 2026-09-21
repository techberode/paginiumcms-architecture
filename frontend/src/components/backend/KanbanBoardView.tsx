import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { GripVertical, Plus, Trash2 } from 'lucide-react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useConfirm } from '../../hooks/useConfirm';
import { AdminHintCard } from './AdminHintCard';
import {
  teamKanbanApi,
  type CannedReply,
  type KanbanBoard,
  type KanbanColumn,
  type KanbanIndex,
  type KanbanLabel,
  type KanbanStats,
  type KanbanTeamContext,
  type KanbanTeamSummary,
  type SupportTicket,
} from '../../api/kanban';
import { KanbanLabelChip } from './KanbanLabelChip';
import { TeamBadge } from './TeamBadge';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { AdminFormActions } from './AdminFormActions';
import { AdminTabs } from '../ui/AdminTabs';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';

const EMPTY: KanbanIndex = {
  board: { schema: 'support-board@1', columns: [], labels: [] },
  tickets: [],
  agents: [],
  cannedReplies: [],
};

function unixToDatetimeLocal(unix: number | null): string {
  if (unix === null || unix <= 0) {
    return '';
  }
  const date = new Date(unix * 1000);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function datetimeLocalToUnix(value: string): number | null {
  const trimmed = value.trim();
  if (trimmed === '') {
    return null;
  }
  const ms = new Date(trimmed).getTime();
  if (Number.isNaN(ms)) {
    return null;
  }
  return Math.floor(ms / 1000);
}

function formatDuration(seconds: number): string {
  if (seconds < 3600) {
    return `${Math.max(1, Math.round(seconds / 60))} min`;
  }
  if (seconds < 86400) {
    return `${(seconds / 3600).toFixed(1)} h`;
  }

  return `${(seconds / 86400).toFixed(1)} d`;
}

function isClosedColumn(column: KanbanColumn): boolean {
  const id = column.id.toLowerCase();
  const label = column.label.toLowerCase();
  return (
    id === 'closed'
    || id === 'done'
    || id === 'resolved'
    || label.includes('closed')
    || label.includes('done')
    || label.includes('uzav')
    || label.includes('hotov')
  );
}

export const KanbanBoardView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const confirm = useConfirm();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [tab, setTab] = useState('board');
  const [data, setData] = useState<KanbanIndex>(EMPTY);
  const [team, setTeam] = useState<KanbanTeamContext | null>(null);
  const [teams, setTeams] = useState<KanbanTeamSummary[]>([]);
  const [activeTeamId, setActiveTeamId] = useState('');
  const [forbidden, setForbidden] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [draftBoard, setDraftBoard] = useState<KanbanBoard>(EMPTY.board);
  const [draftCanned, setDraftCanned] = useState<CannedReply[]>([]);
  const [editing, setEditing] = useState<SupportTicket | null>(null);
  const [subject, setSubject] = useState('');
  const [noteBody, setNoteBody] = useState('');
  const [cannedPick, setCannedPick] = useState('');
  const [filterAssigneeId, setFilterAssigneeId] = useState('');
  const [filterLabelId, setFilterLabelId] = useState('');

  const load = useCallback(async () => {
    const queryTeam = searchParams.get('team') ?? '';
    const teamsResult = await teamKanbanApi.listTeams();
    if (!teamsResult.ok) {
      setForbidden(teamsResult.forbidden);
      setLoadError(null);
      setData(EMPTY);
      setTeam(null);
      setTeams([]);
      return;
    }
    if (teamsResult.teams.length === 0) {
      setForbidden(true);
      setLoadError(null);
      setData(EMPTY);
      setTeam(null);
      setTeams([]);
      return;
    }

    const pick =
      teamsResult.teams.find((item) => item.id === queryTeam)?.id ?? teamsResult.teams[0]?.id ?? '';
    if (pick !== queryTeam && pick !== '') {
      navigate(`/kanban?team=${encodeURIComponent(pick)}`, { replace: true });
    }

    const result = await teamKanbanApi.load(pick);
    if (!result.ok) {
      setForbidden(result.forbidden);
      setLoadError(result.error ?? null);
      setData(EMPTY);
      setTeam(null);
      return;
    }
    setForbidden(false);
    setLoadError(null);
    setTeams(result.teams);
    setTeam(result.data.team);
    setActiveTeamId(result.data.team.id);
    setData(result.data);
    setDraftBoard(result.data.board);
    setDraftCanned(result.data.cannedReplies);
    if (tab === 'settings' && !result.data.team.canManageBoard) {
      setTab('board');
    }
  }, [navigate, searchParams, tab]);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    void load().finally(() => {
      if (!cancelled) {
        setLoading(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [load]);

  const visibleTickets = useMemo(() => {
    return data.tickets.filter((ticket) => {
      if (filterAssigneeId !== '' && ticket.assigneeUserId !== filterAssigneeId) {
        return false;
      }
      if (filterLabelId !== '' && !ticket.labelIds.includes(filterLabelId)) {
        return false;
      }

      return true;
    });
  }, [data.tickets, filterAssigneeId, filterLabelId]);

  const ticketsByColumn = useMemo(() => {
    const map: Record<string, SupportTicket[]> = {};
    data.board.columns.forEach((column) => {
      map[column.id] = [];
    });
    visibleTickets.forEach((ticket) => {
      const key = map[ticket.columnId] ? ticket.columnId : data.board.columns[0]?.id;
      if (!key) {
        return;
      }
      map[key] = [...(map[key] ?? []), ticket];
    });
    for (const columnId of Object.keys(map)) {
      map[columnId].sort((a, b) => a.order - b.order || b.updatedAt - a.updatedAt);
    }
    return map;
  }, [data.board.columns, visibleTickets]);

  const labelsById = useMemo(() => {
    const map: Record<string, KanbanLabel> = {};
    data.board.labels.forEach((label) => {
      map[label.id] = label;
    });
    return map;
  }, [data.board.labels]);

  const agentName = (userId: string): string => {
    const agent = data.agents.find((item) => item.id === userId);
    return agent?.name || userId;
  };

  const moveTicket = async (ticketId: string, columnId: string) => {
    const column = data.board.columns.find((item) => item.id === columnId);
    const wipLimit = column?.wipLimit ?? 0;
    if (wipLimit > 0) {
      const inColumn = data.tickets.filter(
        (ticket) => ticket.columnId === columnId && ticket.id !== ticketId
      );
      if (inColumn.length >= wipLimit) {
        toast.error(t('platform.kanban.toast.wipLimit', { limit: String(wipLimit) }));
        return;
      }
    }
    const inColumn = (ticketsByColumn[columnId] ?? []).filter((ticket) => ticket.id !== ticketId);
    let maxOrder = -1;
    for (const ticket of inColumn) {
      maxOrder = Math.max(maxOrder, ticket.order);
    }
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.updateTicket(activeTeamId, ticketId, {
      columnId,
      order: maxOrder + 1,
    });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.moveFailed'));
      return;
    }
    await load();
  };

  const addTicket = async () => {
    const title = subject.trim();
    if (title === '') {
      toast.error(t('platform.kanban.toast.subjectRequired'));
      return;
    }
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.createTicket(activeTeamId, { subject: title });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.saveFailed'));
      return;
    }
    setSubject('');
    toast.success(t('platform.kanban.toast.created'));
    await load();
  };

  const saveTicket = async () => {
    if (!editing) {
      return;
    }
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.updateTicket(activeTeamId, editing.id, {
      subject: editing.subject,
      body: editing.body,
      assigneeUserId: editing.assigneeUserId,
      requesterName: editing.requesterName,
      requesterEmail: editing.requesterEmail,
      labelIds: editing.labelIds,
      dueAt: editing.dueAt,
    });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.saveFailed'));
      return;
    }
    toast.success(t('platform.kanban.toast.updated'));
    setEditing(null);
    setNoteBody('');
    setCannedPick('');
    await load();
  };

  const saveBoard = async () => {
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.saveBoard(activeTeamId, {
      columns: draftBoard.columns,
      labels: draftBoard.labels,
      statsEnabled: Boolean(draftBoard.statsEnabled),
    });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.boardFailed'));
      return;
    }
    toast.success(t('platform.kanban.toast.boardSaved'));
    await load();
  };

  const saveCanned = async () => {
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.saveCanned(activeTeamId, draftCanned);
    if (!response.success) {
      toast.error(t('platform.kanban.toast.cannedFailed'));
      return;
    }
    toast.success(t('platform.kanban.toast.cannedSaved'));
    await load();
  };

  const addNote = async () => {
    if (!editing) {
      return;
    }
    const text = noteBody.trim();
    if (text === '') {
      toast.error(t('platform.kanban.toast.noteRequired'));
      return;
    }
    if (activeTeamId === '') {
      return;
    }
    const response = await teamKanbanApi.addNote(activeTeamId, editing.id, text);
    if (!response.success || !response.data?.ticket) {
      toast.error(t('platform.kanban.toast.noteFailed'));
      return;
    }
    setEditing(response.data.ticket);
    setNoteBody('');
    toast.success(t('platform.kanban.toast.noteAdded'));
    await load();
  };

  const insertCanned = () => {
    if (!editing || cannedPick === '') {
      return;
    }
    const reply = data.cannedReplies.find((item) => item.id === cannedPick);
    if (!reply) {
      return;
    }
    const nextBody = editing.body.trim() === '' ? reply.body : `${editing.body.trim()}\n\n${reply.body}`;
    setEditing({ ...editing, body: nextBody });
  };

  const addColumn = () => {
    setDraftBoard((current) => ({
      ...current,
      columns: [
        ...current.columns,
        {
          id: `col${current.columns.length + 1}`,
          label: t('platform.kanban.newColumn'),
          color: '#2c7be5',
          wipLimit: 0,
        },
      ],
    }));
  };

  const addLabel = () => {
    setDraftBoard((current) => ({
      ...current,
      labels: [
        ...current.labels,
        { id: `lbl${current.labels.length + 1}`, name: t('platform.kanban.newLabel'), color: '#748194' },
      ],
    }));
  };

  const addCannedRow = () => {
    setDraftCanned((current) => [
      ...current,
      { id: '', title: t('platform.kanban.newCanned'), body: '' },
    ]);
  };

  const nowUnix = Math.floor(Date.now() / 1000);

  const tabItems = useMemo(() => {
    const items = [{ id: 'board', label: t('platform.kanban.tabs.board') }];
    if (team?.canManageBoard) {
      items.push({ id: 'settings', label: t('platform.kanban.tabs.settings') });
    }

    return items;
  }, [team?.canManageBoard, t]);

  const switchTeam = (nextTeamId: string) => {
    navigate(`/kanban?team=${encodeURIComponent(nextTeamId)}`);
  };

  return (
    <div className="space-y-6" data-testid="kanban-board">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2 mb-1">
            <h1 className={ADMIN_PAGE_TITLE}>{t('platform.kanban.title')}</h1>
            {team ? <TeamBadge name={team.name} color={team.color} /> : null}
          </div>
          <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.kanban.subtitle')}</p>
        </div>
        {teams.length > 1 ? (
          <label className="text-sm text-admin-text shrink-0">
            <span className="text-admin-muted block text-xs mb-1">{t('platform.kanban.teamSelect')}</span>
            <select
              className="form-input min-w-[12rem]"
              value={activeTeamId}
              onChange={(event) => switchTeam(event.target.value)}
              data-testid="kanban-team-select"
            >
              {teams.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name}
                </option>
              ))}
            </select>
          </label>
        ) : null}
      </div>

      <AdminTabs items={tabItems} activeId={tab} onSelect={setTab} />

      {loading ? <p className="text-sm text-admin-muted">{t('platform.kanban.loading')}</p> : null}

      {!loading && forbidden ? (
        <AdminHintCard tone="warning" title={t('platform.kanban.title')}>
          {t('platform.kanban.forbidden')}
        </AdminHintCard>
      ) : null}

      {!loading && !forbidden && loadError ? (
        <AdminHintCard tone="warning" title={t('platform.kanban.loadFailed')}>
          {loadError}
        </AdminHintCard>
      ) : null}

      {!loading && !forbidden ? (
        <AdminHintCard title={t('platform.kanban.hintTitle')}>
          <p className="text-sm text-admin-muted">{t('platform.kanban.hint')}</p>
          <Link to="/teams" className="inline-block mt-2 text-sm font-semibold text-admin-primary hover:underline">
            {t('platform.kanban.hintTeamsLink')}
          </Link>
        </AdminHintCard>
      ) : null}

      {tab === 'board' && !forbidden && data.board.statsEnabled && data.stats ? (
        <KanbanStatsBar stats={data.stats} />
      ) : null}

      {tab === 'board' && !forbidden ? (
        <>
          <div className="flex flex-wrap gap-2 items-end">
            <label className="text-sm min-w-[10rem]">
              <span className="text-admin-muted block text-xs mb-1">{t('platform.kanban.filterAssignee')}</span>
              <select
                className="form-input w-full"
                value={filterAssigneeId}
                onChange={(event) => setFilterAssigneeId(event.target.value)}
                data-testid="kanban-filter-assignee"
              >
                <option value="">{t('platform.kanban.filterAll')}</option>
                {data.agents.map((agent) => (
                  <option key={agent.id} value={agent.id}>
                    {agent.name}
                  </option>
                ))}
              </select>
            </label>
            <label className="text-sm min-w-[10rem]">
              <span className="text-admin-muted block text-xs mb-1">{t('platform.kanban.filterLabel')}</span>
              <select
                className="form-input w-full"
                value={filterLabelId}
                onChange={(event) => setFilterLabelId(event.target.value)}
                data-testid="kanban-filter-label"
              >
                <option value="">{t('platform.kanban.filterAll')}</option>
                {data.board.labels.map((label) => (
                  <option key={label.id} value={label.id}>
                    {label.name}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <div className="flex flex-col sm:flex-row gap-2">
            <input
              className="form-input flex-1"
              value={subject}
              onChange={(event) => setSubject(event.target.value)}
              placeholder={t('platform.kanban.subjectPlaceholder')}
              aria-label={t('platform.kanban.subject')}
            />
            <button type="button" className="btn btn-primary inline-flex items-center gap-2" onClick={() => void addTicket()}>
              <Plus className="w-4 h-4" />
              {t('platform.kanban.addTicket')}
            </button>
          </div>

          <div className="flex gap-4 overflow-x-auto pb-4 items-start">
            {data.board.columns.map((column) => (
              <section
                key={column.id}
                className="min-w-[16rem] w-72 shrink-0 rounded-xl border border-admin-border bg-admin-card p-3"
                onDragOver={(event) => event.preventDefault()}
                onDrop={(event) => {
                  const ticketId = event.dataTransfer.getData('text/plain');
                  if (ticketId) {
                    void moveTicket(ticketId, column.id);
                  }
                }}
                data-testid={`kanban-column-${column.id}`}
              >
                <header className="flex items-center gap-2 mb-3">
                  <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ background: column.color }} />
                  <h2 className="text-sm font-semibold text-admin-text">{column.label}</h2>
                  <span
                    className={`ml-auto text-xs ${
                      (column.wipLimit ?? 0) > 0 &&
                      (ticketsByColumn[column.id]?.length ?? 0) >= (column.wipLimit ?? 0)
                        ? 'font-semibold text-amber-700 dark:text-amber-400'
                        : 'text-admin-muted'
                    }`}
                  >
                    {(column.wipLimit ?? 0) > 0
                      ? t('platform.kanban.wipCount', {
                          count: String(ticketsByColumn[column.id]?.length ?? 0),
                          limit: String(column.wipLimit),
                        })
                      : String(ticketsByColumn[column.id]?.length ?? 0)}
                  </span>
                </header>
                <div className="space-y-2 min-h-[8rem]">
                  {(ticketsByColumn[column.id] ?? []).map((ticket) => {
                    const overdue =
                      ticket.dueAt !== null && ticket.dueAt < nowUnix && !isClosedColumn(column);
                    const noteCount = ticket.internalNotes?.length ?? 0;
                    const assignee = ticket.assigneeUserId ? agentName(ticket.assigneeUserId) : null;
                    return (
                      <div
                        key={ticket.id}
                        className="flex gap-1 rounded-lg border border-admin-border bg-admin-canvas hover:border-admin-primary"
                        data-testid={`kanban-card-${ticket.id}`}
                      >
                        <button
                          type="button"
                          draggable
                          onDragStart={(event) => event.dataTransfer.setData('text/plain', ticket.id)}
                          className="shrink-0 p-2 text-admin-muted cursor-grab active:cursor-grabbing touch-none"
                          aria-label={t('platform.kanban.dragHandle')}
                          data-testid={`kanban-drag-${ticket.id}`}
                        >
                          <GripVertical className="w-4 h-4" />
                        </button>
                        <button
                          type="button"
                          onClick={() => {
                            setEditing(ticket);
                            setNoteBody('');
                            setCannedPick('');
                          }}
                          className="flex-1 min-w-0 text-left p-2 pr-3"
                          data-testid={`kanban-open-${ticket.id}`}
                        >
                          <p className="text-sm font-semibold text-admin-text">{ticket.subject}</p>
                          {ticket.requesterName || ticket.requesterEmail ? (
                            <p className="text-xs text-admin-muted mt-1 truncate">
                              {ticket.requesterName
                                ? `${ticket.requesterName}${ticket.requesterEmail ? ` · ${ticket.requesterEmail}` : ''}`
                                : ticket.requesterEmail}
                            </p>
                          ) : null}
                          {assignee ? (
                            <p className="text-xs text-admin-muted mt-1 truncate">{assignee}</p>
                          ) : null}
                          {ticket.dueAt ? (
                            <p
                              className={`text-xs mt-1 ${overdue ? 'text-red-600 dark:text-red-400' : 'text-admin-muted'}`}
                              data-testid={`kanban-due-${ticket.id}`}
                            >
                              {overdue ? t('platform.kanban.overdue') : t('platform.kanban.dueAt')}:{' '}
                              {new Date(ticket.dueAt * 1000).toLocaleString()}
                            </p>
                          ) : null}
                          {noteCount > 0 ? (
                            <p className="text-[11px] text-admin-muted mt-1">
                              {t('platform.kanban.internalNoteCount', { count: String(noteCount) })}
                            </p>
                          ) : null}
                          {ticket.labelIds.length > 0 ? (
                            <div className="flex flex-wrap gap-1 mt-2">
                              {ticket.labelIds.map((labelId) => {
                                const label = labelsById[labelId];
                                if (!label) {
                                  return null;
                                }
                                return <KanbanLabelChip key={labelId} label={label} />;
                              })}
                            </div>
                          ) : null}
                        </button>
                      </div>
                    );
                  })}
                </div>
              </section>
            ))}
          </div>
        </>
      ) : !forbidden ? (
        <div className="space-y-4">
          <AdminWidgetCard title={t('platform.kanban.columnsTitle')}>
            <div className="space-y-3 p-4">
              {draftBoard.columns.map((column, index) => (
                <ColumnRow
                  key={`${column.id}-${index}`}
                  column={column}
                  onChange={(next) =>
                    setDraftBoard((current) => ({
                      ...current,
                      columns: current.columns.map((item, itemIndex) => (itemIndex === index ? next : item)),
                    }))
                  }
                  onRemove={() =>
                    setDraftBoard((current) => ({
                      ...current,
                      columns: current.columns.filter((_, itemIndex) => itemIndex !== index),
                    }))
                  }
                  removeLabel={t('platform.kanban.remove')}
                  wipLabel={t('platform.kanban.wipLimit')}
                />
              ))}
              <button type="button" className="btn btn-secondary" onClick={addColumn}>
                {t('platform.kanban.addColumn')}
              </button>
            </div>
          </AdminWidgetCard>
          <AdminWidgetCard title={t('platform.kanban.labelsTitle')}>
            <div className="space-y-3 p-4">
              {draftBoard.labels.map((label, index) => (
                <LabelRow
                  key={`${label.id}-${index}`}
                  label={label}
                  onChange={(next) =>
                    setDraftBoard((current) => ({
                      ...current,
                      labels: current.labels.map((item, itemIndex) => (itemIndex === index ? next : item)),
                    }))
                  }
                  onRemove={() =>
                    setDraftBoard((current) => ({
                      ...current,
                      labels: current.labels.filter((_, itemIndex) => itemIndex !== index),
                    }))
                  }
                  removeLabel={t('platform.kanban.remove')}
                />
              ))}
              <button type="button" className="btn btn-secondary" onClick={addLabel}>
                {t('platform.kanban.addLabel')}
              </button>
            </div>
          </AdminWidgetCard>
          <label className="flex items-center gap-2 text-sm text-admin-text px-4 pb-2">
            <input
              type="checkbox"
              checked={Boolean(draftBoard.statsEnabled)}
              onChange={(event) =>
                setDraftBoard((current) => ({ ...current, statsEnabled: event.target.checked }))
              }
              data-testid="kanban-stats-enabled"
            />
            {t('platform.kanban.statsEnabled')}
          </label>
          <AdminFormActions onSave={() => void saveBoard()} saveTestId="kanban-save-board" />
          <AdminWidgetCard title={t('platform.kanban.cannedTitle')}>
            <div className="space-y-3 p-4">
              {draftCanned.map((reply, index) => (
                <div key={`${reply.id}-${index}`} className="space-y-2 rounded-lg border border-admin-border p-3">
                  <div className="flex gap-2">
                    <input
                      className="form-input flex-1"
                      value={reply.title}
                      onChange={(event) =>
                        setDraftCanned((current) =>
                          current.map((item, itemIndex) =>
                            itemIndex === index ? { ...item, title: event.target.value } : item
                          )
                        )
                      }
                      placeholder={t('platform.kanban.cannedTitlePlaceholder')}
                      aria-label={t('platform.kanban.cannedTitlePlaceholder')}
                    />
                    <button
                      type="button"
                      className="admin-topbar-ghost p-2 rounded-lg"
                      onClick={() => setDraftCanned((current) => current.filter((_, itemIndex) => itemIndex !== index))}
                      aria-label={t('platform.kanban.remove')}
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                  <textarea
                    className="form-input w-full min-h-[4rem]"
                    value={reply.body}
                    onChange={(event) =>
                      setDraftCanned((current) =>
                        current.map((item, itemIndex) =>
                          itemIndex === index ? { ...item, body: event.target.value } : item
                        )
                      )
                    }
                    placeholder={t('platform.kanban.cannedBodyPlaceholder')}
                  />
                </div>
              ))}
              <button type="button" className="btn btn-secondary" onClick={addCannedRow} data-testid="kanban-add-canned">
                {t('platform.kanban.addCanned')}
              </button>
              <AdminFormActions onSave={() => void saveCanned()} saveTestId="kanban-save-canned" />
            </div>
          </AdminWidgetCard>
        </div>
      ) : null}

      {editing ? (
        <div
          className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
          role="dialog"
          aria-modal="true"
          data-testid="kanban-ticket-dialog"
          onClick={() => setEditing(null)}
        >
          <div
            className="admin-modal-panel w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-4 space-y-3 dark:bg-gray-800"
            data-testid="kanban-ticket-modal"
            onClick={(event) => event.stopPropagation()}
          >
            <h2 className="font-semibold text-admin-text">{t('platform.kanban.editTicket')}</h2>
            <input
              className="form-input w-full"
              value={editing.subject}
              onChange={(event) => setEditing({ ...editing, subject: event.target.value })}
            />
            <label className="block text-xs font-semibold text-admin-muted">
              {t('platform.kanban.ticketBody')}
              <textarea
                className="form-input w-full min-h-[6rem] mt-1"
                value={editing.body}
                onChange={(event) => setEditing({ ...editing, body: event.target.value })}
              />
            </label>
            {data.cannedReplies.length > 0 ? (
              <div className="flex gap-2">
                <select
                  className="form-input flex-1"
                  value={cannedPick}
                  onChange={(event) => setCannedPick(event.target.value)}
                  aria-label={t('platform.kanban.insertCanned')}
                  data-testid="kanban-canned-select"
                >
                  <option value="">{t('platform.kanban.insertCanned')}</option>
                  {data.cannedReplies.map((reply) => (
                    <option key={reply.id} value={reply.id}>
                      {reply.title}
                    </option>
                  ))}
                </select>
                <button type="button" className="btn btn-secondary" onClick={insertCanned} data-testid="kanban-insert-canned">
                  {t('platform.kanban.insert')}
                </button>
              </div>
            ) : null}
            <input
              className="form-input w-full"
              value={editing.requesterName}
              onChange={(event) => setEditing({ ...editing, requesterName: event.target.value })}
              placeholder={t('platform.kanban.requesterName')}
            />
            <input
              className="form-input w-full"
              value={editing.requesterEmail}
              onChange={(event) => setEditing({ ...editing, requesterEmail: event.target.value })}
              placeholder={t('platform.kanban.requesterEmail')}
            />
            <label className="block text-xs font-semibold text-admin-muted">
              {t('platform.kanban.dueAt')}
              <input
                type="datetime-local"
                className="form-input w-full mt-1"
                value={unixToDatetimeLocal(editing.dueAt)}
                onChange={(event) => setEditing({ ...editing, dueAt: datetimeLocalToUnix(event.target.value) })}
                data-testid="kanban-due-input"
              />
            </label>
            {data.board.labels.length > 0 ? (
              <fieldset className="space-y-2">
                <legend className="text-xs font-semibold text-admin-muted">{t('platform.kanban.ticketLabels')}</legend>
                <div className="flex flex-wrap gap-2">
                  {data.board.labels.map((label) => {
                    const checked = editing.labelIds.includes(label.id);
                    return (
                      <label key={label.id} className="inline-flex items-center gap-1.5 text-sm text-admin-text">
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() =>
                            setEditing({
                              ...editing,
                              labelIds: checked
                                ? editing.labelIds.filter((id) => id !== label.id)
                                : [...editing.labelIds, label.id],
                            })
                          }
                        />
                        <KanbanLabelChip label={label} />
                      </label>
                    );
                  })}
                </div>
              </fieldset>
            ) : null}
            {data.agents.length === 0 ? (
              <p className="text-xs text-admin-muted">
                {t('platform.kanban.agentsEmpty')}{' '}
                <Link to="/teams" className="font-semibold text-admin-primary hover:underline">
                  {t('platform.kanban.hintTeamsLink')}
                </Link>
              </p>
            ) : null}
            <select
              className="form-input w-full"
              value={editing.assigneeUserId ?? ''}
              onChange={(event) => setEditing({ ...editing, assigneeUserId: event.target.value || null })}
            >
              <option value="">{t('platform.kanban.unassigned')}</option>
              {data.agents.map((agent) => (
                <option key={agent.id} value={agent.id}>
                  {agent.name}
                </option>
              ))}
            </select>
            <div className="space-y-2 border-t border-admin-border pt-3">
              <h3 className="text-sm font-semibold text-admin-text">{t('platform.kanban.internalNotes')}</h3>
              <p className="text-xs text-admin-muted">{t('platform.kanban.internalNotesHint')}</p>
              {(editing.internalNotes ?? []).map((note) => (
                <div key={note.id} className="rounded-lg bg-admin-canvas border border-admin-border p-2">
                  <p className="text-sm text-admin-text whitespace-pre-wrap">{note.body}</p>
                  <p className="text-[11px] text-admin-muted mt-1">
                    {agentName(note.authorUserId)} · {new Date(note.createdAt * 1000).toLocaleString()}
                  </p>
                </div>
              ))}
              <textarea
                className="form-input w-full min-h-[4rem]"
                value={noteBody}
                onChange={(event) => setNoteBody(event.target.value)}
                placeholder={t('platform.kanban.notePlaceholder')}
                data-testid="kanban-note-input"
              />
              <button type="button" className="btn btn-secondary" onClick={() => void addNote()} data-testid="kanban-add-note">
                {t('platform.kanban.addNote')}
              </button>
            </div>
            <p className="text-xs text-admin-muted">
              {t('platform.kanban.teamChatHint')}{' '}
              <Link to="/team-chat" className="text-admin-primary hover:underline">
                {t('platform.kanban.openTeamChat')}
              </Link>
            </p>
            <div className="flex flex-wrap gap-2 justify-end">
              <button
                type="button"
                className="btn btn-secondary"
                onClick={() => {
                  void (async () => {
                    const ok = await confirm({
                      title: t('platform.kanban.delete'),
                      message: t('platform.kanban.deleteConfirm'),
                      confirmLabel: t('platform.kanban.delete'),
                      variant: 'destructive',
                    });
                    if (!ok) {
                      return;
                    }
                    if (activeTeamId === '') {
                      return;
                    }
                    const removed = await teamKanbanApi.removeTicket(activeTeamId, editing.id);
                    if (!removed) {
                      toast.error(t('platform.kanban.toast.deleteFailed'));
                      return;
                    }
                    toast.success(t('platform.kanban.toast.deleted'));
                    setEditing(null);
                    await load();
                  })();
                }}
              >
                {t('platform.kanban.delete')}
              </button>
              <button type="button" className="btn btn-secondary" onClick={() => setEditing(null)}>
                {t('platform.kanban.cancel')}
              </button>
              <button type="button" className="btn btn-primary" onClick={() => void saveTicket()}>
                {t('platform.kanban.save')}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
};

const KanbanStatsBar: React.FC<{ stats: KanbanStats }> = ({ stats }) => {
  const { t } = useI18n();

  return (
    <div
      className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 rounded-xl border border-admin-border bg-admin-card p-4 text-sm"
      data-testid="kanban-stats"
    >
      <div>
        <p className="text-xs text-admin-muted">{t('platform.kanban.statsOpen')}</p>
        <p className="font-semibold text-admin-text">{stats.openCount}</p>
      </div>
      <div>
        <p className="text-xs text-admin-muted">{t('platform.kanban.statsCompleted')}</p>
        <p className="font-semibold text-admin-text">{stats.completedCount}</p>
      </div>
      <div>
        <p className="text-xs text-admin-muted">{t('platform.kanban.statsAvg')}</p>
        <p className="font-semibold text-admin-text">
          {stats.avgSeconds !== null ? formatDuration(stats.avgSeconds) : '—'}
        </p>
      </div>
      <div>
        <p className="text-xs text-admin-muted">{t('platform.kanban.statsMedian')}</p>
        <p className="font-semibold text-admin-text">
          {stats.medianSeconds !== null ? formatDuration(stats.medianSeconds) : '—'}
        </p>
      </div>
    </div>
  );
};

const ColumnRow: React.FC<{
  column: KanbanColumn;
  onChange: (column: KanbanColumn) => void;
  onRemove: () => void;
  removeLabel: string;
  wipLabel: string;
}> = ({ column, onChange, onRemove, removeLabel, wipLabel }) => (
  <div className="flex flex-wrap gap-2 items-center">
    <input
      className="form-input flex-1 min-w-[8rem]"
      value={column.label}
      onChange={(event) => onChange({ ...column, label: event.target.value })}
    />
    <label className="flex items-center gap-1 text-xs text-admin-muted shrink-0">
      <span>{wipLabel}</span>
      <input
        type="number"
        min={0}
        max={99}
        className="form-input w-16 py-1"
        value={column.wipLimit ?? 0}
        onChange={(event) =>
          onChange({ ...column, wipLimit: Math.max(0, Math.min(99, Number(event.target.value) || 0)) })
        }
        data-testid={`kanban-wip-${column.id}`}
      />
    </label>
    <input
      type="color"
      className="h-10 w-12 rounded border border-admin-border"
      value={column.color}
      onChange={(event) => onChange({ ...column, color: event.target.value })}
    />
    <button type="button" className="admin-topbar-ghost p-2 rounded-lg" onClick={onRemove} aria-label={removeLabel}>
      <Trash2 className="w-4 h-4" />
    </button>
  </div>
);

const LabelRow: React.FC<{
  label: KanbanLabel;
  onChange: (label: KanbanLabel) => void;
  onRemove: () => void;
  removeLabel: string;
}> = ({ label, onChange, onRemove, removeLabel }) => (
  <div className="flex gap-2 items-center">
    <input
      className="form-input flex-1"
      value={label.name}
      onChange={(event) => onChange({ ...label, name: event.target.value })}
    />
    <input
      type="color"
      className="h-10 w-12 rounded border border-admin-border"
      value={label.color}
      onChange={(event) => onChange({ ...label, color: event.target.value })}
    />
    <button type="button" className="admin-topbar-ghost p-2 rounded-lg" onClick={onRemove} aria-label={removeLabel}>
      <Trash2 className="w-4 h-4" />
    </button>
  </div>
);

export default KanbanBoardView;
