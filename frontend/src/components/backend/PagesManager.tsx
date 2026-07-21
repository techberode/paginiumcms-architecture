// frontend/src/components/backend/PagesManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { useAdminListQuery } from '../../hooks/useAdminListQuery';
import { queryKeys } from '../../api/queryKeys';
import { Link } from 'react-router-dom';
import type { PaginationMeta } from '../../api/client';
import { AdminListFilterBar } from './AdminListFilterBar';
import { AdminListPagination } from './AdminListPagination';
import { ContentListMobileCard } from './ContentListMobileCard';
import { BulkActionBar } from './BulkActionBar';
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
import { evaluateContentSeo } from '../../utils/seoHealth';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';
import type { ContentType } from '../../api/drafts';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { useI18n } from '../../context/I18nContext';

interface ContentItem {
  id: string;
  title: string;
  slug: string;
  status: 'draft' | 'published' | 'archived';
  author: string;
  createdAt: string;
  updatedAt: string;
  frontMatter?: Record<string, unknown>;
  featuredImage?: string;
  tags?: string[];
  ogImage?: string;
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
};

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const { t, locale } = useI18n();
  const queryClient = useQueryClient();
  const {
    page,
    search,
    debouncedSearch,
    statusFilter,
    seoIssuesOnly,
    sortField,
    sortDirection,
    handleSort,
    setSearch,
    setPage,
    setStatusFilter,
    setSeoIssuesOnly,
    resetFilters,
  } = useAdminListQueryParams('updatedAt', 'desc');
  const section = type === 'articles' ? 'articles' : 'pages';
  const [pageSize, setPageSize] = useAdminListPageSize(section);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewDraft, setPreviewDraft] = useState<ReturnType<typeof buildSitePreviewDraft> | null>(null);
  const [previewLoadingSlug, setPreviewLoadingSlug] = useState<string | null>(null);
  const { get, delete: del } = useApi();
  const toast = useToast();
  const isMobile = useMediaQuery('(max-width: 767px)');
  const { mode: viewMode, setMode: setViewMode } = useAdminViewMode(section, 'list');

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';
  const routeBase = type === 'articles' ? 'articles' : 'pages';
  const contentScope = type === 'articles' ? 'articles' : 'pages';
  const label = t(`content.${contentScope}.title`);
  const previewType = type === 'articles' ? 'article' : 'page';
  const itemLabel = t(`content.${contentScope}.itemAccusative`);
  const dateLocale = locale === 'en' ? 'en-GB' : 'sk-SK';

  const statusLabel = (status: ContentItem['status']): string => {
    const key = `list.status.${status}`;
    const translated = t(key);
    return translated !== key ? translated : status;
  };
  const hasActiveFilters =
    debouncedSearch.length >= 2 ||
    statusFilter !== 'all' ||
    seoIssuesOnly ||
    sortField !== 'updatedAt' ||
    sortDirection !== 'desc' ||
    page > 1;

  useEffect(() => {
    setPage(1);
  }, [type, pageSize, setPage]);

  const openListPreview = useCallback(
    async (item: ContentItem) => {
      setPreviewLoadingSlug(item.slug);
      try {
        const response = await get<ContentItem>(`${endpoint}/${item.slug}`);
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
    [endpoint, get, previewType, t, toast]
  );

  const listQueryKey = queryKeys.content.list(type, {
    page,
    pageSize,
    search: debouncedSearch,
    status: statusFilter,
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

  const getStatusBadge = (status: string) => {
    const classes = STATUS_BADGE_CLASS;
    return `badge ${classes[status as keyof typeof classes] || 'badge-info'}`;
  };

  const visibleItems = items.filter((item) => {
    if (!seoIssuesOnly) {
      return true;
    }
    return evaluateContentSeo({
      status: item.status,
      frontMatter: item.frontMatter,
      featuredImage: item.featuredImage,
      tags: item.tags,
    }) !== 'ok';
  });

  const bulkSelection = useBulkSelection(
    visibleItems.map((item) => item.slug),
    `${type}:${page}:${debouncedSearch}:${statusFilter}:${seoIssuesOnly}`
  );

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('content.confirm.bulkDelete', { count: bulkSelection.count }))) {
      return;
    }
    const result = await contentApi.bulkDelete(type, bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result));
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
    const result = await contentApi.bulkUpdateStatus(type, bulkSelection.selectedIds, status);
    if (result) {
      toast.success(summarizeBulkResult(result));
      bulkSelection.clear();
      await invalidateList();
    } else {
      toast.error(t('content.bulk.statusFailed'));
    }
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
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        pageSizeOptions={[5, 10, 20, 50]}
        showResetFilters={hasActiveFilters}
        onResetFilters={resetFilters}
      />

      <BulkActionBar
        count={bulkSelection.count}
        onClear={bulkSelection.clear}
        actions={[
          { id: 'publish', label: t('content.bulk.publish'), variant: 'primary', onClick: () => void handleBulkStatus('published') },
          { id: 'draft', label: t('content.bulk.draft'), variant: 'secondary', onClick: () => void handleBulkStatus('draft') },
          { id: 'archive', label: t('content.bulk.archive'), variant: 'secondary', onClick: () => void handleBulkStatus('archived') },
          { id: 'delete', label: t('content.bulk.delete'), variant: 'danger', onClick: () => void handleBulkDelete() },
        ]}
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
            const seoLevel = evaluateContentSeo({
              status: item.status,
              frontMatter: item.frontMatter,
              featuredImage: item.featuredImage,
              tags: item.tags,
            });
            return (
              <div key={item.id} className={`card overflow-hidden flex flex-col ${bulkSelection.isSelected(item.slug) ? 'ring-2 ring-indigo-500' : ''}`}>
                <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden relative">
                  <label className="absolute top-2 left-2 z-10 bg-white/90 dark:bg-gray-900/90 rounded p-1 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(item.slug)}
                      onChange={() => bulkSelection.toggle(item.slug)}
                      aria-label={t('list.select.item', { title: item.title })}
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
                    <p className="font-medium truncate">{item.title}</p>
                    <SeoHealthBadge level={seoLevel} />
                  </div>
                  <p className="text-xs text-gray-500 truncate">/{item.slug}</p>
                  <span className={getStatusBadge(item.status)}>{statusLabel(item.status)}</span>
                  <div className="flex gap-2 mt-auto pt-2">
                    <Link to={`/${routeBase}/${item.slug}`} className="btn btn-secondary text-xs px-3 py-1">
                      {t('list.actions.edit')}
                    </Link>
                    <button
                      type="button"
                      className="btn btn-secondary text-xs px-3 py-1"
                      disabled={previewLoadingSlug === item.slug}
                      onClick={() => void openListPreview(item)}
                    >
                      {previewLoadingSlug === item.slug ? t('list.actions.previewLoading') : t('list.actions.preview')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-danger text-xs px-3 py-1"
                      onClick={() => void handleDelete(item.slug)}
                    >
                      {t('list.actions.delete')}
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ) : isMobile ? (
        <div className="space-y-3">
          {visibleItems.map((item) => {
            const seoLevel = evaluateContentSeo({
              status: item.status,
              frontMatter: item.frontMatter,
              featuredImage: item.featuredImage,
              tags: item.tags,
            });
            return (
              <ContentListMobileCard
                key={item.id}
                title={item.title}
                slug={item.slug}
                status={item.status}
                statusBadgeClass={getStatusBadge(item.status)}
                statusLabel={statusLabel(item.status as ContentItem['status'])}
                seoLevel={seoLevel}
                updatedAt={item.updatedAt}
                routeBase={routeBase}
                selected={bulkSelection.isSelected(item.slug)}
                onToggleSelect={() => bulkSelection.toggle(item.slug)}
                onDelete={() => void handleDelete(item.slug)}
                onPreview={() => void openListPreview(item)}
                previewLoading={previewLoadingSlug === item.slug}
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
                    const seoLevel = evaluateContentSeo({
                      status: item.status,
                      frontMatter: item.frontMatter,
                      featuredImage: item.featuredImage,
                      tags: item.tags,
                    });
                    return (
                      <tr key={item.id}>
                        <td>
                          <input
                            type="checkbox"
                            checked={bulkSelection.isSelected(item.slug)}
                            onChange={() => bulkSelection.toggle(item.slug)}
                            aria-label={t('list.select.item', { title: item.title })}
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
                        <td className="font-medium max-w-[240px] truncate">{item.title}</td>
                        <td className="text-gray-500 dark:text-gray-400 hide-mobile max-w-[180px] truncate">{item.slug}</td>
                        <td>
                          <span className={getStatusBadge(item.status)}>{statusLabel(item.status)}</span>
                        </td>
                        <td>
                          <SeoHealthBadge level={seoLevel} />
                        </td>
                        <td className="text-sm text-gray-500 dark:text-gray-400 hide-tablet">
                          {new Date(item.updatedAt).toLocaleDateString(dateLocale)}
                        </td>
                        <td>
                          <div className="flex flex-wrap gap-2">
                            <Link
                              to={`/${routeBase}/${item.slug}`}
                              className="btn btn-secondary text-xs px-3 py-1"
                            >
                              {t('list.actions.edit')}
                            </Link>
                            <button
                              type="button"
                              className="btn btn-secondary text-xs px-3 py-1 hide-mobile"
                              disabled={previewLoadingSlug === item.slug}
                              onClick={() => void openListPreview(item)}
                            >
                              {previewLoadingSlug === item.slug ? t('list.actions.previewLoading') : t('list.actions.preview')}
                            </button>
                            <button
                              onClick={() => void handleDelete(item.slug)}
                              className="btn btn-danger text-xs px-3 py-1"
                            >
                              {t('list.actions.delete')}
                            </button>
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
