// frontend/src/components/backend/PagesManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { useAdminListQuery } from '../../hooks/useAdminListQuery';
import { queryKeys } from '../../api/queryKeys';
import { Link, useNavigate } from 'react-router-dom';
import type { PaginationMeta } from '../../api/client';
import { AdminListFilterBar } from './AdminListFilterBar';
import { AdminListPagination } from './AdminListPagination';
import { ContentListMobileCard } from './ContentListMobileCard';
import { BulkActionBar } from './BulkActionBar';
import { BulkTagsModal, type BulkTagMode } from './BulkTagsModal';
import { ContentSavedViewsBar } from './ContentSavedViewsBar';
import { SaveContentViewModal } from './SaveContentViewModal';
import { useAuth } from '../../hooks/useAuth';
import { useContentSavedViews } from '../../hooks/useContentSavedViews';
import { normalizeContentFilterPreset } from '../../utils/contentSavedViews';
import { SeoHealthBadge } from './SeoHealthBadge';
import { useAdminViewMode } from '../../hooks/useAdminViewMode';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useAdminListQueryParams } from '../../hooks/useAdminListQueryParams';
import { SitePreviewModal } from './SitePreviewModal';
import { buildSitePreviewDraft } from '../../utils/sitePreview';
import { SortableTableHeader } from './SortableTableHeader';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useMediaQuery } from '../../hooks/useMediaQuery';
import { contentApi } from '../../api/content';
import { summarizeBulkResult } from '../../types/bulk';
import { getContentSeoHealth } from '../../utils/seoHealth';
import { bulkSelectionCounts } from '../../utils/bulkSelectionLabel';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';
import type { ContentType } from '../../api/drafts';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDate } from '../../utils/contentDates';
import { LocaleStatusBadges } from './LocaleStatusBadges';
import type { ContentEditorStatus } from '../../utils/contentScheduling';

interface ContentItem {
  id: string;
  title: string;
  slug: string;
  path?: string;
  status: 'draft' | 'published' | 'archived' | 'scheduled';
  scheduledAt?: string;
  author: string;
  createdAt: string;
  updatedAt: string;
  frontMatter?: Record<string, unknown>;
  localizedContent?: Record<string, { title?: string; body?: string }>;
  featuredImage?: string;
  tags?: string[];
  ogImage?: string;
  localeStatus?: Record<string, ContentEditorStatus>;
  defaultLocale?: string;
  lastReviewedAt?: string;
  isStale?: boolean;
  monthsSinceReview?: number | null;
}

interface PagesManagerProps {
  type?: 'pages' | 'articles';
}

const DEFAULT_META: PaginationMeta = {
  page: 1,
  per_page: 20,
  total: 0,
  total_pages: 0,
};

function previewImageForItem(item: ContentItem): string {
  const fm = item.frontMatter ?? {};
  const raw = item.ogImage
    ?? item.featuredImage
    ?? (typeof fm.seoImage === 'string' ? fm.seoImage : '')
    ?? (typeof fm.featuredImage === 'string' ? fm.featuredImage : '');

  if (!raw || typeof raw !== 'string') {
    return '';
  }

  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/api/')) {
    return raw;
  }

  if (raw.startsWith('/storage/')) {
    return resolvePublicMediaUrl(raw);
  }

  if (raw.startsWith('media/')) {
    return resolveAdminMediaPreviewUrl(raw);
  }

  return raw;
}

const STATUS_BADGE_CLASS: Record<ContentItem['status'], string> = {
  published: 'badge-success',
  draft: 'badge-warning',
  archived: 'badge-danger',
  scheduled: 'badge-info',
};

function resolveContentListTitle(item: ContentItem, untitled: string): string {
  const direct = item.title?.trim();
  if (direct) {
    return direct;
  }

  const localized = item.localizedContent ?? {};
  for (const slice of Object.values(localized)) {
    const title = String(slice?.title ?? '').trim();
    if (title !== '') {
      return title;
    }
  }

  return untitled;
}

function slugFromStoragePath(path: string): string {
  const base = path.split('/').pop() ?? '';
  return base.replace(/\.(json|md)$/i, '').trim();
}

