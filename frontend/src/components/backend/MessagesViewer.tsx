// frontend/src/components/backend/MessagesViewer.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useLocation } from 'react-router-dom';
import {
  Archive,
  CheckCircle2,
  Eye,
  Mail,
  Phone,
  Trash2,
} from 'lucide-react';
import {
  ContactMessage,
  bulkMessageAction,
  deleteMessage,
  listMessages,
  updateMessage,
} from '../../api/messages';
import { MessageMessengerThread } from './MessageMessengerThread';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { AdminListSortBar } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { AdminEmptyState } from '../ui/AdminEmptyState';
import {
  AdminInboxList,
  AdminInboxListHeader,
  AdminInboxRow,
} from './AdminInboxList';
import { inboxPriorityBadgeClass } from '../../utils/adminInboxPriority';
import { bulkSelectionCounts } from '../../utils/bulkSelectionLabel';
import { applyClientListView } from '../../utils/clientListView';
import { messagePriorityWeight } from '../../constants/messageSubjects';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { useDeskActionSync, type DeskItemKey } from '../../hooks/useDeskActionSync';

const truncate = (text: string, max = 90): string =>
  text.length <= max ? text : `${text.slice(0, max).trim()}…`;

export const MessagesViewer: React.FC = () => {
  const { t, locale } = useI18n();
  const location = useLocation();
  const confirmDestructive = useAdminConfirm();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';
  const priorityLabel = (priority: string): string => {
    const key = `messages.priority.${priority}` as const;
    const translated = t(key);
    return translated !== key ? translated : t('messages.priority.normal');
  };
  const { error: showError, success: showSuccess } = useToast();
  const syncDesk = useDeskActionSync();
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
      showError(t('messages.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [search, sortField, sortDirection, pageSize]);

  useEffect(() => {
    const raw = location.hash.replace(/^#/, '');
    if (!raw.startsWith('message-')) {
      return;
    }
    const id = decodeURIComponent(raw.slice('message-'.length));
    if (id !== '') {
      setExpandedId(id);
    }
  }, [location.hash]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (msg) =>
          `${msg.name} ${msg.email} ${msg.phone ?? ''} ${msg.subject} ${msg.message} ${msg.priority} ${msg.channel ?? ''} ${msg.claimedByName ?? ''}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'subject', label: t('messages.table.subject'), getValue: (msg) => msg.subject },
          {
            value: 'priority',
            label: t('messages.table.priority'),
            getValue: (msg) => messagePriorityWeight(msg.priority),
          },
          { value: 'name', label: t('messages.table.name'), getValue: (msg) => msg.name },
          { value: 'createdAt', label: t('messages.table.date'), getValue: (msg) => msg.createdAt },
          { value: 'isRead', label: t('messages.table.state'), getValue: (msg) => (msg.isRead ? 1 : 0) },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField, t]
  );

  useEffect(() => {
    if (!expandedId || loading) {
      return;
    }
    document.getElementById(`message-${expandedId}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, [expandedId, loading, listView.items]);

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
    const counts = bulkSelectionCounts(bulkSelection.count, listView.total);
    const confirmKey =
      action === 'delete'
        ? 'messages.confirm.bulkDelete'
        : action === 'archive'
          ? 'messages.confirm.bulkArchive'
          : action === 'read'
            ? 'messages.confirm.bulkRead'
            : 'messages.confirm.bulkProcessed';
    if (!(await confirmDestructive(t(confirmKey, counts)))) {
      return;
    }
    const selectedIds = [...bulkSelection.selectedIds];
    const result = await bulkMessageAction(selectedIds, action);
    if (result) {
      showSuccess(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
      const deskKeys: DeskItemKey[] =
        action === 'read'
          ? []
          : selectedIds.map((id) => ({ kind: 'message', id }));
      await syncDesk(deskKeys.length > 0 ? deskKeys : undefined);
    } else {
      showError(t('messages.toast.bulkFailed'));
    }
  };

  const markOne = async (msg: ContactMessage, patch: Partial<ContactMessage>) => {
    const updated = await updateMessage(msg.id, patch);
    if (updated) {
      await load();
      if (patch.isProcessed || patch.isArchived) {
        await syncDesk([{ kind: 'message', id: msg.id }]);
      }
    }
  };

  const removeOne = async (id: string) => {
    if (!(await confirmDestructive(t('messages.confirm.deleteOne')))) {
      return;
    }
    if (await deleteMessage(id)) {
      showSuccess(t('messages.toast.deleted'));
      if (expandedId === id) {
        setExpandedId(null);
      }
      await load();
      await syncDesk([{ kind: 'message', id }]);
    }
  };

  return (
    <div className="space-y-6 w-full max-w-none">
      <div>
        <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
          <Mail className="w-6 h-6 text-admin-primary" />
          {t('messages.page.title')} ({listView.total})
        </h1>
        <p className={ADMIN_PAGE_SUBTITLE}>{t('messages.page.unread', { count: String(unread) })}</p>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('messages.search.placeholder')}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
      />

      <AdminListSortBar
        columns={[
          { field: 'priority', label: t('messages.table.priority') },
          { field: 'subject', label: t('messages.table.subject') },
          { field: 'name', label: t('messages.table.name') },
          { field: 'createdAt', label: t('messages.table.date') },
          { field: 'isRead', label: t('messages.table.state') },
        ]}
        activeField={sortField}
        direction={sortDirection}
        onSort={handleSort}
      />

      <BulkActionBar
        count={bulkSelection.count}
        totalCount={listView.total}
        itemLabel={t('messages.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          { id: 'read', label: t('messages.bulk.read'), variant: 'secondary', onClick: () => void handleBulk('read') },
          { id: 'processed', label: t('messages.bulk.processed'), variant: 'primary', onClick: () => void handleBulk('processed') },
          { id: 'archive', label: t('messages.bulk.archive'), variant: 'secondary', onClick: () => void handleBulk('archive') },
          { id: 'delete', label: t('messages.bulk.delete'), variant: 'danger', onClick: () => void handleBulk('delete') },
        ]}
      />

      {loading ? (
        <AdminListSkeleton rows={8} />
      ) : listView.total === 0 ? (
        <AdminEmptyState
          title={items.length === 0 ? t('messages.empty.none') : t('messages.empty.filter')}
        />
      ) : (
        <>
          <AdminInboxList>
            <AdminInboxListHeader
              allSelected={bulkSelection.allSelected && listView.items.length > 0}
              onToggleAll={bulkSelection.toggleAll}
            />
            {listView.items.map((msg, index) => (
              <div key={msg.id} id={`message-${msg.id}`} className="scroll-mt-24">
              <AdminInboxRow
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
                          {priorityLabel(msg.priority)}
                        </span>
                        {msg.isProcessed ? (
                          <span className="badge bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300 text-xs">
                            {t('messages.status.processed')}
                          </span>
                        ) : null}
                        {msg.isArchived ? (
                          <span className="badge bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
                            {t('messages.status.archived')}
                          </span>
                        ) : null}
                        {msg.channel === 'staff-chat' ? (
                          <span className="badge bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300 text-xs">
                            {t('messages.status.staffChat')}
                          </span>
                        ) : null}
                        {msg.desk === 'priority' ? (
                          <span className="badge bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-xs">
                            {t('messages.desk.mine')}
                          </span>
                        ) : null}
                        {msg.handleStatus === 'in_progress' ? (
                          <span className="badge bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 text-xs">
                            {t('messages.desk.inProgress')}
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
                      {new Date(msg.createdAt).toLocaleString(dateLocale)}
                    </div>
                  </div>
                }
                detail={
                  <div className="space-y-3 text-sm">
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                      <span>{msg.email}</span>
                      {msg.phone ? <span>{msg.phone}</span> : null}
                      {msg.ip ? <span>{t('messages.detail.ip', { ip: msg.ip })}</span> : null}
                    </div>
                    {msg.phone ? (
                      <a className="btn btn-secondary text-xs px-2 py-1" href={`tel:${msg.phone}`}>
                        <Phone className="w-3 h-3 inline mr-1" />
                        {t('messages.actions.call')}
                      </a>
                    ) : null}
                    <p className="font-medium text-gray-900 dark:text-white">{msg.subject}</p>
                    <MessageMessengerThread
                      message={msg}
                      composerEnabled
                      onUpdated={(next) =>
                        setItems((current) => current.map((item) => (item.id === next.id ? next : item)))
                      }
                    />
                    <div className="flex flex-wrap gap-2">
                      {!msg.isRead ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(msg, { isRead: true })}>
                          <Eye className="w-3 h-3 inline mr-1" />
                          {t('messages.actions.read')}
                        </button>
                      ) : null}
                      {!msg.isProcessed ? (
                        <button type="button" className="btn btn-primary text-xs px-2 py-1" onClick={() => void markOne(msg, { isProcessed: true, isRead: true })}>
                          <CheckCircle2 className="w-3 h-3 inline mr-1" />
                          {t('messages.actions.processed')}
                        </button>
                      ) : null}
                      {!msg.isArchived ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(msg, { isArchived: true })}>
                          <Archive className="w-3 h-3 inline mr-1" />
                          {t('messages.actions.archive')}
                        </button>
                      ) : null}
                      <button type="button" className="btn btn-danger text-xs px-2 py-1 ml-auto" onClick={() => void removeOne(msg.id)}>
                        <Trash2 className="w-3 h-3 inline mr-1" />
                        {t('messages.actions.delete')}
                      </button>
                    </div>
                  </div>
                }
              />
              </div>
            ))}
          </AdminInboxList>

          <AdminListPagination
            page={listView.page}
            totalPages={listView.totalPages}
            total={listView.total}
            pageSize={pageSize}
            loading={loading}
            onPageChange={setPage}
            itemLabel={t('messages.pagination.itemLabel')}
          />
        </>
      )}
    </div>
  );
};

export default MessagesViewer;
