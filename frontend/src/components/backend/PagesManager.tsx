// frontend/src/components/backend/PagesManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { Link } from 'react-router-dom';
import type { PaginationMeta } from '../../api/client';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { ContentListMobileCard } from './ContentListMobileCard';
import { BulkActionBar } from './BulkActionBar';
import { SeoHealthBadge } from './SeoHealthBadge';
import { useAdminViewMode } from '../../hooks/useAdminViewMode';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { SortableTableHeader } from './SortableTableHeader';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useMediaQuery } from '../../hooks/useMediaQuery';
import { contentApi } from '../../api/content';
import { summarizeBulkResult } from '../../types/bulk';
import { evaluateContentSeo } from '../../utils/seoHealth';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';
import { resolvePreviewPath } from '../../utils/contentEditorMeta';

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

const STATUS_LABELS: Record<ContentItem['status'], string> = {
  published: 'Publikované',
  draft: 'Koncept',
  archived: 'Archivované',
};

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const [items, setItems] = useState<ContentItem[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(DEFAULT_META);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [seoIssuesOnly, setSeoIssuesOnly] = useState(false);
  const [page, setPage] = useState(1);
  const { sortField, sortDirection, handleSort } = useColumnSort('updatedAt', 'desc');
  const section = type === 'articles' ? 'articles' : 'pages';
  const [pageSize, setPageSize] = useAdminListPageSize(section);
  const { get, delete: del } = useApi();
  const toast = useToast();
  const isMobile = useMediaQuery('(max-width: 767px)');
  const { mode: viewMode, setMode: setViewMode } = useAdminViewMode(section, 'list');

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';
  const routeBase = type === 'articles' ? 'articles' : 'pages';
  const label = type === 'articles' ? 'Články' : 'Podstránky';
  // contentEditorMeta pracuje s jednotným tvarom ('page' | 'article').
  const previewType = type === 'articles' ? 'article' : 'page';
  const itemLabel = type === 'articles' ? 'článok' : 'podstránku';

  useEffect(() => {
    const timer = window.setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => window.clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [type, debouncedSearch, statusFilter, sortField, sortDirection, pageSize]);

  const loadItems = useCallback(async () => {
    setLoading(true);
    try {
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
      if (response.success) {
        setItems(response.data || []);
        setMeta(response.meta ?? { ...DEFAULT_META, page });
      }
    } catch (error) {
      toast.error(`Nepodarilo sa načítať ${type === 'articles' ? 'články' : 'podstránky'}`);
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [debouncedSearch, endpoint, get, page, pageSize, sortDirection, sortField, statusFilter, toast, type]);

  useEffect(() => {
    void loadItems();
  }, [loadItems]);

  const handleDelete = async (slug: string) => {
    if (!confirm(`Naozaj chcete zmazať túto ${itemLabel}?`)) {
      return;
    }

    try {
      const response = await del(`${endpoint}/${slug}`);
      if (response.success) {
        toast.success(`${itemLabel} bol zmazaný`);
        await loadItems();
      } else {
        toast.error(response.error || `Nepodarilo sa zmazať ${itemLabel}`);
      }
    } catch (error) {
      toast.error(`Nepodarilo sa zmazať ${itemLabel}`);
      console.error(error);
    }
  };

  const getStatusBadge = (status: string) => {
    const classes = {
      published: 'badge-success',
      draft: 'badge-warning',
      archived: 'badge-danger',
    };
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
    if (!confirm(`Zmazať ${bulkSelection.count} vybraných položiek?`)) {
      return;
    }
    const result = await contentApi.bulkDelete(type, bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result));
      bulkSelection.clear();
      await loadItems();
    } else {
      toast.error('Hromadné mazanie zlyhalo.');
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
      await loadItems();
    } else {
      toast.error('Hromadná zmena stavu zlyhala.');
    }
  };

  if (loading && items.length === 0) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{label}</h1>
        <Link to={`/${routeBase}/new`} className="btn btn-primary w-full sm:w-auto justify-center">
          + Nová položka
        </Link>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={`Hľadať ${type === 'articles' ? 'články' : 'podstránky'}…`}
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
      />

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="vybraných položiek"
        onClear={bulkSelection.clear}
        actions={[
          { id: 'publish', label: 'Publikovať', variant: 'primary', onClick: () => void handleBulkStatus('published') },
          { id: 'draft', label: 'Koncept', variant: 'secondary', onClick: () => void handleBulkStatus('draft') },
          { id: 'archive', label: 'Archivovať', variant: 'secondary', onClick: () => void handleBulkStatus('archived') },
          { id: 'delete', label: 'Zmazať', variant: 'danger', onClick: () => void handleBulkDelete() },
        ]}
      />

      {visibleItems.length === 0 ? (
        <div className="card">
          <div className="card-body text-center py-8 text-gray-500 dark:text-gray-400">
            Nenašli sa žiadne {type === 'articles' ? 'články' : 'podstránky'}
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
                      aria-label={`Select ${item.title}`}
                      className="rounded border-gray-300"
                    />
                  </label>
                  {preview ? (
                    <img src={preview} alt="" className="w-full h-full object-cover" />
                  ) : (
                    <span className="text-sm text-gray-400">No preview image</span>
                  )}
                </div>
                <div className="card-body space-y-2 flex-1 flex flex-col">
                  <div className="flex items-start justify-between gap-2">
                    <p className="font-medium truncate">{item.title}</p>
                    <SeoHealthBadge level={seoLevel} />
                  </div>
                  <p className="text-xs text-gray-500 truncate">/{item.slug}</p>
                  <span className={getStatusBadge(item.status)}>{STATUS_LABELS[item.status] ?? item.status}</span>
                  <div className="flex gap-2 mt-auto pt-2">
                    <Link to={`/${routeBase}/${item.slug}`} className="btn btn-secondary text-xs px-3 py-1">
                      Upraviť
                    </Link>
                    <button
                      type="button"
                      className="btn btn-danger text-xs px-3 py-1"
                      onClick={() => void handleDelete(item.slug)}
                    >
                      Zmazať
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
                statusLabel={STATUS_LABELS[item.status] ?? item.status}
                seoLevel={seoLevel}
                updatedAt={item.updatedAt}
                routeBase={routeBase}
                selected={bulkSelection.isSelected(item.slug)}
                onToggleSelect={() => bulkSelection.toggle(item.slug)}
                onDelete={() => void handleDelete(item.slug)}
                previewUrl={resolvePreviewPath(previewType, item.slug)}
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
                        aria-label="Select all visible items"
                      />
                    </th>
                    {viewMode === 'list-preview' && <th className="w-24 hide-mobile">Náhľad</th>}
                    <SortableTableHeader
                      label="Názov"
                      field="title"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                    />
                    <SortableTableHeader
                      label="Slug"
                      field="slug"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                      thClassName="hide-mobile"
                    />
                    <SortableTableHeader
                      label="Stav"
                      field="status"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                    />
                    <th>SEO</th>
                    <SortableTableHeader
                      label="Upravené"
                      field="updatedAt"
                      activeField={sortField}
                      direction={sortDirection}
                      onSort={handleSort}
                      thClassName="hide-tablet"
                    />
                    <th>Akcie</th>
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
                            aria-label={`Select ${item.title}`}
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
                          <span className={getStatusBadge(item.status)}>{STATUS_LABELS[item.status] ?? item.status}</span>
                        </td>
                        <td>
                          <SeoHealthBadge level={seoLevel} />
                        </td>
                        <td className="text-sm text-gray-500 dark:text-gray-400 hide-tablet">
                          {new Date(item.updatedAt).toLocaleDateString('sk-SK')}
                        </td>
                        <td>
                          <div className="flex flex-wrap gap-2">
                            <Link
                              to={`/${routeBase}/${item.slug}`}
                              className="btn btn-secondary text-xs px-3 py-1"
                            >
                              Upraviť
                            </Link>
                            {resolvePreviewPath(previewType, item.slug) && (
                              <Link
                                to={resolvePreviewPath(previewType, item.slug)!}
                                target="_blank"
                                className="btn btn-secondary text-xs px-3 py-1 hide-mobile"
                              >
                                Náhľad
                              </Link>
                            )}
                            <button
                              onClick={() => void handleDelete(item.slug)}
                              className="btn btn-danger text-xs px-3 py-1"
                            >
                              Zmazať
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
    </div>
  );
};

export default PagesManager;
