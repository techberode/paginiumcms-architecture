// frontend/src/components/frontend/ArticleComments.tsx
import React, { useEffect, useState } from 'react';
import { Comment, listPublicComments, submitComment } from '../../api/comments';
import { useToast } from '../../hooks/useToast';

interface ArticleCommentsProps {
  articleSlug: string;
}

export const ArticleComments: React.FC<ArticleCommentsProps> = ({ articleSlug }) => {
  const toast = useToast();
  const [comments, setComments] = useState<Comment[]>([]);
  const [author, setAuthor] = useState('');
  const [email, setEmail] = useState('');
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    setLoading(true);
    void listPublicComments(articleSlug)
      .then(setComments)
      .finally(() => setLoading(false));
  }, [articleSlug]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    const result = await submitComment({ articleSlug, author, email, content });
    setSubmitting(false);
    if (result.ok) {
      toast.success('Comment submitted for review.');
      setContent('');
      if (result.comment.status === 'approved') {
        setComments((prev) => [result.comment, ...prev]);
      }
    } else {
      toast.error(result.error);
    }
  };

  return (
    <section className="mt-12 space-y-6">
      <h3 className="text-2xl font-bold text-slate-900 dark:text-white">Comments</h3>

      {loading ? (
        <p className="text-sm text-slate-500">Loading comments…</p>
      ) : comments.length === 0 ? (
        <p className="text-sm text-slate-500">No comments yet. Be the first!</p>
      ) : (
        <div className="space-y-4">
          {comments.map((c) => (
            <div key={c.id} className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900">
              <p className="font-semibold text-sm">{c.author}</p>
              <p className="text-xs text-slate-500 mb-2">{new Date(c.createdAt).toLocaleString()}</p>
              <p className="text-sm text-slate-700 dark:text-slate-300">{c.content}</p>
            </div>
          ))}
        </div>
      )}

      <form onSubmit={(e) => void handleSubmit(e)} className="rounded-2xl border border-slate-200 dark:border-slate-800 p-6 bg-white dark:bg-slate-900 space-y-3">
        <h4 className="font-bold">Leave a comment</h4>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input className="form-input" required placeholder="Name" value={author} onChange={(e) => setAuthor(e.target.value)} />
          <input className="form-input" type="email" placeholder="Email (optional)" value={email} onChange={(e) => setEmail(e.target.value)} />
        </div>
        <textarea className="form-input min-h-[100px]" required minLength={3} placeholder="Comment…" value={content} onChange={(e) => setContent(e.target.value)} />
        <button type="submit" className="btn btn-primary" disabled={submitting}>
          {submitting ? 'Sending…' : 'Post comment'}
        </button>
      </form>
    </section>
  );
};

export default ArticleComments;
