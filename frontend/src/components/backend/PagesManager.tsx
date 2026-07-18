// frontend/src/components/backend/PagesManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { Link } from 'react-router-dom';
import type { PaginationMeta } from '../../api/client';
import { AdminViewModeToggle } from './AdminViewModeToggle';
import { BulkActionBar } from './BulkActionBar';
import { SeoHealthBadge } from './SeoHealthBadge';
import { useAdminViewMode } from '../../hooks/useAdminViewMode';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { contentApi } from '../../api/content';
import { summarizeBulkResult } from '../../types/bulk';
import { evaluateContentSeo } from '../../utils/seoHealth';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';

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

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const [items, setItems] = useState<ContentItem[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(DEFAULT_META);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [seoIssuesOnly, setSeoIssuesOnly] = useState(false);
  const [page, setPage] = useState(1);
  const { get, del } = useApi();
  const toast = useToast();
  const section = type === 'articles' ? 'articles' : 'pages';
  const { mode: viewMode, setMode: setViewMode } = useAdminViewMode(section, 'list');

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';
  const routeBase = type === 'articles' ? 'articles' : 'pages';
  const label = type === 'articles' ? 'Articles' : 'Pages';

  useEffect(() => {
    const timer = window.setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => window.clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    setPage(1);
  }, [type, debouncedSearch, statusFilter]);

  const loadItems = useCallback(async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        page: String(page),
        per_page: '20',
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
      toast.error(`Failed to load ${type}`);
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [debouncedSearch, endpoint, get, page, statusFilter, toast, type]);

  useEffect(() => {
    void loadItems();
  }, [loadItems]);

  const handleDelete = async (slug: string) => {
    if (!confirm(`Are you sure you want to delete this ${type.slice(0, -1)}?`)) {
      return;
    }

    try {
      const response = await del(`${endpoint}/${slug}`);
      if (response.success) {
        toast.success(`${type.slice(0, -1)} deleted successfully`);
        await loadItems();
      } else {
        toast.error(response.error || `Failed to delete ${type.slice(0, -1)}`);
      }
    } catch (error) {
      toast.error(`Failed to delete ${type.slice(0, -1)}`);
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
    if (!confirm(`Delete ${bulkSelection.count} selected ${type}?`)) {
      return;
    }
    const result = await contentApi.bulkDelete(type, bulkSelection.selectedIds);
    if (result) {
      toast.success(summarizeBulkResult(result));
      bulkSelection.clear();
      await loadItems();
    } else {
      toast.error('Bulk delete failed.');
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
      toast.error('Bulk status update failed.');
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
        <Link to={`/${routeBase}/new`} className="btn btn-primary">
          + Create New
        </Link>
      </div>

      <div className="flex flex-wrap gap-4 items-center">
        <div className="flex-1 min-w-[200px]">
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={`Search ${type}…`}
            className="form-input"
          />
        </div>
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="form-input w-auto"
        >
          <option value="all">All Status</option>
          <option value="published">Published</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select>
        <AdminViewModeToggle mode={viewMode} onChange={setViewMode} />
        <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
          <input
            type="checkbox"
            checked={seoIssuesOnly}
            onChange={(e) => setSeoIssuesOnly(e.target.checked)}
            className="rounded border-gray-300"
          />
          SEO issues only
        </label>
      </div>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={`${type} selected`}
        onClear={bulkSelection.clear}
        actions={[
          { id: 'publish', label: 'Publish', variant: 'primary', onClick: () => void handleBulkStatus('published') },
          { id: 'draft', label: 'Draft', variant: 'secondary', onClick: () => void handleBulkStatus('draft') },
          { id: 'archive', label: 'Archive', variant: 'secondary', onClick: () => void handleBulkStatus('archived') },
          { id: 'delete', label: 'Delete', variant: 'danger', onClick: () => void handleBulkDelete() },
        ]}
      />

      {visibleItems.length === 0 ? (
        <div className="card">
          <div className="card-body text-center py-8 text-gray-500 dark:text-gray-400">
            No {type} found
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
                  <span className={getStatusBadge(item.status)}>{item.status}</span>
                  <div className="flex gap-2 mt-auto pt-2">
                    <Link to={`/${routeBase}/${item.slug}`} className="btn btn-secondary text-xs px-3 py-1">
                      Edit
                    </Link>
                    <button
                      type="button"
                      className="btn btn-danger text-xs px-3 py-1"
                      onClick={() => void handleDelete(item.slug)}
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        <div className="card">
          <div className="card-body p-0">
            <div className="table-container">
              <table className="table">
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
                    {viewMode === 'list-preview' && <th className="w-24">Preview</th>}
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>SEO</th>
                    <th>Updated</th>
                    <th>Actions</th>
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
                          <td>
                            {preview ? (
                              <img src={preview} alt="" className="w-16 h-12 object-cover rounded bg-gray-100" />
                            ) : (
                              <span className="text-xs text-gray-400">—</span>
                            )}
                          </td>
                        )}
                        <td className="font-medium">{item.title}</td>
                        <td className="text-gray-500 dark:text-gray-400">{item.slug}</td>
                        <td>
                          <span className={getStatusBadge(item.status)}>{item.status}</span>
                        </td>
                        <td>
                          <SeoHealthBadge level={seoLevel} />
                        </td>
                        <td className="text-sm text-gray-500 dark:text-gray-400">
                          {new Date(item.updatedAt).toLocaleDateString()}
                        </td>
                        <td>
                          <div className="flex gap-2">
                            <Link
                              to={`/${routeBase}/${item.slug}`}
                              className="btn btn-secondary text-xs px-3 py-1"
                            >
                              Edit
                            </Link>
                            <Link
                              to={`/preview/${item.slug}`}
                              target="_blank"
                              className="btn btn-secondary text-xs px-3 py-1"
                            >
                              View
                            </Link>
                            <button
                              onClick={() => void handleDelete(item.slug)}
                              className="btn btn-danger text-xs px-3 py-1"
                            >
                              Delete
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

      {meta.total_pages > 1 && (
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            {meta.total} records · page {meta.page} / {meta.total_pages}
          </p>
          <div className="flex gap-2">
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={page <= 1 || loading}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              Previous
            </button>
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={page >= meta.total_pages || loading}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default PagesManager;
