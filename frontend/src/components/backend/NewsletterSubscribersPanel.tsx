import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Download, Mail, RefreshCw } from 'lucide-react';
import {
  exportNewsletterSubscribersCsv,
  listNewsletterSubscribers,
  type NewsletterSubscriber,
} from '../../api/newsletter';
import { useToast } from '../../hooks/useToast';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { AdminListSortBar } from './SortableTableHeader';
import { applyClientListView } from '../../utils/clientListView';
import { useI18n } from '../../context/I18nContext';

const sourceLabelKey = (source: string): string => `newsletter.source.${source}`;

export const NewsletterSubscribersPanel: React.FC = () => {
  const { t, locale } = useI18n();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<NewsletterSubscriber[]>([]);
  const [bySource, setBySource] = useState<Record<string, number>>({});
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('newsletter');
  const { sortField, sortDirection, handleSort } = useColumnSort('subscribedAt', 'desc');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listNewsletterSubscribers();
      setItems(data.items);
      setBySource(data.bySource);
    } catch {
      showError(t('newsletter.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [search, sortField, sortDirection, pageSize]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (row) => `${row.email} ${row.source}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'email', label: t('newsletter.table.email'), getValue: (row) => row.email },
          { value: 'source', label: t('newsletter.table.source'), getValue: (row) => row.source },
          {
            value: 'subscribedAt',
            label: t('newsletter.table.date'),
            getValue: (row) => row.subscribedAt,
          },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField, t]
  );

  const handleExport = async () => {
    setExporting(true);
    try {
      const blob = await exportNewsletterSubscribersCsv();
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `newsletter_subscribers_${new Date().toISOString().slice(0, 10)}.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
      showSuccess(t('newsletter.toast.exported'));
    } catch {
      showError(t('newsletter.toast.exportFailed'));
    } finally {
      setExporting(false);
    }
  };

  const formatSource = (source: string): string => {
    const key = sourceLabelKey(source);
    const translated = t(key);
    return translated !== key ? translated : source;
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-theme-text flex items-center gap-2">
            <Mail className="h-7 w-7 text-theme-primary" />
            {t('newsletter.page.title')}
          </h1>
          <p className="text-sm text-theme-text-muted mt-1">{t('newsletter.page.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void load()}
            disabled={loading}
            className="inline-flex items-center gap-2 rounded-xl border border-theme-border bg-theme-surface px-4 py-2 text-sm font-semibold text-theme-text hover:bg-theme-surface-elevated disabled:opacity-60"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            {t('newsletter.actions.refresh')}
          </button>
          <button
            type="button"
            onClick={() => void handleExport()}
            disabled={exporting || items.length === 0}
            className="inline-flex items-center gap-2 rounded-xl bg-theme-primary px-4 py-2 text-sm font-semibold text-theme-primary-foreground hover:opacity-90 disabled:opacity-60"
          >
            <Download className="h-4 w-4" />
            {t('newsletter.actions.exportCsv')}
          </button>
        </div>
      </div>

      {Object.keys(bySource).length > 0 ? (
        <div className="flex flex-wrap gap-2">
          {Object.entries(bySource).map(([source, count]) => (
            <span
              key={source}
              className="inline-flex items-center gap-1 rounded-full border border-theme-border bg-theme-surface px-3 py-1 text-xs font-medium text-theme-text-muted"
            >
              {formatSource(source)}: <strong className="text-theme-text">{count}</strong>
            </span>
          ))}
        </div>
      ) : null}

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('newsletter.search.placeholder')}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
      />

      <AdminListSortBar
        columns={[
          { field: 'email', label: t('newsletter.table.email') },
          { field: 'source', label: t('newsletter.table.source') },
          { field: 'subscribedAt', label: t('newsletter.table.date') },
        ]}
        activeField={sortField}
        direction={sortDirection}
        onSort={handleSort}
      />

      <div className="overflow-hidden rounded-xl border border-theme-border bg-theme-surface">
        <table className="min-w-full divide-y divide-theme-border text-sm">
          <thead className="bg-theme-surface-elevated/60">
            <tr>
              <th className="px-4 py-3 text-left font-semibold text-theme-text-muted">{t('newsletter.table.email')}</th>
              <th className="px-4 py-3 text-left font-semibold text-theme-text-muted">{t('newsletter.table.source')}</th>
              <th className="px-4 py-3 text-left font-semibold text-theme-text-muted">{t('newsletter.table.date')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-theme-border">
            {loading ? (
              <tr>
                <td colSpan={3} className="px-4 py-8 text-center text-theme-text-muted">
                  {t('list.loading')}
                </td>
              </tr>
            ) : listView.items.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-8 text-center text-theme-text-muted">
                  {t('newsletter.empty')}
                </td>
              </tr>
            ) : (
              listView.items.map((row) => (
                <tr key={row.id} className="hover:bg-theme-surface-elevated/40">
                  <td className="px-4 py-3 font-medium text-theme-text">{row.email}</td>
                  <td className="px-4 py-3 text-theme-text-muted">{formatSource(row.source)}</td>
                  <td className="px-4 py-3 text-theme-text-muted">
                    {row.subscribedAt
                      ? new Date(row.subscribedAt).toLocaleString(dateLocale)
                      : '—'}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <AdminListPagination
        page={page}
        totalPages={listView.totalPages}
        total={listView.total}
        pageSize={pageSize}
        onPageChange={setPage}
      />
    </div>
  );
};

export default NewsletterSubscribersPanel;
