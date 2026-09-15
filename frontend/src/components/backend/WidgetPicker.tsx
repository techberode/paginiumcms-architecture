import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { widgetsApi, type WidgetTypeDefinition } from '../../api/widgets';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { AdminOfferCard } from '../ui/AdminOfferCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';
import { buildWidgetMarkup, sampleInnerFor, widgetTypeIcon, widgetTypeLabel } from '../../utils/widgetMarkup';

interface WidgetPickerProps {
  actionLabel: string;
  onAction: (markup: string) => void;
  disabled?: boolean;
  reloadToken?: number;
}

export const WidgetPicker: React.FC<WidgetPickerProps> = ({
  actionLabel,
  onAction,
  disabled,
  reloadToken = 0,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [items, setItems] = useState<WidgetTypeDefinition[]>([]);
  const [selectedId, setSelectedId] = useState('');
  const [values, setValues] = useState<Record<string, string>>({});
  const [inner, setInner] = useState('');
  const [html, setHtml] = useState('');
  const [previewing, setPreviewing] = useState(false);

  const selected = useMemo(
    () => items.find((item) => item.id === selectedId) ?? null,
    [items, selectedId]
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await widgetsApi.list();
      setItems(data);
      const first = data[0];
      if (first) {
        setSelectedId(first.id);
        setValues({ ...first.defaults });
        setInner(sampleInnerFor(first));
      }
    } catch {
      toast.error(t('platform.widgets.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load, reloadToken]);

  useEffect(() => {
    if (!selected) {
      setHtml('');
      return;
    }

    setPreviewing(true);
    const timer = window.setTimeout(() => {
      void widgetsApi
        .preview({
          type: selected.id,
          attrs: values,
          content: selected.selfClosing ? '' : inner,
        })
        .then((response) => {
          setHtml(response.success && response.data ? response.data.html : '');
        })
        .catch(() => {
          setHtml('');
        })
        .finally(() => {
          setPreviewing(false);
        });
    }, 280);

    return () => window.clearTimeout(timer);
  }, [selected, values, inner]);

  const markup = selected ? buildWidgetMarkup(selected, values, inner) : '';

  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] items-start" data-testid="widget-picker">
      <div className="space-y-3">
        <p className="text-xs font-bold uppercase tracking-wide text-admin-muted">{t('platform.widgets.catalog')}</p>
        {loading ? (
          <p className="text-sm text-admin-muted">{t('platform.widgets.loading')}</p>
        ) : (
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-1">
            {items.map((item) => (
              <AdminOfferCard
                key={item.id}
                active={item.id === selectedId}
                disabled={disabled}
                icon={widgetTypeIcon(item.id, item.source)}
                title={widgetTypeLabel(item, t)}
                subtitle={item.source === 'custom' ? t('platform.widgets.custom.title') : undefined}
                testId={`widget-type-${item.id}`}
                onSelect={() => {
                  setSelectedId(item.id);
                  setValues({ ...item.defaults });
                  setInner(sampleInnerFor(item));
                }}
              />
            ))}
          </div>
        )}
      </div>

      <div className="space-y-4">
        {selected ? (
          <>
            <AdminWidgetCard title={widgetTypeLabel(selected, t)}>
              <div className="space-y-3">
                {selected.fields.map((field) => (
                  <label key={field.key} className="block text-sm text-admin-text">
                    {t(`platform.widgets.fields.${field.key}`) === `platform.widgets.fields.${field.key}`
                      ? field.key
                      : t(`platform.widgets.fields.${field.key}`)}
                    {field.kind === 'tone' ? (
                      <select
                        className={`mt-1 ${ADMIN_INPUT}`}
                        value={values[field.key] ?? ''}
                        disabled={disabled}
                        onChange={(event) =>
                          setValues((prev) => ({ ...prev, [field.key]: event.target.value }))
                        }
                      >
                        {(field.options ?? ['primary', 'success', 'warn', 'muted']).map((option) => (
                          <option key={option} value={option}>
                            {t(`platform.widgets.tones.${option}`)}
                          </option>
                        ))}
                      </select>
                    ) : (
                      <input
                        className={`mt-1 ${ADMIN_INPUT}`}
                        value={values[field.key] ?? ''}
                        disabled={disabled}
                        onChange={(event) =>
                          setValues((prev) => ({ ...prev, [field.key]: event.target.value }))
                        }
                      />
                    )}
                  </label>
                ))}
                {!selected.selfClosing ? (
                  <label className="block text-sm text-admin-text">
                    {t('platform.widgets.inner')}
                    <textarea
                      className={`mt-1 min-h-[6rem] font-mono text-xs ${ADMIN_INPUT}`}
                      value={inner}
                      disabled={disabled}
                      onChange={(event) => setInner(event.target.value)}
                    />
                  </label>
                ) : null}
                <pre className="overflow-x-auto rounded-lg bg-admin-canvas p-3 text-xs text-admin-muted" data-testid="widget-markup">
                  {markup}
                </pre>
                <button
                  type="button"
                  disabled={disabled || markup === ''}
                  onClick={() => onAction(markup)}
                  className="btn-primary"
                  data-testid="widget-action"
                >
                  {actionLabel}
                </button>
              </div>
            </AdminWidgetCard>
            <AdminWidgetCard title={t('platform.widgets.preview')}>
              {previewing && html === '' ? (
                <p className="text-sm text-admin-muted">{t('platform.widgets.loading')}</p>
              ) : (
                <MarkdownRenderer content="" html={html} className="paginium-prose pg-shortcode-surface max-w-none" />
              )}
            </AdminWidgetCard>
          </>
        ) : (
          <p className="text-sm text-admin-muted">{t('platform.widgets.empty')}</p>
        )}
      </div>
    </div>
  );
};
