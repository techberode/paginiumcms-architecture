import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Copy, Plus, RefreshCw, RotateCcw, Send, Trash2, Webhook } from 'lucide-react';
import {
  webhooksApi,
  type WebhookDelivery,
  type WebhookMetadata,
  type WebhooksIndexResponse,
} from '../../api/webhooks';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useI18n } from '../../context/I18nContext';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';

const EVENT_LABEL_KEYS: Record<string, string> = {
  'content.published': 'platform.webhooks.events.contentPublished',
  'content.updated': 'platform.webhooks.events.contentUpdated',
};

export const WebhooksManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [index, setIndex] = useState<WebhooksIndexResponse | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [label, setLabel] = useState('');
  const [url, setUrl] = useState('');
  const [selectedEvents, setSelectedEvents] = useState<string[]>(['content.published']);
  const [copySecret, setCopySecret] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [deliveries, setDeliveries] = useState<WebhookDelivery[]>([]);

  const availableEvents = useMemo(() => index?.availableEvents ?? [], [index]);
  const webhooks = useMemo(() => index?.webhooks ?? [], [index]);

  const bulkSelection = useBulkSelection(
    webhooks.map((webhook) => webhook.id),
    String(webhooks.length)
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await webhooksApi.list();
      setIndex(data);
    } catch {
      toast.error(t('platform.webhooks.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const toggleEvent = (event: string) => {
    setSelectedEvents((prev) =>
      prev.includes(event) ? prev.filter((item) => item !== event) : [...prev, event]
    );
  };

  const handleCreate = async () => {
    if (!label.trim()) {
      toast.error(t('platform.webhooks.toast.labelRequired'));
      return;
    }
    if (!url.trim()) {
      toast.error(t('platform.webhooks.toast.urlRequired'));
      return;
    }
    if (selectedEvents.length === 0) {
      toast.error(t('platform.webhooks.toast.eventRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await webhooksApi.create({
        label: label.trim(),
        url: url.trim(),
        events: selectedEvents,
      });
      if (!created.success || !created.data) {
        toast.error(created.error || created.message || t('platform.webhooks.toast.createFailed'));
        return;
      }
      setCopySecret(created.data.secret);
      setShowCreate(false);
      setLabel('');
      setUrl('');
      setSelectedEvents(['content.published']);
      toast.success(t('platform.webhooks.toast.created'));
      await load();
    } finally {
      setCreating(false);
    }
  };

  const handleCopy = async (secret: string) => {
    try {
      await navigator.clipboard.writeText(secret);
      toast.success(t('platform.webhooks.toast.copied'));
    } catch {
      toast.error(t('platform.webhooks.toast.copyFailed'));
    }
  };

  const toggleEnabled = async (webhook: WebhookMetadata) => {
    setBusyId(webhook.id);
    try {
      const updated = await webhooksApi.update(webhook.id, { enabled: !webhook.enabled });
      if (!updated.success) {
        toast.error(updated.error || t('platform.webhooks.toast.updateFailed'));
        return;
      }
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!window.confirm(t('platform.webhooks.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }
    setBusyId('bulk');
    try {
      const result = await webhooksApi.bulkDelete(bulkSelection.selectedIds);
      if (!result) {
        toast.error(t('platform.webhooks.toast.bulkFailed'));
        return;
      }
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleDelete = async (webhook: WebhookMetadata) => {
    if (!window.confirm(t('platform.webhooks.confirm.delete', { label: webhook.label }))) {
      return;
    }
    setBusyId(webhook.id);
    try {
      const ok = await webhooksApi.remove(webhook.id);
      if (!ok) {
        toast.error(t('platform.webhooks.toast.deleteFailed'));
        return;
      }
      toast.success(t('platform.webhooks.toast.deleted'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleRotate = async (webhook: WebhookMetadata) => {
    if (!window.confirm(t('platform.webhooks.confirm.rotate', { label: webhook.label }))) {
      return;
    }
    setBusyId(webhook.id);
    try {
      const rotated = await webhooksApi.rotate(webhook.id);
      if (!rotated.success || !rotated.data) {
        toast.error(rotated.error || t('platform.webhooks.toast.rotateFailed'));
        return;
      }
      setCopySecret(rotated.data.secret);
      toast.success(t('platform.webhooks.toast.rotated'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleTest = async (webhook: WebhookMetadata) => {
    setBusyId(webhook.id);
    try {
      const result = await webhooksApi.test(webhook.id);
      if (!result.success || !result.data) {
        toast.error(result.error || t('platform.webhooks.toast.testFailed'));
        return;
      }
      const delivery = result.data.delivery;
      if (delivery?.status === 'success') {
        toast.success(t('platform.webhooks.toast.testOk'));
      } else {
        toast.error(delivery?.lastError || t('platform.webhooks.toast.testFailed'));
      }
      if (expandedId === webhook.id) {
        const rows = await webhooksApi.deliveries(webhook.id);
        setDeliveries(rows);
      }
    } finally {
      setBusyId(null);
    }
  };

  const toggleDeliveries = async (webhook: WebhookMetadata) => {
    if (expandedId === webhook.id) {
      setExpandedId(null);
      setDeliveries([]);
      return;
    }
    setExpandedId(webhook.id);
    const rows = await webhooksApi.deliveries(webhook.id);
    setDeliveries(rows);
  };

  const eventLabel = (event: string) => {
    const key = EVENT_LABEL_KEYS[event];
    return key ? t(key) : event;
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Webhook className="w-7 h-7 text-indigo-600" />
            {t('platform.webhooks.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('platform.webhooks.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.webhooks.refresh')}
          </button>
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="w-4 h-4" />
            {t('platform.webhooks.create')}
          </button>
        </div>
      </div>

      {index?.config?.encryptionEnabled === false && (
        <div className="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 p-4 text-sm text-amber-900 dark:text-amber-100">
          {t('platform.webhooks.encryptionMissing')}
        </div>
      )}

      {copySecret && (
        <div className="rounded-2xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/30 p-4 space-y-3">
          <p className="font-bold text-emerald-900 dark:text-emerald-100">{t('platform.webhooks.copyOnceTitle')}</p>
          <code className="block text-xs break-all p-3 rounded-xl bg-white dark:bg-slate-950 border border-emerald-200 dark:border-emerald-800">
            {copySecret}
          </code>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => void handleCopy(copySecret)}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold"
            >
              <Copy className="w-4 h-4" />
              {t('platform.webhooks.copy')}
            </button>
            <button
              type="button"
              onClick={() => setCopySecret(null)}
              className="px-3 py-2 rounded-xl border border-emerald-300 dark:border-emerald-700 text-sm"
            >
              {t('platform.webhooks.dismissCopy')}
            </button>
          </div>
        </div>
      )}

      {showCreate && (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-900">
          <h2 className="font-bold text-slate-900 dark:text-white">{t('platform.webhooks.createTitle')}</h2>
          <label className="text-sm space-y-1 block">
            <span>{t('platform.webhooks.fields.label')}</span>
            <input
              value={label}
              onChange={(e) => setLabel(e.target.value)}
              placeholder={t('platform.webhooks.fields.labelPlaceholder')}
              className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
            />
          </label>
          <label className="text-sm space-y-1 block">
            <span>{t('platform.webhooks.fields.url')}</span>
            <input
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              placeholder="https://hooks.example.com/paginium"
              className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
            />
          </label>
          <div className="space-y-2">
            <span className="text-sm font-medium">{t('platform.webhooks.fields.events')}</span>
            <div className="flex flex-wrap gap-2">
              {availableEvents.map((event) => (
                <label key={event} className="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                  <input
                    type="checkbox"
                    checked={selectedEvents.includes(event)}
                    onChange={() => toggleEvent(event)}
                  />
                  {eventLabel(event)}
                </label>
              ))}
            </div>
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={creating}
              onClick={() => void handleCreate()}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-50"
            >
              {creating ? t('platform.webhooks.creating') : t('platform.webhooks.createSubmit')}
            </button>
            <button
              type="button"
              onClick={() => setShowCreate(false)}
              className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
            >
              {t('platform.webhooks.cancel')}
            </button>
          </div>
        </div>
      )}

      {loading ? (
        <p className="text-sm text-slate-500">{t('platform.webhooks.loading')}</p>
      ) : webhooks.length === 0 ? (
        <p className="text-sm text-slate-500">{t('platform.webhooks.empty')}</p>
      ) : (
        <div className="space-y-3">
          <BulkActionBar
            count={bulkSelection.count}
            onClear={bulkSelection.clear}
            actions={[
              {
                id: 'delete',
                label: t('platform.webhooks.delete'),
                variant: 'danger',
                disabled: busyId === 'bulk',
                onClick: () => void handleBulkDelete(),
              },
            ]}
          />
          {webhooks.map((webhook) => (
            <div
              key={webhook.id}
              className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden"
            >
              <div className="p-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-start gap-3 min-w-0">
                  <input
                    type="checkbox"
                    className="mt-1 shrink-0"
                    checked={bulkSelection.isSelected(webhook.id)}
                    onChange={() => bulkSelection.toggle(webhook.id)}
                    aria-label={webhook.label}
                  />
                  <div className="min-w-0">
                    <div className="font-bold text-slate-900 dark:text-white">{webhook.label}</div>
                    <div className="text-xs text-slate-500 break-all">{webhook.url}</div>
                    <div className="text-xs mt-1 text-slate-600 dark:text-slate-300">
                      {webhook.events.map(eventLabel).join(' · ')}
                    </div>
                  </div>
                </div>
                <div className="flex flex-wrap gap-2">
                  <button
                    type="button"
                    disabled={busyId === webhook.id}
                    onClick={() => void toggleEnabled(webhook)}
                    className="px-3 py-1.5 rounded-lg border text-xs font-bold"
                  >
                    {webhook.enabled ? t('platform.webhooks.on') : t('platform.webhooks.off')}
                  </button>
                  <button
                    type="button"
                    disabled={busyId === webhook.id}
                    onClick={() => void handleTest(webhook)}
                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs"
                  >
                    <Send className="w-3.5 h-3.5" />
                    {t('platform.webhooks.test')}
                  </button>
                  <button
                    type="button"
                    disabled={busyId === webhook.id}
                    onClick={() => void handleRotate(webhook)}
                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs"
                  >
                    <RotateCcw className="w-3.5 h-3.5" />
                    {t('platform.webhooks.rotate')}
                  </button>
                  <button
                    type="button"
                    onClick={() => void toggleDeliveries(webhook)}
                    className="px-3 py-1.5 rounded-lg border text-xs"
                  >
                    {t('platform.webhooks.deliveries')}
                  </button>
                  <button
                    type="button"
                    disabled={busyId === webhook.id}
                    onClick={() => void handleDelete(webhook)}
                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-200 text-red-700 text-xs"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                    {t('platform.webhooks.delete')}
                  </button>
                </div>
              </div>
              {expandedId === webhook.id && (
                <div className="border-t border-slate-200 dark:border-slate-700 p-4 overflow-x-auto">
                  {deliveries.length === 0 ? (
                    <p className="text-xs text-slate-500">{t('platform.webhooks.deliveriesEmpty')}</p>
                  ) : (
                    <table className="min-w-full text-xs">
                      <thead>
                        <tr className="text-left text-slate-500">
                          <th className="pr-4 py-1">{t('platform.webhooks.columns.time')}</th>
                          <th className="pr-4 py-1">{t('platform.webhooks.columns.event')}</th>
                          <th className="pr-4 py-1">{t('platform.webhooks.columns.status')}</th>
                          <th className="pr-4 py-1">{t('platform.webhooks.columns.http')}</th>
                          <th className="py-1">{t('platform.webhooks.columns.error')}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {deliveries.map((row) => (
                          <tr key={row.id} className="border-t border-slate-100 dark:border-slate-800">
                            <td className="pr-4 py-2 whitespace-nowrap">{row.createdAt}</td>
                            <td className="pr-4 py-2">{row.event}</td>
                            <td className="pr-4 py-2">{row.status}</td>
                            <td className="pr-4 py-2">{row.httpStatus ?? '—'}</td>
                            <td className="py-2 max-w-xs truncate">{row.lastError || '—'}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
