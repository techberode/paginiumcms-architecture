import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Download, Mail, RefreshCw, Send, Trash2, UserMinus } from 'lucide-react';
import {
  bulkDeleteNewsletterSubscribers,
  bulkUnsubscribeNewsletterSubscribers,
  deleteNewsletterSubscriber,
  exportNewsletterSubscribersCsv,
  fetchNewsletterSendStatus,
  listNewsletterSubscribers,
  sendNewsletterCmsRelease,
  sendNewsletterTestEmail,
  sendNewsletterWeeklyDigestNow,
  unsubscribeNewsletterSubscriber,
  type NewsletterPreferenceKey,
  type NewsletterSendStatus,
  type NewsletterSubscriber,
} from '../../api/newsletter';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { AdminListSortBar } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import { applyClientListView } from '../../utils/clientListView';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';
import { NewsletterSettingsPanel } from './NewsletterSettingsPanel';

const sourceLabelKey = (source: string): string => `newsletter.source.${source}`;

export const NewsletterSubscribersPanel: React.FC = () => {
  const { t, locale } = useI18n();
  const { user } = useAuth();
  const dateLocale = locale === 'en' ? 'en-US' : 'sk-SK';
  const { error: showError, success: showSuccess, warning: showWarning } = useToast();
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const [items, setItems] = useState<NewsletterSubscriber[]>([]);
  const [bySource, setBySource] = useState<Record<string, number>>({});
  const [sendStatus, setSendStatus] = useState<NewsletterSendStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [sendLoading, setSendLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [testEmail, setTestEmail] = useState('');
  const [releaseVersion, setReleaseVersion] = useState('');
  const [releaseTitle, setReleaseTitle] = useState('');
  const [releaseBody, setReleaseBody] = useState('');
  const [releaseUrl, setReleaseUrl] = useState('');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [page, setPage] = useState(1);
  const [bulkBusy, setBulkBusy] = useState(false);
  const [pageSize, setPageSize] = useAdminListPageSize('newsletter');
  const { sortField, sortDirection, handleSort } = useColumnSort('subscribedAt', 'desc');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [data, status] = await Promise.all([
        listNewsletterSubscribers(),
        fetchNewsletterSendStatus(),
      ]);
      setItems(data.items);
      setBySource(data.bySource);
      setSendStatus(status);
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
  }, [search, sortField, sortDirection, pageSize, statusFilter]);

  const filteredItems = useMemo(() => {
    if (statusFilter === 'all') {
      return items;
    }

    return items.filter((row) => (row.status ?? 'active') === statusFilter);
  }, [items, statusFilter]);

  const listView = useMemo(
    () =>
      applyClientListView(filteredItems, {
        search,
        searchText: (row) =>
          `${row.email} ${row.source} ${(row.preferences ?? []).join(' ')} ${row.status ?? ''}`,
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
          {
            value: 'preferences',
            label: t('newsletter.table.preferences'),
            getValue: (row) => (row.preferences ?? []).join(','),
          },
          {
            value: 'status',
            label: t('newsletter.table.status'),
            getValue: (row) => row.status ?? '',
          },
        ],
        page,
        pageSize,
      }),
    [filteredItems, page, pageSize, search, sortDirection, sortField, t]
  );

  const visibleIds = useMemo(() => listView.items.map((row) => row.id), [listView.items]);
  const bulkSelection = useBulkSelection(
    visibleIds,
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}:${statusFilter}`
  );

  const statusOptions = useMemo(
    () => [
      { value: 'all', label: t('newsletter.filter.statusAll') },
      { value: 'active', label: t('newsletter.status.active') },
      { value: 'pending', label: t('newsletter.status.pending') },
      { value: 'unsubscribed', label: t('newsletter.status.unsubscribed') },
    ],
    [t]
  );

  const handleBulk = async (action: 'unsubscribe' | 'delete') => {
    if (bulkSelection.count === 0) {
      return;
    }

    const confirmKey =
      action === 'delete' ? 'newsletter.confirm.bulkDelete' : 'newsletter.confirm.bulkUnsubscribe';
    if (!confirm(t(confirmKey, { count: String(bulkSelection.count) }))) {
      return;
    }

    setBulkBusy(true);
    try {
      const result =
        action === 'delete'
          ? await bulkDeleteNewsletterSubscribers(bulkSelection.selectedIds)
          : await bulkUnsubscribeNewsletterSubscribers(bulkSelection.selectedIds);

      if (!result) {
        showError(t('newsletter.toast.bulkFailed'));
        return;
      }

      const summary = summarizeBulkResult(result, t);
      if (result.failed > 0) {
        showWarning(summary);
      } else {
        showSuccess(
          action === 'delete'
            ? t('newsletter.toast.bulkDeleted', { count: String(result.succeeded) })
            : t('newsletter.toast.bulkUnsubscribed', { count: String(result.succeeded) })
        );
      }

      bulkSelection.clear();
      await load();
    } catch {
      showError(t('newsletter.toast.bulkFailed'));
    } finally {
      setBulkBusy(false);
    }
  };

  const handleUnsubscribeOne = async (id: string) => {
    if (!confirm(t('newsletter.confirm.unsubscribeOne'))) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await unsubscribeNewsletterSubscriber(id);
      if (result.ok) {
        showSuccess(result.message ?? t('newsletter.toast.unsubscribed'));
        bulkSelection.clear();
        await load();
      } else {
        showError(result.message ?? t('newsletter.toast.bulkFailed'));
      }
    } catch {
      showError(t('newsletter.toast.bulkFailed'));
    } finally {
      setBulkBusy(false);
    }
  };

  const handleDeleteOne = async (id: string) => {
    if (!confirm(t('newsletter.confirm.deleteOne'))) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await deleteNewsletterSubscriber(id);
      if (result.ok) {
        showSuccess(result.message ?? t('newsletter.toast.deleted'));
        bulkSelection.clear();
        await load();
      } else {
        showError(result.message ?? t('newsletter.toast.bulkFailed'));
      }
    } catch {
      showError(t('newsletter.toast.bulkFailed'));
    } finally {
      setBulkBusy(false);
    }
  };

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

  const formatBool = (value: boolean | undefined): string =>
    value ? t('newsletter.send.yes') : t('newsletter.send.no');

  const handleSendWeeklyDigest = async () => {
    setSendLoading(true);
    try {
      const result = await sendNewsletterWeeklyDigestNow();
      if (result.ok) {
        showSuccess(result.message ?? t('newsletter.toast.weeklyDigestSent'));
        await load();
      } else {
        showError(result.message ?? t('newsletter.toast.weeklyDigestFailed'));
      }
    } catch {
      showError(t('newsletter.toast.weeklyDigestFailed'));
    } finally {
      setSendLoading(false);
    }
  };

  const handleSendTest = async () => {
    const email = testEmail.trim();
    if (email === '') {
      return;
    }

    setSendLoading(true);
    try {
      const result = await sendNewsletterTestEmail(email);
      if (result.ok) {
        showSuccess(result.message ?? t('newsletter.toast.testSent'));
      } else {
        showError(result.message ?? t('newsletter.toast.testFailed'));
      }
    } catch {
      showError(t('newsletter.toast.testFailed'));
    } finally {
      setSendLoading(false);
    }
  };

  const handleSendCmsRelease = async () => {
    const title = releaseTitle.trim();
    const body = releaseBody.trim();
    if (title === '' || body === '') {
      showError(t('newsletter.release.validation'));
      return;
    }

    setSendLoading(true);
    try {
      const result = await sendNewsletterCmsRelease({
        version: releaseVersion.trim() || undefined,
        title,
        body,
        url: releaseUrl.trim() || undefined,
      });
      if (result.ok) {
        showSuccess(result.message ?? t('newsletter.toast.releaseSent'));
        await load();
      } else {
        showError(result.message ?? t('newsletter.toast.releaseFailed'));
      }
    } catch {
      showError(t('newsletter.toast.releaseFailed'));
    } finally {
      setSendLoading(false);
    }
  };

  const formatSource = (source: string): string => {
    const key = sourceLabelKey(source);
    const translated = t(key);
    return translated !== key ? translated : source;
  };

  const formatPreference = (key: NewsletterPreferenceKey): string => {
    const labelKey = `newsletter.preference.${key}`;
    const translated = t(labelKey);
    return translated !== labelKey ? translated : key;
  };

  const formatPreferences = (preferences?: NewsletterPreferenceKey[]): string => {
    if (!preferences || preferences.length === 0) {
      return '—';
    }
    return preferences.map((key) => formatPreference(key)).join(', ');
  };

  const formatStatus = (status?: string): string => {
    if (!status) {
      return '—';
    }
    const key = `newsletter.status.${status}`;
    const translated = t(key);
    return translated !== key ? translated : status;
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Mail className="h-7 w-7 text-indigo-600 dark:text-indigo-400" />
            {t('newsletter.page.title')}
          </h1>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">{t('newsletter.page.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => void load()}
            disabled={loading}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-60"
          >
            <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
            {t('newsletter.actions.refresh')}
          </button>
          <button
            type="button"
            onClick={() => void handleExport()}
            disabled={exporting || items.length === 0}
            className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60"
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
              className="inline-flex items-center gap-1 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-1 text-xs font-medium text-slate-500 dark:text-slate-400"
            >
              {formatSource(source)}: <strong className="text-slate-900 dark:text-white">{count}</strong>
            </span>
          ))}
        </div>
      ) : null}

      <NewsletterSettingsPanel onSaved={() => void load()} />

      {sendStatus ? (
        <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-4">
          <div>
            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">{t('newsletter.send.title')}</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">{t('newsletter.send.subtitle')}</p>
          </div>
          <dl className="grid gap-2 sm:grid-cols-2 text-sm">
            <div className="flex justify-between gap-4 sm:block">
              <dt className="text-slate-500 dark:text-slate-400">{t('newsletter.send.configured')}</dt>
              <dd className="font-medium text-slate-900 dark:text-white">{formatBool(sendStatus.configured)}</dd>
            </div>
            <div className="flex justify-between gap-4 sm:block sm:col-span-2">
              <dt className="text-slate-500 dark:text-slate-400">{t('newsletter.send.lastWeeklyDigestAt')}</dt>
              <dd className="font-medium text-slate-900 dark:text-white">
                {sendStatus.lastWeeklyDigestAt
                  ? new Date(sendStatus.lastWeeklyDigestAt).toLocaleString(dateLocale)
                  : t('newsletter.send.never')}
              </dd>
            </div>
          </dl>

          {isSuperAdmin ? (
            <div className="flex flex-col gap-4 pt-2 border-t border-slate-200 dark:border-slate-800">
              <button
                type="button"
                onClick={() => void handleSendWeeklyDigest()}
                disabled={sendLoading}
                className="inline-flex w-fit items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60"
              >
                <Send className="h-4 w-4" />
                {t('newsletter.actions.sendWeeklyDigest')}
              </button>

              <div className="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 p-4 space-y-3">
                <h3 className="text-sm font-semibold text-slate-900 dark:text-white">{t('newsletter.release.title')}</h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">{t('newsletter.release.subtitle')}</p>
                <div className="grid gap-3 sm:grid-cols-2">
                  <label className="flex flex-col gap-1 text-sm">
                    <span className="font-medium text-slate-900 dark:text-white">{t('newsletter.release.version')}</span>
                    <input
                      type="text"
                      value={releaseVersion}
                      onChange={(event) => setReleaseVersion(event.target.value)}
                      placeholder={t('newsletter.release.versionPlaceholder')}
                      className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white"
                    />
                  </label>
                  <label className="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span className="font-medium text-slate-900 dark:text-white">{t('newsletter.release.releaseTitle')}</span>
                    <input
                      type="text"
                      value={releaseTitle}
                      onChange={(event) => setReleaseTitle(event.target.value)}
                      placeholder={t('newsletter.release.titlePlaceholder')}
                      className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white"
                    />
                  </label>
                  <label className="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span className="font-medium text-slate-900 dark:text-white">{t('newsletter.release.body')}</span>
                    <textarea
                      rows={4}
                      value={releaseBody}
                      onChange={(event) => setReleaseBody(event.target.value)}
                      placeholder={t('newsletter.release.bodyPlaceholder')}
                      className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white"
                    />
                  </label>
                  <label className="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span className="font-medium text-slate-900 dark:text-white">{t('newsletter.release.url')}</span>
                    <input
                      type="url"
                      value={releaseUrl}
                      onChange={(event) => setReleaseUrl(event.target.value)}
                      placeholder={t('newsletter.release.urlPlaceholder')}
                      className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white"
                    />
                  </label>
                </div>
                <button
                  type="button"
                  onClick={() => void handleSendCmsRelease()}
                  disabled={sendLoading}
                  className="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-60"
                >
                  <Send className="h-4 w-4" />
                  {t('newsletter.actions.sendRelease')}
                </button>
              </div>

              <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                <label className="flex flex-col gap-1 text-sm flex-1 max-w-md">
                  <span className="font-medium text-slate-900 dark:text-white">{t('newsletter.send.testEmailLabel')}</span>
                  <input
                    type="email"
                    value={testEmail}
                    onChange={(event) => setTestEmail(event.target.value)}
                    placeholder={t('newsletter.send.testEmailPlaceholder')}
                    className="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-white"
                  />
                </label>
                <button
                  type="button"
                  onClick={() => void handleSendTest()}
                  disabled={sendLoading || testEmail.trim() === ''}
                  className="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-60"
                >
                  {t('newsletter.actions.sendTest')}
                </button>
              </div>
            </div>
          ) : (
            <p className="text-xs text-slate-500 dark:text-slate-400">{t('newsletter.send.superAdminHint')}</p>
          )}
        </div>
      ) : null}

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('newsletter.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'unsubscribe',
            label: t('newsletter.bulk.unsubscribe'),
            variant: 'secondary',
            disabled: bulkBusy,
            onClick: () => void handleBulk('unsubscribe'),
          },
          {
            id: 'delete',
            label: t('newsletter.bulk.delete'),
            variant: 'danger',
            disabled: bulkBusy,
            onClick: () => void handleBulk('delete'),
          },
        ]}
      />

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('newsletter.search.placeholder')}
        statusFilter={statusFilter}
        onStatusFilterChange={setStatusFilter}
        statusOptions={statusOptions}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
      />

      <AdminListSortBar
        columns={[
          { field: 'email', label: t('newsletter.table.email') },
          { field: 'source', label: t('newsletter.table.source') },
          { field: 'preferences', label: t('newsletter.table.preferences') },
          { field: 'status', label: t('newsletter.table.status') },
          { field: 'subscribedAt', label: t('newsletter.table.date') },
        ]}
        activeField={sortField}
        direction={sortDirection}
        onSort={handleSort}
      />

      <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
          <thead className="bg-slate-50 dark:bg-slate-800/80">
            <tr>
              <th className="px-4 py-3 w-10">
                <input
                  type="checkbox"
                  checked={bulkSelection.allSelected}
                  ref={(input) => {
                    if (input) {
                      input.indeterminate = bulkSelection.someSelected && !bulkSelection.allSelected;
                    }
                  }}
                  onChange={bulkSelection.toggleAll}
                  disabled={loading || listView.items.length === 0 || bulkBusy}
                  aria-label={t('list.inbox.selectAllOnPage')}
                  className="rounded border-slate-300 dark:border-slate-600"
                />
              </th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.email')}</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.source')}</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.preferences')}</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.status')}</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.date')}</th>
              <th className="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400">{t('newsletter.table.actions')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {loading ? (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                  {t('list.loading')}
                </td>
              </tr>
            ) : listView.items.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                  {t('newsletter.empty')}
                </td>
              </tr>
            ) : (
              listView.items.map((row) => (
                <tr
                  key={row.id}
                  className={`hover:bg-slate-50 dark:hover:bg-slate-800/60 ${
                    bulkSelection.isSelected(row.id) ? 'bg-indigo-50/60 dark:bg-indigo-950/20' : ''
                  }`}
                >
                  <td className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(row.id)}
                      onChange={() => bulkSelection.toggle(row.id)}
                      disabled={bulkBusy}
                      aria-label={t('list.inbox.selectItem')}
                      className="rounded border-slate-300 dark:border-slate-600"
                    />
                  </td>
                  <td className="px-4 py-3 font-medium text-slate-900 dark:text-white">{row.email}</td>
                  <td className="px-4 py-3 text-slate-500 dark:text-slate-400">{formatSource(row.source)}</td>
                  <td className="px-4 py-3 text-slate-500 dark:text-slate-400">{formatPreferences(row.preferences)}</td>
                  <td className="px-4 py-3 text-slate-500 dark:text-slate-400">{formatStatus(row.status)}</td>
                  <td className="px-4 py-3 text-slate-500 dark:text-slate-400">
                    {row.subscribedAt
                      ? new Date(row.subscribedAt).toLocaleString(dateLocale)
                      : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      {row.status !== 'unsubscribed' ? (
                        <button
                          type="button"
                          onClick={() => void handleUnsubscribeOne(row.id)}
                          disabled={bulkBusy}
                          className="inline-flex items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-60"
                          title={t('newsletter.actions.unsubscribe')}
                        >
                          <UserMinus className="h-3.5 w-3.5" />
                          {t('newsletter.actions.unsubscribe')}
                        </button>
                      ) : null}
                      <button
                        type="button"
                        onClick={() => void handleDeleteOne(row.id)}
                        disabled={bulkBusy}
                        className="inline-flex items-center gap-1 rounded-lg border border-red-200 dark:border-red-900/50 px-2 py-1 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 disabled:opacity-60"
                        title={t('newsletter.actions.delete')}
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                        {t('newsletter.actions.delete')}
                      </button>
                    </div>
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