function resolveContentListSlug(item: ContentItem): string {
  const direct = item.slug?.trim();
  if (direct) {
    return direct;
  }

  if (item.path?.trim()) {
    return slugFromStoragePath(item.path);
  }

  return '';
}

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const { t, locale } = useI18n();
  const queryClient = useQueryClient();
  const {
    page,
    search,
    debouncedSearch,
    statusFilter,
    seoIssuesOnly,
    staleOnly,
    sortField,
    sortDirection,
    handleSort,
    setSearch,
    setPage,
    setStatusFilter,
    setSeoIssuesOnly,
    setStaleOnly,
    resetFilters,
    applyFilterPreset,
    getCurrentFilterPreset,
    tagFilter,
  } = useAdminListQueryParams('updatedAt', 'desc');
  const section = type === 'articles' ? 'articles' : 'pages';
  const [pageSize, setPageSize] = useAdminListPageSize(section);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewDraft, setPreviewDraft] = useState<ReturnType<typeof buildSitePreviewDraft> | null>(null);
  const [previewLoadingSlug, setPreviewLoadingSlug] = useState<string | null>(null);
  const [bulkTagsOpen, setBulkTagsOpen] = useState(false);
  const [saveViewOpen, setSaveViewOpen] = useState(false);
  const { user } = useAuth();
  const { get, delete: del } = useApi();
  const toast = useToast();
  const navigate = useNavigate();
  const {
    views: savedViews,
    saveCurrentView,
    deleteView,
    renameView,
    hideDefaultView,
    isViewActive,
  } = useContentSavedViews(user?.id, type);
  const isMobile = useMediaQuery('(max-width: 767px)');
  const { mode: viewMode, setMode: setViewMode } = useAdminViewMode(section, 'list');

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';
  const routeBase = type === 'articles' ? 'articles' : 'pages';
  const contentScope = type === 'articles' ? 'articles' : 'pages';
  const label = t(`content.${contentScope}.title`);
  const previewType = type === 'articles' ? 'article' : 'page';
  const itemLabel = t(`content.${contentScope}.itemAccusative`);
  const untitledLabel = t('editor.sitePreview.untitled');

  const itemDisplayTitle = useCallback(
    (item: ContentItem) => resolveContentListTitle(item, untitledLabel),
    [untitledLabel]
  );

  const itemListSlug = useCallback(
    (item: ContentItem) => resolveContentListSlug(item),
    []
  );

  const statusLabel = (status: ContentItem['status']): string => {
    const key = `list.status.${status}`;
    const translated = t(key);
    return translated !== key ? translated : status;
  };

  const editorStatusLabels = useMemo(
    (): Record<ContentEditorStatus, string> => ({
      draft: statusLabel('draft'),
      published: statusLabel('published'),
      archived: statusLabel('archived'),
      scheduled: statusLabel('scheduled'),
    }),
    [t]
  );
  const hasActiveFilters =
    debouncedSearch.length >= 2 ||
    statusFilter !== 'all' ||
    tagFilter !== '' ||
    seoIssuesOnly ||
    staleOnly ||
    sortField !== 'updatedAt' ||
    sortDirection !== 'desc' ||
    page > 1;

  useEffect(() => {
    setPage(1);
  }, [type, pageSize, setPage]);

  const openListPreview = useCallback(
    async (item: ContentItem) => {
      const listSlug = itemListSlug(item);
      if (!listSlug) {
        toast.warning(t('editor.markdown.toast.slugRequired'));
        return;
      }
      setPreviewLoadingSlug(listSlug);
      try {
        const response = await get<ContentItem>(`${endpoint}/${listSlug}`);
        if (response.success && response.data) {
          setPreviewDraft(buildSitePreviewDraft(previewType as ContentType, response.data));
          setPreviewOpen(true);
        } else {
          toast.error(t('content.preview.loadFailed'));
        }
      } catch {
        toast.error(t('content.preview.loadFailed'));
      } finally {
        setPreviewLoadingSlug(null);
      }
    },
    [endpoint, get, itemListSlug, previewType, t, toast]
  );

  const listQueryKey = queryKeys.content.list(type, {
    page,
    pageSize,
    search: debouncedSearch,
    status: statusFilter,
    tag: tagFilter,
    staleOnly,
    sortField,
    sortDirection,
  });

  const { data: listData, isLoading } = useAdminListQuery({
    queryKey: listQueryKey,
    queryFn: async () => {
      const params = new URLSearchParams({
        page: String(page),
        per_page: String(pageSize),
        sort: `${sortDirection === 'desc' ? '-' : ''}${sortField}`,
      });
      if (debouncedSearch.length >= 2) {
        params.set('search', debouncedSearch);
      }
      if (statusFilter !== 'all') {
        params.set('status', statusFilter);
      }
      if (type === 'articles' && tagFilter.trim() !== '') {
        params.set('tag', tagFilter.trim());
      }
      if (staleOnly) {
        params.set('stale', '1');
      }

      const response = await get<ContentItem[]>(`${endpoint}?${params.toString()}`);
      if (!response.success) {
        throw new Error(t(`content.${contentScope}.loadError`));
      }

      return {
        items: response.data || [],
        meta: response.meta ?? { ...DEFAULT_META, page },
      };
    },
  });

  const items = listData?.items ?? [];
  const meta = listData?.meta ?? DEFAULT_META;
  const loading = isLoading && !listData;

  const invalidateList = () =>
    queryClient.invalidateQueries({ queryKey: ['admin', 'content', type, 'list'] });

  const handleDelete = async (slug: string) => {
    if (!confirm(t('content.confirm.deleteOne', { item: itemLabel }))) {
      return;
    }

    try {
      const response = await del(`${endpoint}/${slug}`);
      if (response.success) {
        toast.success(t('content.toast.deleted', { item: itemLabel }));
        await invalidateList();
      } else {
        toast.error(response.error || t('content.toast.deleteFailed', { item: itemLabel }));
      }
    } catch (error) {
      toast.error(t('content.toast.deleteFailed', { item: itemLabel }));
      console.error(error);
    }
  };

  const handleDuplicate = async (slug: string) => {
    try {
      const result = await contentApi.duplicate(type, slug);
      if (result?.slug) {
        toast.success(t('content.duplicate.success'));
        await invalidateList();
        navigate(`/${routeBase}/${result.slug}`);
        return;
      }
      toast.error(t('content.duplicate.failed'));
    } catch (error) {
      toast.error(t('content.duplicate.failed'));
      console.error(error);
    }
  };

  const getStatusBadge = (status: string) => {
    const classes = STATUS_BADGE_CLASS;
    return `badge ${classes[status as keyof typeof classes] || 'badge-info'}`;
  };

  const staleBadgeLabel = (item: ContentItem): string | null => {
    if (!item.isStale) {
      return null;
    }
    return t('content.stale.badge', { count: item.monthsSinceReview ?? 0 });
  };

  const itemSeoHealth = (item: (typeof items)[number]) =>
    getContentSeoHealth({
      status: item.status,
      frontMatter: item.frontMatter,
      featuredImage: item.featuredImage,
      tags: item.tags,
      contentType: type === 'articles' ? 'article' : 'page',
    });

  const visibleItems = items.filter((item) => {
    if (!seoIssuesOnly) {
      return true;
    }
    return itemSeoHealth(item).level !== 'ok';
  });

  const bulkSelection = useBulkSelection(
    visibleItems.map((item) => itemListSlug(item)).filter((slug) => slug !== ''),
    `${type}:${page}:${debouncedSearch}:${statusFilter}:${tagFilter}:${seoIssuesOnly}`
  );
  const bulkListTotal = seoIssuesOnly ? visibleItems.length : meta.total;

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('content.confirm.bulkDelete', bulkSelectionCounts(bulkSelection.count, bulkListTotal)))) {
      return;
    }
    const result = await contentApi.bulkDelete(type, bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await invalidateList();
    } else {
      toast.error(t('content.bulk.deleteFailed'));
    }
  };

  const handleBulkStatus = async (status: ContentItem['status']) => {
    if (bulkSelection.count === 0) {
      return;
    }
    const confirmKey =
      status === 'published'
        ? 'content.confirm.bulkPublish'
        : status === 'draft'
          ? 'content.confirm.bulkDraft'
          : 'content.confirm.bulkArchive';
    if (!confirm(t(confirmKey, bulkSelectionCounts(bulkSelection.count, bulkListTotal)))) {
      return;
    }
    const result = await contentApi.bulkUpdateStatus(type, bulkSelection.selectedIds, status);
    if (result.success && result.data) {
      toast.success(summarizeBulkResult(result.data, t));
      bulkSelection.clear();
      await invalidateList();
    } else {
      toast.error(result.error || t('content.bulk.statusFailed'));
    }
  };

  const handleBulkTags = async (mode: BulkTagMode, tags: string[]) => {
    if (bulkSelection.count === 0) {
      return;
    }
    const result = await contentApi.bulkUpdateTags(type, bulkSelection.selectedIds, mode, tags);
    if (result) {
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await invalidateList();
      return;
    }
    toast.error(t('content.bulkTags.failed'));
  };

  const currentFilterPreset = getCurrentFilterPreset();

  const handleSaveCurrentView = async (name: string) => {
    const result = saveCurrentView(name, currentFilterPreset);
    if (result.error === 'limit') {
      toast.error(t('content.savedViews.limitReached'));
      return;
    }
    if (result.error === 'name') {
      toast.error(t('content.savedViews.nameRequired'));
      return;
    }
    if (result.view) {
      toast.success(t('content.savedViews.saved'));
    }
  };

  const handleRenameView = (viewId: string, name: string) => {
    const result = renameView(viewId, name);
    if (result.error === 'name') {
      toast.error(t('content.savedViews.nameRequired'));
      return;
    }
    toast.success(t('content.savedViews.renamed'));
  };

  if (loading) {
    return <AdminListSkeleton rows={8} />;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{label}</h1>
        <Link to={`/${routeBase}/new`} className="btn btn-primary w-full sm:w-auto justify-center">
          + {t('content.newItem')}
        </Link>
      </div>

      <AdminListFilterBar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t(`content.${contentScope}.searchPlaceholder`)}
        statusFilter={statusFilter}
        onStatusFilterChange={setStatusFilter}
        viewMode={viewMode}
        onViewModeChange={setViewMode}
        showViewToggle
        seoIssuesOnly={seoIssuesOnly}
        onSeoIssuesOnlyChange={setSeoIssuesOnly}
        showSeoFilter
        staleOnly={staleOnly}
        onStaleOnlyChange={setStaleOnly}
        showStaleFilter
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
        showResetFilters={hasActiveFilters}
        onResetFilters={resetFilters}
      />

      <ContentSavedViewsBar
        views={savedViews}
        activePreset={normalizeContentFilterPreset(currentFilterPreset)}
        isViewActive={isViewActive}
        onApply={(view) => applyFilterPreset(view.preset)}
        onSaveCurrent={() => setSaveViewOpen(true)}
        onHideDefault={hideDefaultView}
        onRename={handleRenameView}
        onDelete={deleteView}
      />

      <SaveContentViewModal
        open={saveViewOpen}
        onClose={() => setSaveViewOpen(false)}
        onSave={handleSaveCurrentView}
      />

      <BulkActionBar
        count={bulkSelection.count}
        totalCount={bulkListTotal}
        onClear={bulkSelection.clear}
        actions={[
          { id: 'publish', label: t('content.bulk.publish'), variant: 'primary', onClick: () => void handleBulkStatus('published') },
          { id: 'draft', label: t('content.bulk.draft'), variant: 'secondary', onClick: () => void handleBulkStatus('draft') },
          { id: 'archive', label: t('content.bulk.archive'), variant: 'secondary', onClick: () => void handleBulkStatus('archived') },
          { id: 'tags', label: t('content.bulkTags.action'), variant: 'secondary', onClick: () => setBulkTagsOpen(true) },
          { id: 'delete', label: t('content.bulk.delete'), variant: 'danger', onClick: () => void handleBulkDelete() },
        ]}
      />

      <BulkTagsModal
        open={bulkTagsOpen}
        count={bulkSelection.count}
        onClose={() => setBulkTagsOpen(false)}
        onSubmit={handleBulkTags}
      />

      {visibleItems.length === 0 ? (
        <div className="card">
          <div className="card-body text-center py-8 text-gray-500 dark:text-gray-400">
            {t(`content.${contentScope}.empty`)}
          </div>
        </div>
      ) : viewMode === 'preview' ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {visibleItems.map((item) => {
            const preview = previewImageForItem(item);
            const seoHealth = itemSeoHealth(item);
            const listSlug = itemListSlug(item);
            const actionsDisabled = listSlug === '';
            return (
              <div key={item.id} className={`card overflow-hidden flex flex-col ${bulkSelection.isSelected(listSlug) ? 'ring-2 ring-indigo-500' : ''}`}>
                <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden relative">
                  <label className="absolute top-2 left-2 z-10 bg-white/90 dark:bg-gray-900/90 rounded p-1 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(listSlug)}
                      onChange={() => bulkSelection.toggle(listSlug)}
                      disabled={actionsDisabled}
                      aria-label={t('list.select.item', { title: itemDisplayTitle(item) })}
                      className="rounded border-gray-300"
                    />
                  </label>
                  {preview ? (
                    <img src={preview} alt="" className="w-full h-full object-cover" />
                  ) : (
                    <span className="text-sm text-gray-400">{t('list.noPreviewImage')}</span>
                  )}
                </div>
                <div className="card-body space-y-2 flex-1 flex flex-col">
                  <div className="flex items-start justify-between gap-2">
                    <p className="font-medium truncate">{itemDisplayTitle(item)}</p>
                    <SeoHealthBadge level={seoHealth.level} issues={seoHealth.issues} />
                  </div>
                  <p className="text-xs text-gray-500 truncate">/{listSlug || '—'}</p>
                  <div className="flex flex-wrap items-center gap-2">
                    <span className={getStatusBadge(item.status)}>{statusLabel(item.status)}</span>
                    <LocaleStatusBadges
                      localeStatus={item.localeStatus}
                      statusLabels={editorStatusLabels}
                    />
                  </div>
                  <div className="flex gap-2 mt-auto pt-2">
                    {actionsDisabled ? (
                      <span className="text-xs text-amber-600 dark:text-amber-400">{t('editor.markdown.toast.slugRequired')}</span>
                    ) : (
                      <>
                    <Link to={`/${routeBase}/${listSlug}`} className="btn btn-secondary text-xs px-3 py-1">
                      {t('list.actions.edit')}
                    </Link>
                    <button
                      type="button"
                      className="btn btn-secondary text-xs px-3 py-1"
                      disabled={previewLoadingSlug === listSlug}
                      onClick={() => void openListPreview(item)}
                    >
                      {previewLoadingSlug === listSlug ? t('list.actions.previewLoading') : t('list.actions.preview')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-secondary text-xs px-3 py-1"
                      onClick={() => void handleDuplicate(listSlug)}
                    >
                      {t('content.duplicate.action')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-danger text-xs px-3 py-1"
                      onClick={() => void handleDelete(listSlug)}
                    >
                      {t('list.actions.delete')}
                    </button>
                      </>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ) : isMobile ? (
        <div className="space-y-3">
          {visibleItems.map((item) => {
            const seoHealth = itemSeoHealth(item);
            const listSlug = itemListSlug(item);
            return (
              <ContentListMobileCard
                key={item.id}
                title={itemDisplayTitle(item)}
                slug={listSlug}
                status={item.status}
                statusBadgeClass={getStatusBadge(item.status)}
                statusLabel={statusLabel(item.status as ContentItem['status'])}
                seoLevel={seoHealth.level}
                seoIssues={seoHealth.issues}
                updatedAt={item.updatedAt}
                scheduledAt={
                  item.status === 'scheduled'
                    ? String(item.scheduledAt ?? item.frontMatter?.scheduledAt ?? '')
                    : undefined
                }
                routeBase={routeBase}
                selected={bulkSelection.isSelected(listSlug)}
                onToggleSelect={() => bulkSelection.toggle(listSlug)}
                onDelete={() => void handleDelete(listSlug)}
                onDuplicate={() => void handleDuplicate(listSlug)}
                onPreview={() => void openListPreview(item)}
                previewLoading={previewLoadingSlug === listSlug}
                actionsDisabled={listSlug === ''}
                staleLabel={staleBadgeLabel(item)}
              />
            );
          })}
        </div>
      ) : (
        <div className="card">
          <div className="card-body p-0">
            <div className="table-container">
              <table className="table w-full">
                <thead>
                  <tr>
                    <th className="w-10">
                      <input
                        type="checkbox"
                        checked={bulkSelection.allSelected && visibleItems.length > 0}
                        onChange={bulkSelection.toggleAll}
                        aria-label={t('list.select.allVisible')}
                      />
                    </th>
                    {viewMode === 'list-preview' && <th className="w-24 hide-mobile">{t('content.table.preview')}</th>}
                    <SortableTableHeader
                      label={t('content.table.title')}
                      field="title"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                    />
                    <SortableTableHeader
                      label={t('content.table.slug')}
                      field="slug"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                      thClassName="hide-mobile"
                    />
                    <SortableTableHeader
                      label={t('content.table.status')}
                      field="status"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                    />
                    <th className="hide-tablet">{t('content.table.scheduledAt')}</th>
                    <th>{t('content.table.seo')}</th>
                    <SortableTableHeader
                      label={t('content.table.updated')}
                      field="updatedAt"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                      thClassName="hide-tablet"
                    />
                    <th>{t('content.table.actions')}</th>
                  </tr>
                </thead>
                <tbody>
                  {visibleItems.map((item) => {
                    const preview = previewImageForItem(item);
                    const seoHealth = itemSeoHealth(item);
                    const listSlug = itemListSlug(item);
                    const actionsDisabled = listSlug === '';
                    return (
                      <tr key={item.id}>
                        <td>
                          <input
                            type="checkbox"
                            checked={bulkSelection.isSelected(listSlug)}
                            onChange={() => bulkSelection.toggle(listSlug)}
                            disabled={actionsDisabled}
                            aria-label={t('list.select.item', { title: itemDisplayTitle(item) })}
                          />
                        </td>
                        {viewMode === 'list-preview' && (
                          <td className="hide-mobile">
                            {preview ? (
                              <img src={preview} alt="" className="w-16 h-12 object-cover rounded bg-gray-100" />
                            ) : (
                              <span className="text-xs text-gray-400">—</span>
                            )}
                          </td>
                        )}
                        <td className="font-medium max-w-[240px] truncate">{itemDisplayTitle(item)}</td>
                        <td className="text-gray-500 dark:text-gray-400 hide-mobile max-w-[180px] truncate">{listSlug || '—'}</td>
                        <td>
                          <div className="flex flex-col gap-1">
                            <span className={getStatusBadge(item.status)}>{statusLabel(item.status)}</span>
                            <LocaleStatusBadges
                              localeStatus={item.localeStatus}
                              statusLabels={editorStatusLabels}
                            />
                            {staleBadgeLabel(item) ? (
                              <span className="badge badge-warning text-[10px]">{staleBadgeLabel(item)}</span>
                            ) : null}
                          </div>
                        </td>
                        <td className="text-sm text-gray-500 dark:text-gray-400 hide-tablet">
                          {item.status === 'scheduled'
                            ? formatDisplayDate(
                                String(item.scheduledAt ?? item.frontMatter?.scheduledAt ?? ''),
                                locale
                              ) || '—'
                            : '—'}
                        </td>
                        <td>
                          <SeoHealthBadge level={seoHealth.level} issues={seoHealth.issues} />
                        </td>
                        <td className="text-sm text-gray-500 dark:text-gray-400 hide-tablet">
                          {formatDisplayDate(item.updatedAt, locale)}
                        </td>
                        <td>
                          <div className="flex flex-wrap gap-2">
                            {actionsDisabled ? (
                              <span className="text-xs text-amber-600 dark:text-amber-400">{t('editor.markdown.toast.slugRequired')}</span>
                            ) : (
                              <>
                            <Link
                              to={`/${routeBase}/${listSlug}`}
                              className="btn btn-secondary text-xs px-3 py-1"
                            >
                              {t('list.actions.edit')}
                            </Link>
                            <button
                              type="button"
                              className="btn btn-secondary text-xs px-3 py-1 hide-mobile"
                              disabled={previewLoadingSlug === listSlug}
                              onClick={() => void openListPreview(item)}
                            >
                              {previewLoadingSlug === listSlug ? t('list.actions.previewLoading') : t('list.actions.preview')}
                            </button>
                            <button
                              type="button"
                              className="btn btn-secondary text-xs px-3 py-1"
                              onClick={() => void handleDuplicate(listSlug)}
                            >
                              {t('content.duplicate.action')}
                            </button>
                            <button
                              onClick={() => void handleDelete(listSlug)}
                              className="btn btn-danger text-xs px-3 py-1"
                            >
                              {t('list.actions.delete')}
                            </button>
                              </>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      <AdminListPagination
        page={meta.page}
        totalPages={Math.max(meta.total_pages, 1)}
        total={meta.total}
        pageSize={pageSize}
        loading={loading}
        onPageChange={setPage}
      />

      <SitePreviewModal
        open={previewOpen}
        onClose={() => setPreviewOpen(false)}
        draft={previewDraft}
      />
    </div>
  );
};

export default PagesManager;
