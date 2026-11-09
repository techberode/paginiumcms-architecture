import React, { useEffect, useState } from 'react';
import { Loader2, Send } from 'lucide-react';
import { fetchPublicFormSchema, submitPublicForm, type PublicFormSchema } from '../api/publicForms';
import { useI18n } from '../context/I18nContext';
import { BTN_PRIMARY, INPUT_THEME, PUBLIC_CARD } from '../theme/publicUiClasses';
import type { IslandProps } from './publicIslandDefinitions';

export const PublicFormIsland: React.FC<{ attrs: IslandProps }> = ({ attrs }) => {
  const { t } = useI18n();
  const formId = (attrs.formId || attrs['form-id'] || '').trim();
  const [schema, setSchema] = useState<PublicFormSchema | null>(null);
  const [loading, setLoading] = useState(true);
  const [values, setValues] = useState<Record<string, string | boolean>>({});
  const [honeypot, setHoneypot] = useState('');
  const [busy, setBusy] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    void fetchPublicFormSchema(formId).then((next) => {
      if (!cancelled) {
        setSchema(next);
        setLoading(false);
      }
    });
    return () => {
      cancelled = true;
    };
  }, [formId]);

  if (formId === '') {
    return null;
  }

  if (loading) {
    return (
      <div className={`${PUBLIC_CARD} p-6 flex justify-center`}>
        <Loader2 className="h-6 w-6 animate-spin text-theme-text-muted" aria-hidden />
      </div>
    );
  }

  if (!schema) {
    return (
      <div className={`${PUBLIC_CARD} p-6 text-sm text-theme-text-muted`}>{t('public.formBuilder.unavailable')}</div>
    );
  }

  const onSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      const payload: Record<string, unknown> = { ...values, _hp: honeypot };
      const result = await submitPublicForm(formId, payload);
      if (result.ok) {
        setSent(true);
      } else {
        setError(result.error || t('public.formBuilder.failed'));
      }
    } finally {
      setBusy(false);
    }
  };

  if (sent) {
    return (
      <section className={`${PUBLIC_CARD} p-6 sm:p-8 text-center`}>
        <p className="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{t('public.formBuilder.sent')}</p>
      </section>
    );
  }

  const inputClass = `w-full px-3 py-2 rounded-lg ${INPUT_THEME}`;

  return (
    <section className={`${PUBLIC_CARD} p-6 sm:p-8`} aria-labelledby={`form-title-${formId}`}>
      <h2 id={`form-title-${formId}`} className="text-lg font-black text-theme-text mb-1">
        {schema.title}
      </h2>
      {schema.description ? <p className="text-sm text-theme-text-muted mb-4">{schema.description}</p> : null}
      <form onSubmit={(e) => void onSubmit(e)} className="space-y-4">
        <input
          type="text"
          name="_hp"
          className="hidden"
          tabIndex={-1}
          autoComplete="off"
          value={honeypot}
          onChange={(e) => setHoneypot(e.target.value)}
        />
        {schema.fields.map((field) => {
          if (field.type === 'checkbox') {
            return (
              <label key={field.key} className="flex items-start gap-2 text-sm text-theme-text">
                <input
                  type="checkbox"
                  checked={Boolean(values[field.key])}
                  onChange={(e) => setValues((prev) => ({ ...prev, [field.key]: e.target.checked }))}
                  required={field.required}
                />
                <span>{field.label}</span>
              </label>
            );
          }

          if (field.type === 'textarea') {
            return (
              <label key={field.key} className="block text-sm">
                <span className="text-theme-text-muted">{field.label}</span>
                <textarea
                  className={`${inputClass} mt-1 min-h-[120px]`}
                  required={field.required}
                  placeholder={field.placeholder}
                  value={String(values[field.key] ?? '')}
                  onChange={(e) => setValues((prev) => ({ ...prev, [field.key]: e.target.value }))}
                />
              </label>
            );
          }

          const inputType = field.type === 'email' ? 'email' : field.type === 'tel' ? 'tel' : field.type === 'url' ? 'url' : 'text';

          return (
            <label key={field.key} className="block text-sm">
              <span className="text-theme-text-muted">{field.label}</span>
              <input
                type={inputType}
                className={`${inputClass} mt-1`}
                required={field.required}
                placeholder={field.placeholder}
                value={String(values[field.key] ?? '')}
                onChange={(e) => setValues((prev) => ({ ...prev, [field.key]: e.target.value }))}
              />
            </label>
          );
        })}
        {error ? <p className="text-sm text-red-600 dark:text-red-400">{error}</p> : null}
        <button type="submit" disabled={busy} className={`${BTN_PRIMARY} inline-flex items-center gap-2`}>
          {busy ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
          {t('public.formBuilder.submit')}
        </button>
      </form>
    </section>
  );
};
