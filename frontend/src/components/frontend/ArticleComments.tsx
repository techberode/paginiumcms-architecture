// frontend/src/components/frontend/ArticleComments.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Comment, listPublicComments, replyToComment, submitComment } from '../../api/comments';
import { authApi } from '../../api/auth';
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

const CommentCard: React.FC<{
  comment: Comment;
  locale: string;
  canReply: boolean;
  onReplied: (parentId: string, reply: Comment) => void;
}> = ({ comment, locale, canReply, onReplied }) => {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);
  const [body, setBody] = useState('');
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const send = async () => {
    if (body.trim().length < 2) {
      return;
    }
    setBusy(true);
    const result = await replyToComment(comment.id, body.trim());
    setBusy(false);
    if (!result.ok) {
      toast.error(result.error);
      return;
    }
    setBody('');
    setOpen(false);
    onReplied(comment.id, result.comment);
  };

  return (
    <div id={`comment-${comment.id}`} className={`${PUBLIC_CARD} p-4 scroll-mt-24`}>
      <p className="font-semibold text-sm text-theme-text">{comment.author}</p>
      <p className="text-xs text-theme-text-muted mb-2">{formatDisplayDateTime(comment.createdAt, locale)}</p>
      <p className="text-sm text-theme-text">{comment.content}</p>
      {(comment.replies ?? []).map((reply) => (
        <div
          key={reply.id}
          id={`comment-${reply.id}`}
          className={`mt-3 ml-4 rounded-xl px-3 py-2 text-sm ${
            reply.staffReply ? 'bg-theme-primary/10 border border-theme-primary/20' : 'bg-theme-canvas'
          }`}
        >
          <p className="font-semibold text-xs">
            {reply.author}
            {reply.staffReply ? <span className="ml-2 uppercase tracking-wide">{t('public.comments.staffReply')}</span> : null}
          </p>
          <p className="mt-1">{reply.content}</p>
        </div>
      ))}
      {canReply ? (
        <div className="mt-3">
          {open ? (
            <div className="space-y-2">
              <textarea
                className={`${inputClassName} min-h-[72px]`}
                value={body}
                onChange={(event) => setBody(event.target.value)}
                placeholder={t('public.comments.replyPlaceholder')}
              />
              <button type="button" className={`${BTN_PRIMARY} px-4 py-1.5 text-sm`} disabled={busy} onClick={() => void send()}>
                {t('public.comments.reply')}
              </button>
            </div>
          ) : (
            <button type="button" className="text-sm font-semibold text-theme-primary" onClick={() => setOpen(true)}>
              {t('public.comments.reply')}
            </button>
          )}
        </div>
      ) : null}
    </div>
  );
};

export const ArticleComments: React.FC<ArticleCommentsProps> = ({
  articleSlug,
  enabled = true,
  allowGuests = true,
  requireApproval = true,
}) => {
  const { t, locale } = useI18n();
  const toast = useToast();
  const { user } = useAuth();
  const location = useLocation();
  const [comments, setComments] = useState<Comment[]>([]);
  const [author, setAuthor] = useState('');
  const [email, setEmail] = useState('');
  const [content, setContent] = useState('');
  const [honeypot, setHoneypot] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [canReply, setCanReply] = useState(false);

  const canSubmit = useMemo(() => enabled && (allowGuests || Boolean(user)), [allowGuests, enabled, user]);

  useEffect(() => {
    if (!user) {
      return;
    }
    setAuthor((current) => (current === '' ? user.name || user.username || '' : current));
    setEmail((current) => (current === '' ? user.email || '' : current));
  }, [user]);

  const load = useCallback(() => {
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

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (!user) {
      setCanReply(false);
      return;
    }
    void authApi.chatStatus().then((res) => {
      setCanReply(Boolean(res.success && res.data?.canReplyComments));
    });
  }, [user]);

  useEffect(() => {
    const onDesk = (event: Event) => {
      const slug = (event as CustomEvent<{ articleSlug?: string }>).detail?.articleSlug;
      if (!slug || slug === articleSlug) {
        load();
      }
    };
    window.addEventListener('paginium:desk-comment', onDesk);
    return () => window.removeEventListener('paginium:desk-comment', onDesk);
  }, [articleSlug, load]);

  useEffect(() => {
    const hash = location.hash.replace('#', '');
    if (!hash.startsWith('comment-') || loading) {
      return;
    }
    const node = document.getElementById(hash);
    node?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, [location.hash, loading, comments]);

  if (!enabled) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canSubmit) {
      toast.error(t('public.comments.toast.guestsDisabled'));
      return;
    }
    if (email.trim() === '') {
      toast.error(t('public.comments.toast.emailRequired'));
      return;
    }

    setSubmitting(true);
    const result = await submitComment({ articleSlug, author, email, content, _hp: honeypot });
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
            <CommentCard
              key={c.id}
              comment={c}
              locale={locale}
              canReply={canReply}
              onReplied={(parentId, reply) =>
                setComments((current) =>
                  current.map((item) =>
                    item.id === parentId ? { ...item, replies: [...(item.replies ?? []), reply] } : item
                  )
                )
              }
            />
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
              required
              placeholder={t('public.comments.form.email')}
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
          <input
            type="text"
            name="_hp"
            tabIndex={-1}
            autoComplete="off"
            className="hidden"
            aria-hidden="true"
            value={honeypot}
            onChange={(e) => setHoneypot(e.target.value)}
          />
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
