import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { claimComment, replyToComment, type Comment } from '../../api/comments';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

export const CommentMessengerThread: React.FC<{
  comment: Comment;
  replies: Comment[];
  composerEnabled: boolean;
  canReply: boolean;
  onApprove?: () => void;
  onUpdated: () => void;
}> = ({ comment, replies, composerEnabled, canReply, onApprove, onUpdated }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [body, setBody] = useState('');
  const [busy, setBusy] = useState(false);
  const approved = comment.status === 'approved';
  const showComposer = composerEnabled && canReply && approved;

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
    window.dispatchEvent(new CustomEvent('paginium:desk-comment', { detail: { articleSlug: comment.articleSlug } }));
    onUpdated();
  };

  const claim = async () => {
    setBusy(true);
    const next = await claimComment(comment.id);
    setBusy(false);
    if (next) {
      onUpdated();
    }
  };

  const rows: Comment[] = [comment, ...replies];

  return (
    <div className="rounded-xl border border-admin-border bg-admin-canvas p-3 space-y-3" data-testid={`comment-thread-${comment.id}`}>
      <div className="max-h-80 overflow-y-auto space-y-2">
        {rows.map((row) => {
          const staff = Boolean(row.staffReply || (row.parentId && row.authorUserId));
          return (
            <div key={row.id} className={`flex ${staff ? 'justify-end' : 'justify-start'}`}>
              <div
                className={`max-w-[80%] rounded-2xl px-3 py-2 text-sm ${
                  staff
                    ? 'bg-admin-primary text-white rounded-br-md'
                    : 'bg-white dark:bg-gray-800 border border-admin-border rounded-bl-md'
                }`}
              >
                <p className="text-[11px] opacity-70 mb-0.5">
                  {row.author || (staff ? t('comments.thread.staff') : comment.author)}
                </p>
                <p className="whitespace-pre-wrap">{row.content}</p>
              </div>
            </div>
          );
        })}
      </div>
      {comment.handleStatus === 'in_progress' && comment.claimedBy ? (
        <p className="text-xs text-admin-muted">{t('comments.thread.inProgress')}</p>
      ) : null}
      <div className="flex flex-wrap items-center gap-2">
        {onApprove && !approved ? (
          <button
            type="button"
            className="btn btn-primary text-xs"
            data-testid={`comment-thread-approve-${comment.id}`}
            disabled={busy}
            onClick={onApprove}
          >
            {t('comments.actions.approve')}
          </button>
        ) : null}
        {canReply && !comment.claimedBy ? (
          <button type="button" className="btn btn-secondary text-xs" disabled={busy} onClick={() => void claim()}>
            {t('comments.thread.claim')}
          </button>
        ) : null}
        <Link
          to={`/blog/${encodeURIComponent(comment.articleSlug)}#comment-${encodeURIComponent(comment.id)}`}
          className="text-xs underline text-admin-muted"
        >
          {t('comments.thread.viewArticle')}
        </Link>
      </div>
      {showComposer ? (
        <div className="flex gap-2">
          <textarea
            className="flex-1 rounded-lg border border-admin-border bg-admin-card px-3 py-2 text-sm"
            rows={2}
            placeholder={t('comments.thread.placeholder')}
            value={body}
            onChange={(event) => setBody(event.target.value)}
            data-testid={`comment-thread-input-${comment.id}`}
          />
          <button
            type="button"
            className="btn btn-primary self-end text-sm"
            disabled={busy}
            onClick={() => void send()}
          >
            {t('comments.thread.send')}
          </button>
        </div>
      ) : null}
      {!approved && canReply ? (
        <p className="text-xs text-admin-muted">{t('comments.thread.needsApproval')}</p>
      ) : null}
    </div>
  );
};
