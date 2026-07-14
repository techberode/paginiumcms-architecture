// frontend/src/components/backend/PagesManager.tsx
import React, { useState, useEffect } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { Link } from 'react-router-dom';

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

export const PagesManager: React.FC<PagesManagerProps> = ({ type = 'pages' }) => {
  const [items, setItems] = useState<Page[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const { get, del } = useApi();
  const toast = useToast();

  const endpoint = type === 'articles' ? '/api/articles' : '/api/pages';

  useEffect(() => {
    loadItems();
  }, [type]);

  const loadItems = async () => {
    setLoading(true);
    try {
      const response = await get<Page[]>(endpoint);
      if (response.success) {
        setItems(response.data || []);
      }
    } catch (error) {
      toast.error(`Failed to load ${type}`);
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string, slug: string) => {
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

  const filteredItems = items.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(search.toLowerCase()) ||
                          item.slug.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || item.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  if (loading) {
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
        <Link
          to={`/${type}/new`}
          className="btn btn-primary"
        >
          + Create New
        </Link>
      </div>

      {/* Filters */}
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

      {/* Table */}
      <div className="card">
        <div className="card-body p-0">
          {filteredItems.length === 0 ? (
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
                  {filteredItems.map((item) => (
                    <tr key={item.id}>
                      <td className="font-medium">{item.title}</td>
                      <td className="text-gray-500 dark:text-gray-400">{item.slug}</td>
                      <td>
                        <span className={getStatusBadge(item.status)}>
                          {item.status}
                        </span>
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
                            onClick={() => handleDelete(item.id, item.slug)}
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
    </div>
  );
};

export default PagesManager;
