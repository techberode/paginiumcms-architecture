// frontend/src/components/backend/CommentsManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Archive,
  CheckCircle2,
  Eye,
  MessageSquare,
  Trash2,
} from 'lucide-react';
import {
  Comment,
  CommentStatus,
  bulkCommentWorkflow,
  bulkDeleteComments,
  deleteComment,
  listAdminComments,
  updateCommentFlags,
  updateCommentStatus,
} from '../../api/comments';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useAdminListQueryParams } from '../../hooks/useAdminListQueryParams';
import { AdminListSortBar } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import {
  AdminInboxList,
  AdminInboxListHeader,
  AdminInboxRow,
} from './AdminInboxList';
import { OtpConfirmModal } from './OtpConfirmModal';
import { applyClientListView } from '../../utils/clientListView';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';

const statusBadgeClass = (status: CommentStatus): string => {
  switch (status) {
    case 'approved':
      return 'badge bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300';
    case 'rejected':
      return 'badge bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
    default:
      return 'badge bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
  }
};

const truncate = (text: string, max = 90): string =>
  text.length <= max ? text : `${text.slice(0, max).trim()}…`;

export const CommentsManager: React.FC = () => {
  const { t, locale } = useI18n();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';
  const statusLabel = (status: CommentStatus): string => t(`comments.status.${status}`);
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<Comment[]>([]);
  const {
    page,
    search,
    statusFilter: filter,
    sortField,
    sortDirection,
    handleSort,
    setSearch,
    setPage,
    setStatusFilter: setFilter,
    resetFilters,
  } = useAdminListQueryParams('createdAt', 'desc');
  const [pageSize, setPageSize] = useAdminListPageSize('comments');
  const hasActiveFilters =
    search.trim().length >= 2 ||
    filter !== 'all' ||
    sortField !== 'createdAt' ||
    sortDirection !== 'desc' ||
    page > 1;
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [otpChallenge, setOtpChallenge] = useState<{ id: string; commentId: string; debugCode?: string } | null>(
    null
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const comments = await listAdminComments(
        filter === 'all' ? undefined : { status: filter as CommentStatus }
      );
      setItems(comments);
    } catch {
      showError(t('comments.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [filter, showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [pageSize, setPage]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (comment) =>
          `${comment.author} ${comment.email ?? ''} ${comment.content} ${comment.articleSlug} ${comment.status}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'author', label: t('comments.table.author'), getValue: (comment) => comment.author },
          { value: 'articleSlug', label: t('comments.table.article'), getValue: (comment) => comment.articleSlug },
          { value: 'status', label: t('comments.table.status'), getValue: (comment) => comment.status },
          { value: 'createdAt', label: t('comments.table.date'), getValue: (comment) => comment.createdAt },
          { value: 'isRead', label: t('comments.table.read'), getValue: (comment) => (comment.isRead ? 1 : 0) },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField, t]
  );

  const bulkSelection = useBulkSelection(
    listView.items.map((comment) => comment.id),
    `${filter}:${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const unread = items.filter((c) => !c.isRead && !c.isArchived).length;

  const toggleExpand = (id: string) => {
    setExpandedId((current) => (current === id ? null : id));
  };

  const handleBulkWorkflow = async (action: 'read' | 'processed' | 'archive') => {
    if (bulkSelection.count === 0) {
      return;
    }
    const result = await bulkCommentWorkflow(bulkSelection.selectedIds, action);
    if (result) {
      showSuccess(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } else {
      showError(t('comments.toast.bulkFailed'));
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('comments.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }
    const result = await bulkDeleteComments(bulkSelection.selectedIds);
    if (result) {
      showSuccess(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } else {
      showError(t('comments.toast.bulkDeleteFailed'));
    }
  };

  const approveOne = async (id: string) => {
    const result = await updateCommentStatus(id, 'approved');
    if (result.ok && 'requiresOtp' in result && result.requiresOtp) {
      setOtpChallenge({ id: result.challengeId, commentId: id, debugCode: result.debugCode });
      showSuccess(t('comments.toast.otpSent'));
      if (result.debugCode) {
        showError(`Dev OTP: ${result.debugCode}`);
      }
      return;
    }
    if (result.ok) {
      showSuccess(t('comments.toast.approved'));
      await load();
    } else {
      showError(result.error || t('comments.toast.updateFailed'));
    }
  };

  const markOne = async (comment: Comment, patch: { isRead?: boolean; isArchived?: boolean }) => {
    const updated = await updateCommentFlags(comment.id, patch);
    if (updated) {
      await load();
    }
  };

  const removeOne = async (id: string) => {
    if (!confirm(t('comments.confirm.deleteOne'))) {
      return;
    }
    if (await deleteComment(id)) {
      showSuccess(t('comments.toast.deleted'));
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
          <MessageSquare className="w-6 h-6 text-indigo-500" />
          {t('comments.page.title')} ({listView.total})
        </h1>
        <p className="text-sm text-gray-500 mt-1">{t('comments.page.unread', { count: String(unread) })}</p>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('comments.search.placeholder')}
        statusFilter={filter}
        onStatusFilterChange={(value) => setFilter(value as CommentStatus | 'all')}
        statusOptions={[
          { value: 'all', label: t('comments.filter.all') },
          { value: 'pending', label: t('comments.filter.pending') },
          { value: 'approved', label: t('comments.filter.approved') },
          { value: 'rejected', label: t('comments.filter.rejected') },
        ]}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
        onResetFilters={resetFilters}
        showResetFilters={hasActiveFilters}
      />

      <AdminListSortBar
        columns={[
          { field: 'author', label: t('comments.table.author') },
          { field: 'articleSlug', label: t('comments.table.article') },
          { field: 'status', label: t('comments.table.status') },
          { field: 'createdAt', label: t('comments.table.date') },
          { field: 'isRead', label: t('comments.table.read') },
        ]}
        activeField={sortField}
        direction={sortDirection}
        onSort={handleSort}
      />

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('comments.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          { id: 'read', label: t('comments.bulk.read'), variant: 'secondary', onClick: () => void handleBulkWorkflow('read') },
          { id: 'processed', label: t('comments.bulk.processed'), variant: 'primary', onClick: () => void handleBulkWorkflow('processed') },
          { id: 'archive', label: t('comments.bulk.archive'), variant: 'secondary', onClick: () => void handleBulkWorkflow('archive') },
          { id: 'delete', label: t('comments.bulk.delete'), variant: 'danger', onClick: () => void handleBulkDelete() },
        ]}
      />

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
        </div>
      ) : listView.total === 0 ? (
        <div className="card card-body text-center text-gray-500 py-12">{t('comments.empty.none')}</div>
      ) : (
        <>
          <AdminInboxList>
            <AdminInboxListHeader
              allSelected={bulkSelection.allSelected && listView.items.length > 0}
              onToggleAll={bulkSelection.toggleAll}
            />
            {listView.items.map((comment, index) => (
              <AdminInboxRow
                key={comment.id}
                id={comment.id}
                index={index}
                expanded={expandedId === comment.id}
                onToggleExpand={toggleExpand}
                selected={bulkSelection.isSelected(comment.id)}
                onToggleSelect={bulkSelection.toggle}
                unread={!comment.isRead}
                summary={
                  <div className="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-1 sm:gap-4 items-start w-full">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className={!comment.isRead ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-800 dark:text-gray-200'}>
                          {comment.author}
                        </span>
                        <span className={`text-xs ${statusBadgeClass(comment.status)}`}>
                          {statusLabel(comment.status)}
                        </span>
                        {comment.isArchived ? (
                          <span className="badge bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs">
                            {t('comments.status.archived')}
                          </span>
                        ) : null}
                      </div>
                      <p className="text-xs text-gray-500 truncate">{comment.articleSlug}</p>
                      {!expandedId || expandedId !== comment.id ? (
                        <p className={`text-sm truncate mt-0.5 ${!comment.isRead ? 'text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-400'}`}>
                          {truncate(comment.content)}
                        </p>
                      ) : null}
                    </div>
                    <div className="text-xs text-gray-500 shrink-0 sm:text-right">
                      {new Date(comment.createdAt).toLocaleString(dateLocale)}
                    </div>
                  </div>
                }
                detail={
                  <div className="space-y-3 text-sm">
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                      <span>{t('comments.detail.article', { slug: comment.articleSlug })}</span>
                      {comment.email ? <span>{comment.email}</span> : null}
                    </div>
                    <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{comment.content}</p>
                    <div className="flex flex-wrap gap-2">
                      {!comment.isRead ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(comment, { isRead: true })}>
                          <Eye className="w-3 h-3 inline mr-1" />
                          {t('comments.actions.read')}
                        </button>
                      ) : null}
                      {comment.status !== 'approved' ? (
                        <button type="button" className="btn btn-primary text-xs px-2 py-1" onClick={() => void approveOne(comment.id)}>
                          <CheckCircle2 className="w-3 h-3 inline mr-1" />
                          {t('comments.actions.processed')}
                        </button>
                      ) : null}
                      {!comment.isArchived ? (
                        <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void markOne(comment, { isArchived: true })}>
                          <Archive className="w-3 h-3 inline mr-1" />
                          {t('comments.actions.archive')}
                        </button>
                      ) : null}
                      <button type="button" className="btn btn-danger text-xs px-2 py-1 ml-auto" onClick={() => void removeOne(comment.id)}>
                        <Trash2 className="w-3 h-3 inline mr-1" />
                        {t('comments.actions.delete')}
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
            itemLabel={t('comments.pagination.itemLabel')}
          />
        </>
      )}

      <OtpConfirmModal
        open={otpChallenge !== null}
        title={t('comments.otp.title')}
        description={t('comments.otp.description')}
        challengeId={otpChallenge?.id ?? ''}
        debugCode={otpChallenge?.debugCode}
        onClose={() => setOtpChallenge(null)}
        onVerified={load}
      />
    </div>
  );
};

export default CommentsManager;
