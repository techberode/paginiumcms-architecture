// frontend/src/components/frontend/ArticleComments.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Comment, listPublicComments, submitComment } from '../../api/comments';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { formatDisplayDateTime } from '../../utils/contentDates';
import { BTN_PRIMARY, INPUT_THEME, PUBLIC_CARD } from '../../theme/publicUiClasses';

interface ArticleCommentsProps {
  articleSlug: string;
  enabled?: boolean;
  allowGuests?: boolean;
  requireApproval?: boolean;
}

const inputClassName = `w-full px-3 py-2 rounded-lg ${INPUT_THEME}`;

export const ArticleComments: React.FC<ArticleCommentsProps> = ({
  articleSlug,
  enabled = true,
  allowGuests = true,
  requireApproval = true,
}) => {
  const { t, locale } = useI18n();
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
      <h3 className="text-2xl font-bold text-theme-text">{t('public.comments.title')}</h3>

      {loading ? (
        <p className="text-sm text-theme-text-muted">{t('public.comments.loading')}</p>
      ) : comments.length === 0 ? (
        <p className="text-sm text-theme-text-muted">{t('public.comments.empty')}</p>
      ) : (
        <div className="space-y-4">
          {comments.map((c) => (
            <div key={c.id} className={`${PUBLIC_CARD} p-4`}>
              <p className="font-semibold text-sm text-theme-text">{c.author}</p>
              <p className="text-xs text-theme-text-muted mb-2">{formatDisplayDateTime(c.createdAt, locale)}</p>
              <p className="text-sm text-theme-text">{c.content}</p>
            </div>
          ))}
        </div>
      )}

      {canSubmit ? (
        <form onSubmit={(e) => void handleSubmit(e)} className={`${PUBLIC_CARD} p-6 space-y-3`}>
          <h4 className="font-bold text-theme-text">{t('public.comments.form.title')}</h4>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input
              className={inputClassName}
              required
              placeholder={t('public.comments.form.name')}
              value={author}
              onChange={(e) => setAuthor(e.target.value)}
            />
            <input
              className={inputClassName}
              type="email"
              placeholder={t('public.comments.form.emailOptional')}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>
          <textarea
            className={`${inputClassName} min-h-[100px]`}
            required
            minLength={3}
            placeholder={t('public.comments.form.contentPlaceholder')}
            value={content}
            onChange={(e) => setContent(e.target.value)}
          />
          <button type="submit" className={`${BTN_PRIMARY} px-6 py-2.5`} disabled={submitting}>
            {submitting ? t('public.comments.form.submitting') : t('public.comments.form.submit')}
          </button>
        </form>
      ) : (
        <p className="text-sm text-theme-text-muted">
          {t('public.comments.loginRequired')}{' '}
          <Link to="/login" className="text-theme-primary hover:underline">
            {t('public.auth.common.signIn')}
          </Link>
        </p>
      )}
    </section>
  );
};

export default ArticleComments;
