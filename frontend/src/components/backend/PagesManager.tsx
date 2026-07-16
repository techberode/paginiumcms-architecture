// frontend/src/components/backend/PagesManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { Link } from 'react-router-dom';
import type { PaginationMeta } from '../../api/client';

interface Page {
  id: string;
  title: string;
  slug: string;
  status: 'draft' | 'published' | 'archived';
  author: string;
  createdAt: string;
  updatedAt: string;
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

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const [items, setItems] = useState<Page[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>(DEFAULT_META);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [page, setPage] = useState(1);
  const { get, del } = useApi();
  const toast = useToast();

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';

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

      const response = await get<Page[]>(`${endpoint}?${params.toString()}`);
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

  if (loading && items.length === 0) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white capitalize">
          {type === 'articles' ? 'Articles' : 'Pages'}
        </h1>
        <Link to={`/${type}/new`} className="btn btn-primary">
          + Create New
        </Link>
      </div>

      <div className="flex flex-wrap gap-4">
        <div className="flex-1 min-w-[200px]">
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={`Search ${type}...`}
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
      </div>

      <div className="card">
        <div className="card-body p-0">
          {items.length === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              No {type} found
            </div>
          ) : (
            <div className="table-container">
              <table className="table">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Author</th>
                    <th>Updated</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((item) => (
                    <tr key={item.id}>
                      <td className="font-medium">{item.title}</td>
                      <td className="text-gray-500 dark:text-gray-400">{item.slug}</td>
                      <td>
                        <span className={getStatusBadge(item.status)}>{item.status}</span>
                      </td>
                      <td>{item.author || 'Unknown'}</td>
                      <td className="text-sm text-gray-500 dark:text-gray-400">
                        {new Date(item.updatedAt).toLocaleDateString()}
                      </td>
                      <td>
                        <div className="flex gap-2">
                          <Link
                            to={`/${type}/${item.slug}`}
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
                            onClick={() => handleDelete(item.slug)}
                            className="btn btn-danger text-xs px-3 py-1"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {meta.total_pages > 1 && (
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            {meta.total} záznamov · strana {meta.page} / {meta.total_pages}
          </p>
          <div className="flex gap-2">
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={page <= 1 || loading}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              Predchádzajúca
            </button>
            <button
              type="button"
              className="btn btn-secondary text-sm"
              disabled={page >= meta.total_pages || loading}
              onClick={() => setPage((p) => p + 1)}
            >
              Ďalšia
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default PagesManager;
