import React, { useCallback, useEffect, useState } from 'react';
import { comingSoonApi, type ComingSoonItem, type ComingSoonKind } from '../../api/comingSoon';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { datetimeLocalToUnix, unixToDatetimeLocal, nextHourDatetimeLocal } from '../../utils/siteEvents';

export const ComingSoonPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [items, setItems] = useState<ComingSoonItem[]>([]);
  const [pages, setPages] = useState<Array<{ slug: string; title: string }>>([]);
  const [articles, setArticles] = useState<Array<{ slug: string; title: string }>>([]);
  const [kind, setKind] = useState<ComingSoonKind>('page');
  const [slug, setSlug] = useState('');
  const [title, setTitle] = useState('');
  const [subtitle, setSubtitle] = useState('');
  const [publishAt, setPublishAt] = useState(nextHourDatetimeLocal);
  const [embedOnPage, setEmbedOnPage] = useState(true);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    const [list, catalog] = await Promise.all([comingSoonApi.list(), comingSoonApi.targets()]);
    setItems(list);
    setPages(catalog.pages);
    setArticles(catalog.articles);
    setSlug((current) => {
      if (current !== '') {
        return current;
      }
      return catalog.pages[0]?.slug ?? catalog.articles[0]?.slug ?? '';
    });
  }, []);

  useEffect(() => {
    void load().catch(() => toast.error(t('platform.comingSoon.toast.loadFailed')));
  }, [load, t, toast]);

  const catalog = kind === 'article' ? articles : pages;

  const handleCreate = async () => {
    const unix = datetimeLocalToUnix(publishAt);
    if (!slug || unix === null) {
      toast.error(t('platform.comingSoon.toast.targetRequired'));
      return;
    }
    setBusy(true);
    try {
      const response = await comingSoonApi.create({
        contentKind: kind,
        slug,
        title: title.trim() || slug,
        subtitle: subtitle.trim(),
        publishAt: unix,
        enabled: true,
        embedOnPage,
      });
      if (!response.success) {
        toast.error(response.error || response.message || t('platform.comingSoon.toast.saveFailed'));
        return;
      }
      toast.success(t('platform.comingSoon.toast.saved'));
      setSubtitle('');
      await load();
    } finally {
      setBusy(false);
    }
  };

  const handleToggle = async (item: ComingSoonItem) => {
    const response = await comingSoonApi.update(item.id, { enabled: !item.enabled });
    if (!response.success) {
      toast.error(t('platform.comingSoon.toast.saveFailed'));
      return;
    }
    await load();
  };

  const handleDelete = async (item: ComingSoonItem) => {
    if (!window.confirm(t('platform.comingSoon.confirmDelete'))) {
      return;
    }
    const ok = await comingSoonApi.remove(item.id);
    if (!ok) {
      toast.error(t('platform.comingSoon.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.comingSoon.toast.deleted'));
    await load();
  };

  return (
    <div className="grid gap-6 lg:grid-cols-2" data-testid="coming-soon-panel">
      <AdminWidgetCard title={t('platform.comingSoon.formTitle')}>
        <div className="space-y-3">
          <p className="text-sm text-admin-muted">{t('platform.comingSoon.hint')}</p>
          <label className="block space-y-1 text-sm">
            <span className="text-admin-muted">{t('platform.comingSoon.kind')}</span>
            <select
              className={ADMIN_INPUT}
              value={kind}
              onChange={(event) => {
                const next = event.target.value as ComingSoonKind;
                setKind(next);
                const first = next === 'article' ? articles[0] : pages[0];
                setSlug(first?.slug ?? '');
              }}
            >
              <option value="page">{t('platform.comingSoon.kinds.page')}</option>
              <option value="article">{t('platform.comingSoon.kinds.article')}</option>
            </select>
          </label>
          <label className="block space-y-1 text-sm">
            <span className="text-admin-muted">{t('platform.comingSoon.target')}</span>
            <select className={ADMIN_INPUT} value={slug} onChange={(event) => setSlug(event.target.value)}>
              {catalog.length === 0 ? (
                <option value="">{t('platform.comingSoon.noTargets')}</option>
              ) : null}
              {catalog.map((item) => (
                <option key={item.slug} value={item.slug}>
                  {item.title}
                </option>
              ))}
            </select>
          </label>
          <label className="block space-y-1 text-sm">
            <span className="text-admin-muted">{t('platform.comingSoon.title')}</span>
            <input className={ADMIN_INPUT} value={title} onChange={(event) => setTitle(event.target.value)} />
          </label>
          <label className="block space-y-1 text-sm">
            <span className="text-admin-muted">{t('platform.comingSoon.subtitle')}</span>
            <input className={ADMIN_INPUT} value={subtitle} onChange={(event) => setSubtitle(event.target.value)} />
          </label>
          <label className="block space-y-1 text-sm">
            <span className="text-admin-muted">{t('platform.comingSoon.publishAt')}</span>
            <input
              type="datetime-local"
              className={ADMIN_INPUT}
              value={publishAt}
              onChange={(event) => setPublishAt(event.target.value)}
            />
          </label>
          <label className="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" checked={embedOnPage} onChange={(event) => setEmbedOnPage(event.target.checked)} />
            {t('platform.comingSoon.embedOnPage')}
          </label>
          <button type="button" className="btn btn-primary" disabled={busy} onClick={() => void handleCreate()}>
            {t('platform.comingSoon.save')}
          </button>
        </div>
      </AdminWidgetCard>
      <AdminWidgetCard title={t('platform.comingSoon.listTitle')}>
        {items.length === 0 ? (
          <p className="text-sm text-admin-muted">{t('platform.comingSoon.empty')}</p>
        ) : (
          <ul className="space-y-3">
            {items.map((item) => (
              <li key={item.id} className="rounded-lg border border-admin-border p-3 space-y-1">
                <p className="font-semibold text-sm">
                  {item.title}{' '}
                  <span className="text-admin-muted font-normal">
                    ({item.contentKind}/{item.slug})
                  </span>
                </p>
                <p className="text-xs text-admin-muted">{unixToDatetimeLocal(item.publishAt)}</p>
                <div className="flex gap-2 pt-1">
                  <button type="button" className="btn btn-secondary text-xs" onClick={() => void handleToggle(item)}>
                    {item.enabled ? t('platform.comingSoon.disable') : t('platform.comingSoon.enable')}
                  </button>
                  <button type="button" className="btn btn-danger text-xs" onClick={() => void handleDelete(item)}>
                    {t('platform.comingSoon.delete')}
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </AdminWidgetCard>
    </div>
  );
};

export default ComingSoonPanel;
