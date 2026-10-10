import React, { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  discardDraft,
  listPendingContentDrafts,
  type ContentType,
  type PendingContentDraft,
} from '../../api/drafts';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useDeskInbox } from '../../hooks/useDeskInbox';
import { editorPathForDraft } from '../../utils/contentUnsavedDraft';

interface UnsavedContentDraftBannerProps {
  type: ContentType;
}

export const UnsavedContentDraftBanner: React.FC<UnsavedContentDraftBannerProps> = ({ type }) => {
  const { t } = useI18n();
  const toast = useToast();
  const navigate = useNavigate();
  const { refresh: refreshDesk } = useDeskInbox();
  const [items, setItems] = useState<PendingContentDraft[]>([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const list = await listPendingContentDrafts(type);
    setItems(list);
    setLoading(false);
  }, [type]);

  useEffect(() => {
    void reload();
  }, [reload]);

  const dismiss = async (item: PendingContentDraft) => {
    const ok = await discardDraft(item.type, item.slug);
    if (!ok) {
      toast.error(t('editor.unsavedNewBanner.deleteFailed'));
      return;
    }
    toast.info(t('editor.unsavedNewBanner.deleted'));
    void reload();
    void refreshDesk();
  };

  if (loading || items.length === 0) {
    return null;
  }

  const labelKey = type === 'article' ? 'editor.unsavedNewBanner.article' : 'editor.unsavedNewBanner.page';

  return (
    <div
      className="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100"
      data-testid="unsaved-content-draft-banner"
    >
      <p className="font-semibold">{t(labelKey)}</p>
      <ul className="mt-2 space-y-2">
        {items.map((item) => (
          <li
            key={`${item.type}-${item.slug}`}
            className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
          >
            <span className="truncate">{item.title || item.slug}</span>
            <span className="flex shrink-0 gap-2">
              <button
                type="button"
                className="rounded bg-amber-600 px-3 py-1 text-white hover:bg-amber-700"
                onClick={() => navigate(editorPathForDraft(item.type, item.slug))}
              >
                {t('editor.unsavedNewBanner.continue')}
              </button>
              <button
                type="button"
                className="rounded px-3 py-1 hover:bg-amber-100 dark:hover:bg-amber-900/50"
                onClick={() => void dismiss(item)}
              >
                {t('editor.unsavedNewBanner.delete')}
              </button>
            </span>
          </li>
        ))}
      </ul>
      <p className="mt-2 text-xs opacity-80">
        <Link to={type === 'article' ? '/articles/new' : '/pages/new'} className="underline">
          {t('editor.unsavedNewBanner.createNew')}
        </Link>
      </p>
    </div>
  );
};
