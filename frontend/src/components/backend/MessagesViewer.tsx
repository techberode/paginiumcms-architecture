// frontend/src/components/backend/MessagesViewer.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Archive,
  CheckCircle2,
  Eye,
  Mail,
  Trash2,
} from 'lucide-react';
import {
  ContactMessage,
  bulkMessageAction,
  deleteMessage,
  listMessages,
  updateMessage,
} from '../../api/messages';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { AdminListSortBar } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import {
  AdminInboxList,
  AdminInboxListHeader,
  AdminInboxRow,
  inboxPriorityBadgeClass,
  inboxPriorityLabel,
} from './AdminInboxList';
import { applyClientListView } from '../../utils/clientListView';
import { messagePriorityWeight } from '../../constants/messageSubjects';
import { summarizeBulkResult } from '../../types/bulk';

const truncate = (text: string, max = 90): string =>
  text.length <= max ? text : `${text.slice(0, max).trim()}…`;

export const MessagesViewer: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<ContactMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [pageSize, setPageSize] = useAdminListPageSize('messages');
  const { sortField, sortDirection, handleSort } = useColumnSort('createdAt', 'desc');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listMessages());
    } catch {
      showError('Nepodarilo sa načítať správy.');
    } finally {
      setLoading(false);
    }
  }, [showError]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [search, sortField, sortDirection, pageSize]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (msg) =>
          `${msg.name} ${msg.email} ${msg.subject} ${msg.message} ${msg.priority}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'subject', label: 'Predmet', getValue: (msg) => msg.subject },
          {
            value: 'priority',
            label: 'Priorita',
            getValue: (msg) => messagePriorityWeight(msg.priority),
          },
          { value: 'name', label: 'Meno', getValue: (msg) => msg.name },
          { value: 'createdAt', label: 'Dátum', getValue: (msg) => msg.createdAt },
          { value: 'isRead', label: 'Stav', getValue: (msg) => (msg.isRead ? 1 : 0) },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField]
  );

  const bulkSelection = useBulkSelection(
    listView.items.map((msg) => msg.id),
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const unread = items.filter((m) => !m.isRead && !m.isArchived).length;

  const toggleExpand = (id: string) => {
    setExpandedId((current) => (current === id ? null : id));
  };

  const handleBulk = async (action: 'read' | 'processed' | 'archive' | 'delete') => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (action === 'delete' && !confirm(`Vymazať ${bulkSelection.count} označených správ?`)) {
      return;
    }
    const result = await bulkMessageAction(bulkSelection.selectedIds, action);
    if (result) {
      showSuccess(summarizeBulkResult(result));
      bulkSelection.clear();
      await load();
    } else {
      showError('Hromadná akcia zlyhala.');
    }
  };

  const markOne = async (msg: ContactMessage, patch: Partial<ContactMessage>) => {
    const updated = await updateMessage(msg.id, patch);
    if (updated) {
      await load();
    }
  };

  const removeOne = async (id: string) => {
    if (!confirm('Vymazať túto správu?')) {
      return;
    }
    if (await deleteMessage(id)) {
      showSuccess('Správa zmazaná.');
      if (expandedId === id) {
        setExpandedId(null);
      }
      await load();
    }
  };

  return (
    <div className="space-y-6 w-full max-w-none">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <Mail className="w-6 h-6 text-violet-500" />
          Správy ({listView.total})
        </h1>
        <p className="text-sm text-gray-500 mt-1">Neprečítané: {unread}</p>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder="Hľadať podľa mena, e-mailu alebo predmetu…"
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
      />

      <AdminListSortBar
        columns={[
          { field: 'priority', label: 'Priorita' },
          { field: 'subject', label: 'Predmet' },
          { field: 'name', label: 'Meno' },
          { field: 'createdAt', label: 'Dátum' },
          { field: 'isRead', label: 'Stav' },
        ]}
        activeField={sortField}
        direction={sortDirection}
        onSort={handleSort}
      />

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="označených správ"
        onClear={bulkSelection.clear}
        actions={[
          { id: 'read', label: 'Prečítané', variant: 'secondary', onClick: () => void handleBulk('read') },
          { id: 'processed', label: 'Vybavené', variant: 'primary', onClick: () => void handleBulk('processed') },
          { id: 'archive', label: 'Archivovať', variant: 'secondary', onClick: () => void handleBulk('archive') },
          { id: 'delete', label: 'Vymazať označené', variant: 'danger', onClick: () => void handleBulk('delete') },
        ]}
      />

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
        </div>
      ) : listView.total === 0 ? (
        <div className="card card-body text-center text-gray-500 py-12">
          {items.length === 0 ? 'Zatiaľ žiadne správy.' : 'Nenašli sa žiadne správy pre filter.'}
        </div>
      ) : (
        <>
          <AdminInboxList>
            <AdminInboxListHeader
              allSelected={bulkSelection.allSelected && listView.items.length > 0}
              onToggleAll={bulkSelection.toggleAll}
            />
            {listView.items.map((msg, index) => (
              <AdminInboxRow
                key={msg.id}
                id={msg.id}
                index={index}
                expanded={expandedId === msg.id}
                onToggleExpand={toggleExpand}
                selected={bulkSelection.isSelected(msg.id)}
                onToggleSelect={bulkSelection.toggle}
                unread={!msg.isRead}
                summary={
                  <div className="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-1 sm:gap-4 items-start w-full">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className={!msg.isRead ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-800 dark:text-gray-200'}>
                          {msg.name}
                        </span>
                        <span className={`text-xs ${inboxPriorityBadgeClass(msg.priority)}`}>
                          {inboxPriorityLabel(msg.priority)}
                        </span>
                        {msg.isProcessed ? (
                          <span className="badge bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300 text-xs">
                            Vybavené
                          </span>
                        ) : null}
                        {msg.isArchived ? (
                          <span className="badge bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
                            Archivované
                          </span>
                        ) : null}
                      </div>
                      <p className={`text-sm truncate mt-0.5 ${!msg.isRead ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400'}`}>
                        {msg.subject}
                      </p>
                      {!expandedId || expandedId !== msg.id ? (
                        <p className="text-xs text-gray-500 truncate mt-0.5">{truncate(msg.message)}</p>
                      ) : null}
                    </div>
                    <div className="text-xs text-gray-500 shrink-0 sm:text-right">
                      {new Date(msg.createdAt).toLocaleString('sk-SK')}
                    </div>
                  </div>
                }
                detail={
                  <div className="space-y-3 text-sm">
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                      <span>{msg.email}</span>
                      {msg.ip ? <span>IP: {msg.ip}</span> : null}
                    </div>
                    <p className="font-medium text-gray-900 dark:text-white">{msg.subject}</p>
                    <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{msg.message}</p>
                    <div className="flex flex-wrap gap-2">
                      {!msg.isRead ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(msg, { isRead: true })}>
                          <Eye className="w-3 h-3 inline mr-1" />
                          Prečítané
                        </button>
                      ) : null}
                      {!msg.isProcessed ? (
                        <button type="button" className="btn btn-primary text-xs px-2 py-1" onClick={() => void markOne(msg, { isProcessed: true, isRead: true })}>
                          <CheckCircle2 className="w-3 h-3 inline mr-1" />
                          Vybavené
                        </button>
                      ) : null}
                      {!msg.isArchived ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(msg, { isArchived: true })}>
                          <Archive className="w-3 h-3 inline mr-1" />
                          Archivovať
                        </button>
                      ) : null}
                      <button type="button" className="btn btn-danger text-xs px-2 py-1 ml-auto" onClick={() => void removeOne(msg.id)}>
                        <Trash2 className="w-3 h-3 inline mr-1" />
                        Vymazať
                      </button>
                    </div>
                  </div>
                }
              />
            ))}
          </AdminInboxList>

          <AdminListPagination
            page={listView.page}
            totalPages={listView.totalPages}
            total={listView.total}
            pageSize={pageSize}
            loading={loading}
            onPageChange={setPage}
            itemLabel="správ"
          />
        </>
      )}
    </div>
  );
};

export default MessagesViewer;
