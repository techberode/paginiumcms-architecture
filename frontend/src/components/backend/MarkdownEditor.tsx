// frontend/src/components/backend/MarkdownEditor.tsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';

interface MarkdownEditorProps {
  type?: 'page' | 'article';
}

export const MarkdownEditor: React.FC<MarkdownEditorProps> = ({ type = 'page' }) => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [status, setStatus] = useState<'draft' | 'published' | 'archived'>('draft');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const { get, post, put } = useApi();
  const toast = useToast();
  const isNew = slug === 'new' || !slug;

  const endpoint = type === 'article' ? '/api/articles' : '/api/pages';

  useEffect(() => {
    if (!isNew && slug) {
      loadContent();
    }
  }, [slug]);

  const loadContent = async () => {
    setLoading(true);
    try {
      const response = await get<any>(`${endpoint}/${slug}`);
      if (response.success && response.data) {
        setTitle(response.data.title || '');
        setContent(response.data.content || '');
        setStatus(response.data.status || 'draft');
      }
    } catch (error) {
      toast.error('Failed to load content');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    if (!title.trim()) {
      toast.warning('Please enter a title');
      return;
    }

    setSaving(true);
    try {
      const data = {
        title: title.trim(),
        content: content,
        status: status,
        slug: isNew ? title.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-') : slug,
      };

      let response;
      if (isNew) {
        response = await post(endpoint, data);
      } else {
        response = await put(`${endpoint}/${slug}`, data);
      }

      if (response.success) {
        toast.success(`${type} saved successfully`);
        if (isNew && response.data?.slug) {
          navigate(`/${type}s/${response.data.slug}`);
        }
      } else {
        toast.error(response.error || `Failed to save ${type}`);
      }
    } catch (error) {
      toast.error(`Failed to save ${type}`);
      console.error(error);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {isNew ? `Create New ${type}` : `Edit ${type}`}
        </h1>
        <button
          onClick={handleSave}
          disabled={saving}
          className="btn btn-primary"
        >
          {saving ? 'Saving...' : 'Save'}
        </button>
      </div>

      <div className="card">
        <div className="card-body space-y-4">
          <div className="form-group">
            <label className="form-label">Title</label>
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="form-input"
              placeholder="Enter title..."
            />
          </div>

          <div className="form-group">
            <label className="form-label">Status</label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as any)}
              className="form-input"
            >
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="archived">Archived</option>
            </select>
          </div>

          <div className="form-group">
            <label className="form-label">Content (Markdown)</label>
            <textarea
              value={content}
              onChange={(e) => setContent(e.target.value)}
              className="form-input min-h-[400px] font-mono text-sm"
              placeholder="Write your content in Markdown..."
            />
          </div>

          {!isNew && (
            <div className="text-sm text-gray-500 dark:text-gray-400">
              Slug: /{type}s/{slug}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MarkdownEditor;
