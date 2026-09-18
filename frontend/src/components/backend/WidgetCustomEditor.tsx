import React, { useCallback, useEffect, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import {
  widgetsApi,
  type WidgetCustomDefinition,
  type WidgetFieldKind,
  type WidgetFieldSchema,
} from '../../api/widgets';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { useToast } from '../../hooks/useToast';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { AdminFormActions } from './AdminFormActions';
import { AdminOfferCard } from '../ui/AdminOfferCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { widgetTypeIcon } from '../../utils/widgetMarkup';

const DEFAULT_EXPAND =
  '<div class="pg-widget pg-widget-custom">\n  <p class="pg-widget-title">{{title}}</p>\n  <p class="pg-widget-hint">{{body}}</p>\n</div>';

const KINDS: WidgetFieldKind[] = ['string', 'tone', 'percent', 'href'];

type DraftField = WidgetFieldSchema & { rowId: string };

interface WidgetDraft extends Omit<WidgetCustomDefinition, 'fields'> {
  fields: DraftField[];
}

interface WidgetCustomEditorProps {
  onChanged: () => void;
}

function newRowId(): string {
  return `field-row-${Math.random().toString(36).slice(2, 10)}`;
}

function withRowIds(fields: WidgetFieldSchema[]): DraftField[] {
  return fields.map((field) => ({
    ...field,
    rowId: 'rowId' in field && typeof (field as DraftField).rowId === 'string'
      ? (field as DraftField).rowId
      : newRowId(),
  }));
}

function emptyDraft(): WidgetDraft {
  return {
    id: '',
    label: '',
    version: 1,
    selfClosing: true,
    fields: withRowIds([
      { key: 'title', kind: 'string' },
      { key: 'body', kind: 'string' },
    ]),
    defaults: { title: 'Title', body: 'Body' },
    expand: DEFAULT_EXPAND,
    source: 'custom',
  };
}

export const WidgetCustomEditor: React.FC<WidgetCustomEditorProps> = ({ onChanged }) => {
  const { t } = useI18n();
  const confirmDestructive = useAdminConfirm();
  const toast = useToast();
  const [items, setItems] = useState<string[]>([]);
  const [draft, setDraft] = useState<WidgetDraft>(emptyDraft());
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    try {
      const catalog = await widgetsApi.list();
      setItems(catalog.filter((item) => item.source === 'custom').map((item) => item.id));
    } catch {
      toast.error(t('platform.widgets.toast.loadFailed'));
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const loadOne = async (id: string) => {
    const response = await widgetsApi.get(id);
    if (response.success && response.data?.widget) {
      const widget = response.data.widget;
      setDraft({ ...widget, fields: withRowIds(widget.fields) });
    } else {
      toast.error(response.error || t('platform.widgets.toast.loadFailed'));
    }
  };

  const save = async () => {
    const id = draft.id.trim().toLowerCase();
    if (!/^[a-z][a-z0-9_-]{0,39}$/.test(id)) {
      toast.error(t('platform.widgets.custom.idInvalid'));
      return;
    }
    setSaving(true);
    try {
      const response = await widgetsApi.save(id, {
        ...draft,
        id,
        fields: draft.fields.map((field) => ({
          key: field.key,
          kind: field.kind,
          ...(field.options ? { options: field.options } : {}),
        })),
      });
      if (!response.success) {
        toast.error(response.error || t('platform.widgets.toast.saveFailed'));
        return;
      }
      toast.success(t('platform.widgets.toast.saved'));
      await load();
      onChanged();
    } catch {
      toast.error(t('platform.widgets.toast.saveFailed'));
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!draft.id || !(await confirmDestructive(t('platform.widgets.custom.confirmDelete')))) {
      return;
    }
    try {
      await widgetsApi.delete(draft.id);
      toast.success(t('platform.widgets.toast.deleted'));
      setDraft(emptyDraft());
      await load();
      onChanged();
    } catch {
      toast.error(t('platform.widgets.toast.deleteFailed'));
    }
  };

  const updateField = (index: number, patch: Partial<WidgetFieldSchema>) => {
    setDraft((prev) => ({
      ...prev,
      fields: prev.fields.map((field, i) => (i === index ? { ...field, ...patch } : field)),
    }));
  };

  const creating = !items.includes(draft.id);

  return (
    <AdminWidgetCard
      title={t('platform.widgets.custom.title')}
      description={t('platform.widgets.custom.hint')}
    >
      <div className="space-y-4" data-testid="widget-custom-editor">
      <div className="grid gap-3 sm:grid-cols-2">
        <AdminOfferCard
          active={creating}
          icon={Plus}
          title={t('platform.widgets.custom.new')}
          testId="widget-custom-new"
          onSelect={() => setDraft(emptyDraft())}
        />
        {items.map((id) => (
          <AdminOfferCard
            key={id}
            active={draft.id === id}
            icon={widgetTypeIcon(id, 'custom')}
            title={id}
            onSelect={() => void loadOne(id)}
          />
        ))}
      </div>
      <div className="grid gap-3 md:grid-cols-2">
        <label className="text-sm text-admin-text">
          {t('platform.widgets.custom.id')}
          <input
            className={`mt-1 font-mono ${ADMIN_INPUT}`}
            value={draft.id}
            onChange={(event) => setDraft((prev) => ({ ...prev, id: event.target.value }))}
            data-testid="widget-custom-id"
          />
        </label>
        <label className="text-sm text-admin-text">
          {t('platform.widgets.custom.label')}
          <input
            className={`mt-1 ${ADMIN_INPUT}`}
            value={draft.label}
            onChange={(event) => setDraft((prev) => ({ ...prev, label: event.target.value }))}
          />
        </label>
      </div>
      <div className="space-y-2">
        <p className="text-xs font-bold uppercase tracking-wide text-admin-muted">
          {t('platform.widgets.custom.fields')}
        </p>
        {draft.fields.map((field, index) => (
          <div key={field.rowId} className="grid grid-cols-[1fr_8rem_auto] gap-2">
            <input
              className={`font-mono text-sm ${ADMIN_INPUT}`}
              value={field.key}
              onChange={(event) => updateField(index, { key: event.target.value })}
              data-testid={`widget-field-key-${index}`}
            />
            <select
              className={`text-sm ${ADMIN_INPUT}`}
              value={field.kind}
              onChange={(event) => updateField(index, { kind: event.target.value as WidgetFieldKind })}
            >
              {KINDS.map((kind) => (
                <option key={kind} value={kind}>
                  {kind}
                </option>
              ))}
            </select>
            <button
              type="button"
              className="admin-chip"
              onClick={() =>
                setDraft((prev) => ({ ...prev, fields: prev.fields.filter((_, i) => i !== index) }))
              }
            >
              ×
            </button>
          </div>
        ))}
        <button
          type="button"
          className="admin-chip"
          onClick={() =>
            setDraft((prev) => ({
              ...prev,
              fields: [
                ...prev.fields,
                { key: `field${prev.fields.length + 1}`, kind: 'string', rowId: newRowId() },
              ],
            }))
          }
        >
          {t('platform.widgets.custom.addField')}
        </button>
      </div>
      <label className="block text-sm text-admin-text">
        {t('platform.widgets.custom.expand')}
        <textarea
          className={`mt-1 min-h-[8rem] font-mono text-xs ${ADMIN_INPUT}`}
          value={draft.expand}
          onChange={(event) => setDraft((prev) => ({ ...prev, expand: event.target.value }))}
          data-testid="widget-custom-expand"
        />
      </label>
      <p className="text-xs text-admin-muted">{t('platform.widgets.custom.classHint')}</p>
      <AdminFormActions
        onSave={() => void save()}
        saveLabel={t('platform.widgets.custom.save')}
        saveDisabled={saving}
        saveBusy={saving}
        saveTestId="widget-custom-save"
        extra={
          draft.id && items.includes(draft.id) ? (
            <button type="button" className="admin-chip text-rose-600" onClick={() => void remove()}>
              <Trash2 className="mr-1 inline h-3.5 w-3.5" />
              {t('platform.widgets.custom.delete')}
            </button>
          ) : null
        }
      />
      </div>
    </AdminWidgetCard>
  );
};
