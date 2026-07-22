// frontend/src/components/frontend/ArticleComments.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Comment, listPublicComments, submitComment } from '../../api/comments';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';

interface ArticleCommentsProps {
  articleSlug: string;
  enabled?: boolean;
  allowGuests?: boolean;
  requireApproval?: boolean;
}

export const ArticleComments: React.FC<ArticleCommentsProps> = ({
  articleSlug,
  enabled = true,
  allowGuests = true,
  requireApproval = true,
}) => {
  const { t, locale } = useI18n();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';
  const toast = useToast();
  const { user } = useAuth();
  const [comments, setComments] = useState<Comment[]>([]);
  const [author, setAuthor] = useState('');
  const [email, setEmail] = useState('');
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const canSubmit = useMemo(() => enabled && (allowGuests || Boolean(user)), [allowGuests, enabled, user]);

  useEffect(() => {
    if (!enabled) {
      setComments([]);
      setLoading(false);
      return;
    }

    setLoading(true);
    void listPublicComments(articleSlug)
      .then(setComments)
      .finally(() => setLoading(false));
  }, [articleSlug, enabled]);

  if (!enabled) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canSubmit) {
      toast.error(t('public.comments.toast.guestsDisabled'));
      return;
    }

    setSubmitting(true);
    const result = await submitComment({ articleSlug, author, email, content });
    setSubmitting(false);
    if (result.ok) {
      toast.success(
        requireApproval ? t('public.comments.toast.pendingApproval') : t('public.comments.toast.published')
      );
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
      <h3 className="text-2xl font-bold text-slate-900 dark:text-white">{t('public.comments.title')}</h3>

      {loading ? (
        <p className="text-sm text-slate-500">{t('public.comments.loading')}</p>
      ) : comments.length === 0 ? (
        <p className="text-sm text-slate-500">{t('public.comments.empty')}</p>
      ) : (
        <div className="space-y-4">
          {comments.map((c) => (
            <div
              key={c.id}
              className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900"
            >
              <p className="font-semibold text-sm">{c.author}</p>
              <p className="text-xs text-slate-500 mb-2">{new Date(c.createdAt).toLocaleString(dateLocale)}</p>
              <p className="text-sm text-slate-700 dark:text-slate-300">{c.content}</p>
            </div>
          ))}
        </div>
      )}

      {canSubmit ? (
        <form
          onSubmit={(e) => void handleSubmit(e)}
          className="rounded-2xl border border-slate-200 dark:border-slate-800 p-6 bg-white dark:bg-slate-900 space-y-3"
        >
          <h4 className="font-bold">{t('public.comments.form.title')}</h4>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input
              className="form-input"
              required
              placeholder={t('public.comments.form.name')}
              value={author}
              onChange={(e) => setAuthor(e.target.value)}
            />
            <input
              className="form-input"
              type="email"
              placeholder={t('public.comments.form.emailOptional')}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>
          <textarea
            className="form-input min-h-[100px]"
            required
            minLength={3}
            placeholder={t('public.comments.form.contentPlaceholder')}
            value={content}
            onChange={(e) => setContent(e.target.value)}
          />
          <button type="submit" className="btn btn-primary" disabled={submitting}>
            {submitting ? t('public.comments.form.submitting') : t('public.comments.form.submit')}
          </button>
        </form>
      ) : (
        <p className="text-sm text-slate-500">
          {t('public.comments.loginRequired')}{' '}
          <Link to="/login" className="text-indigo-600 hover:underline">
            {t('public.auth.common.signIn')}
          </Link>
        </p>
      )}
    </section>
  );
};

export default ArticleComments;
