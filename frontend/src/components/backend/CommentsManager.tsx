// frontend/src/components/backend/CommentsManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { MessageSquare, Check, X, Trash2 } from 'lucide-react';
import {
  Comment,
  CommentStatus,
  deleteComment,
  listAdminComments,
  updateCommentStatus,
} from '../../api/comments';
import { useToast } from '../../hooks/useToast';

export const CommentsManager: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<Comment[]>([]);
  const [filter, setFilter] = useState<CommentStatus | 'all'>('all');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const comments = await listAdminComments(
        filter === 'all' ? undefined : { status: filter }
      );
      setItems(comments);
    } catch {
      showError('Failed to load comments.');
    } finally {
      setLoading(false);
    }
  }, [filter]);

  useEffect(() => {
    void load();
  }, [load]);

  const setStatus = async (id: string, status: CommentStatus) => {
    const ok = await updateCommentStatus(id, status);
    if (ok) {
      showSuccess('Comment updated.');
      await load();
    } else {
      showError('Update failed.');
    }
  };

  const remove = async (id: string) => {
    if (!confirm('Delete this comment?')) return;
    const ok = await deleteComment(id);
    if (ok) {
      showSuccess('Comment deleted.');
      await load();
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <MessageSquare className="w-6 h-6 text-indigo-500" />
          Comments ({items.length})
        </h1>
        <select className="form-input w-auto" value={filter} onChange={(e) => setFilter(e.target.value as typeof filter)}>
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
        </div>
      ) : items.length === 0 ? (
        <div className="card card-body text-center text-gray-500 py-12">No comments.</div>
      ) : (
        <div className="space-y-4">
          {items.map((comment) => (
            <div key={comment.id} className="card">
              <div className="card-body space-y-2">
                <div className="flex justify-between gap-4 flex-wrap">
                  <div>
                    <p className="font-medium">{comment.author}</p>
                    <p className="text-xs text-gray-500">
                      {comment.articleSlug} · {new Date(comment.createdAt).toLocaleString()}
                    </p>
                  </div>
                  <span className="badge badge-info">{comment.status}</span>
                </div>
                <p className="text-sm text-gray-700 dark:text-gray-300">{comment.content}</p>
                <div className="flex gap-2">
                  {comment.status !== 'approved' && (
                    <button type="button" className="btn btn-primary text-xs px-2 py-1" onClick={() => void setStatus(comment.id, 'approved')}>
                      <Check className="w-3 h-3" />
                    </button>
                  )}
                  {comment.status !== 'rejected' && (
                    <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => void setStatus(comment.id, 'rejected')}>
                      <X className="w-3 h-3" />
                    </button>
                  )}
                  <button type="button" className="btn btn-danger text-xs px-2 py-1 ml-auto" onClick={() => void remove(comment.id)}>
                    <Trash2 className="w-3 h-3" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default CommentsManager;
