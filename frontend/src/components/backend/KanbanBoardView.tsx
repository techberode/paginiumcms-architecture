import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import {
  supportKanbanApi,
  type KanbanBoard,
  type KanbanColumn,
  type KanbanIndex,
  type KanbanLabel,
  type SupportTicket,
} from '../../api/kanban';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { AdminFormActions } from './AdminFormActions';
import { AdminTabs } from '../ui/AdminTabs';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';

const EMPTY: KanbanIndex = {
  board: { schema: 'support-board@1', columns: [], labels: [] },
  tickets: [],
  agents: [],
};

export const KanbanBoardView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [tab, setTab] = useState('board');
  const [data, setData] = useState<KanbanIndex>(EMPTY);
  const [loading, setLoading] = useState(true);
  const [draftBoard, setDraftBoard] = useState<KanbanBoard>(EMPTY.board);
  const [editing, setEditing] = useState<SupportTicket | null>(null);
  const [subject, setSubject] = useState('');

  const load = useCallback(async () => {
    const next = await supportKanbanApi.list();
    setData(next);
    setDraftBoard(next.board);
  }, []);

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

  const ticketsByColumn = useMemo(() => {
    const map: Record<string, SupportTicket[]> = {};
    data.board.columns.forEach((column) => {
      map[column.id] = [];
    });
    data.tickets.forEach((ticket) => {
      const key = map[ticket.columnId] ? ticket.columnId : data.board.columns[0]?.id;
      if (!key) {
        return;
      }
      map[key] = [...(map[key] ?? []), ticket];
    });
    return map;
  }, [data]);

  const moveTicket = async (ticketId: string, columnId: string) => {
    const response = await supportKanbanApi.updateTicket(ticketId, { columnId });
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
    const response = await supportKanbanApi.createTicket({ subject: title });
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
    const response = await supportKanbanApi.updateTicket(editing.id, {
      subject: editing.subject,
      body: editing.body,
      assigneeUserId: editing.assigneeUserId,
      requesterName: editing.requesterName,
      requesterEmail: editing.requesterEmail,
      labelIds: editing.labelIds,
    });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.saveFailed'));
      return;
    }
    toast.success(t('platform.kanban.toast.updated'));
    setEditing(null);
    await load();
  };

  const saveBoard = async () => {
    const response = await supportKanbanApi.saveBoard({
      columns: draftBoard.columns,
      labels: draftBoard.labels,
    });
    if (!response.success) {
      toast.error(t('platform.kanban.toast.boardFailed'));
      return;
    }
    toast.success(t('platform.kanban.toast.boardSaved'));
    await load();
  };

  const addColumn = () => {
    setDraftBoard((current) => ({
      ...current,
      columns: [
        ...current.columns,
        { id: `col${current.columns.length + 1}`, label: t('platform.kanban.newColumn'), color: '#2c7be5' },
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

  return (
    <div className="space-y-6" data-testid="kanban-board">
      <div>
        <h1 className={ADMIN_PAGE_TITLE}>{t('platform.kanban.title')}</h1>
        <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.kanban.subtitle')}</p>
      </div>

      <AdminTabs
        items={[
          { id: 'board', label: t('platform.kanban.tabs.board') },
          { id: 'settings', label: t('platform.kanban.tabs.settings') },
        ]}
        activeId={tab}
        onSelect={setTab}
      />

      {loading ? <p className="text-sm text-admin-muted">{t('platform.kanban.loading')}</p> : null}

      {tab === 'board' ? (
        <>
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
                  <span className="ml-auto text-xs text-admin-muted">{ticketsByColumn[column.id]?.length ?? 0}</span>
                </header>
                <div className="space-y-2 min-h-[8rem]">
                  {(ticketsByColumn[column.id] ?? []).map((ticket) => (
                    <button
                      key={ticket.id}
                      type="button"
                      draggable
                      onDragStart={(event) => event.dataTransfer.setData('text/plain', ticket.id)}
                      onClick={() => setEditing(ticket)}
                      className="w-full text-left rounded-lg border border-admin-border bg-admin-canvas p-3 hover:border-admin-primary"
                      data-testid={`kanban-card-${ticket.id}`}
                    >
                      <p className="text-sm font-semibold text-admin-text">{ticket.subject}</p>
                      {ticket.requesterEmail ? (
                        <p className="text-xs text-admin-muted mt-1 truncate">{ticket.requesterEmail}</p>
                      ) : null}
                    </button>
                  ))}
                </div>
              </section>
            ))}
          </div>
        </>
      ) : (
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
          <AdminFormActions onSave={() => void saveBoard()} saveTestId="kanban-save-board" />
        </div>
      )}

      {editing ? (
        <div
          className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
          role="dialog"
          aria-modal="true"
          data-testid="kanban-ticket-dialog"
        >
          <div
            className="admin-modal-panel w-full max-w-lg rounded-xl bg-white p-4 space-y-3 dark:bg-gray-800"
            data-testid="kanban-ticket-modal"
          >
            <h2 className="font-semibold text-admin-text">{t('platform.kanban.editTicket')}</h2>
            <input
              className="form-input w-full"
              value={editing.subject}
              onChange={(event) => setEditing({ ...editing, subject: event.target.value })}
            />
            <textarea
              className="form-input w-full min-h-[6rem]"
              value={editing.body}
              onChange={(event) => setEditing({ ...editing, body: event.target.value })}
            />
            <input
              className="form-input w-full"
              value={editing.requesterEmail}
              onChange={(event) => setEditing({ ...editing, requesterEmail: event.target.value })}
              placeholder={t('platform.kanban.requesterEmail')}
            />
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
            <div className="flex flex-wrap gap-2 justify-end">
              <button
                type="button"
                className="btn btn-secondary"
                onClick={() => {
                  void supportKanbanApi.removeTicket(editing.id).then(() => {
                    setEditing(null);
                    void load();
                  });
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

const ColumnRow: React.FC<{
  column: KanbanColumn;
  onChange: (column: KanbanColumn) => void;
  onRemove: () => void;
  removeLabel: string;
}> = ({ column, onChange, onRemove, removeLabel }) => (
  <div className="flex gap-2 items-center">
    <input
      className="form-input flex-1"
      value={column.label}
      onChange={(event) => onChange({ ...column, label: event.target.value })}
    />
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
