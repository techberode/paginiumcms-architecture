import React from 'react';
import { Link } from 'react-router-dom';
import type { MailSignatureFields, MailSignaturePrefs, MailSignatureTemplateId } from '../../api/mail';
import { useI18n } from '../../context/I18nContext';

const MAIL_FIELD =
  'mt-1 w-full min-w-0 rounded-md border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text';

type Props = {
  mailbox: string;
  templates: Array<{ id: MailSignatureTemplateId }>;
  prefs: MailSignaturePrefs;
  fields: MailSignatureFields;
  previewHtml: string;
  saving: boolean;
  onChangePrefs: (next: MailSignaturePrefs) => void;
  onChangeFields: (next: MailSignatureFields) => void;
  onSave: () => void;
  onImportProfile: () => void;
};

export const MailSignaturePanel: React.FC<Props> = ({
  mailbox,
  templates,
  prefs,
  fields,
  previewHtml,
  saving,
  onChangePrefs,
  onChangeFields,
  onSave,
  onImportProfile,
}) => {
  const { t } = useI18n();

  const setField = (key: keyof MailSignatureFields, value: string) => {
    onChangeFields({ ...fields, [key]: value });
  };

  return (
    <div className="mt-2 space-y-3" data-testid="mail-signature-panel">
      <p className="text-xs text-admin-muted">{t('platform.mail.signatureHint', { mailbox })}</p>
      <p className="text-xs text-admin-muted">
        {t('platform.mail.signatureProfileLink')}{' '}
        <Link to="/account" className="text-admin-accent underline">
          {t('platform.mail.signatureProfileCta')}
        </Link>
      </p>
      <label className="flex items-center gap-2 text-sm text-admin-text">
        <input
          type="checkbox"
          checked={prefs.enabled}
          onChange={(event) => onChangePrefs({ ...prefs, enabled: event.target.checked })}
          data-testid="mail-signature-enabled"
        />
        {t('platform.mail.signatureEnabled')}
      </label>
      <label className="block text-sm font-medium text-admin-text">
        {t('platform.mail.signatureTemplate')}
        <select
          className={`${MAIL_FIELD} max-w-full`}
          value={prefs.templateId}
          onChange={(event) =>
            onChangePrefs({ ...prefs, templateId: event.target.value as MailSignatureTemplateId })
          }
          data-testid="mail-signature-template"
        >
          {templates.map((item) => (
            <option key={item.id} value={item.id}>
              {t(`platform.mail.signatureTemplates.${item.id}`)}
            </option>
          ))}
        </select>
      </label>
      <div className="grid gap-2">
        {(
          [
            ['displayName', 'platform.mail.signatureFields.displayName'],
            ['jobTitle', 'platform.mail.signatureFields.jobTitle'],
            ['contactEmail', 'platform.mail.signatureFields.contactEmail'],
            ['phone', 'platform.mail.signatureFields.phone'],
            ['companyName', 'platform.mail.signatureFields.companyName'],
            ['website', 'platform.mail.signatureFields.website'],
          ] as const
        ).map(([key, labelKey]) => (
          <label key={key} className="block text-xs font-medium text-admin-text">
            {t(labelKey)}
            <input
              className={MAIL_FIELD}
              value={fields[key]}
              onChange={(event) => setField(key, event.target.value)}
              data-testid={`mail-signature-field-${key}`}
            />
          </label>
        ))}
        <label className="block text-xs font-medium text-admin-text">
          {t('platform.mail.signatureFields.bio')}
          <textarea
            className={`${MAIL_FIELD} min-h-[64px]`}
            value={fields.bio}
            onChange={(event) => setField('bio', event.target.value)}
            data-testid="mail-signature-field-bio"
          />
        </label>
      </div>
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="btn btn-secondary text-xs"
          disabled={saving}
          onClick={onImportProfile}
          data-testid="mail-signature-import"
        >
          {t('platform.mail.signatureImportProfile')}
        </button>
        <button
          type="button"
          className="btn btn-primary text-xs"
          disabled={saving}
          onClick={onSave}
          data-testid="mail-signature-save"
        >
          {t('platform.mail.signatureSave')}
        </button>
      </div>
      {previewHtml ? (
        <div className="rounded-md border border-admin-border bg-white p-3 dark:bg-admin-surface">
          <p className="mb-2 text-xs font-medium text-admin-muted">{t('platform.mail.signaturePreview')}</p>
          <div
            className="text-sm text-admin-text"
            data-testid="mail-signature-preview"
            // Signature HTML is server-rendered from fixed templates with escaped fields.
            dangerouslySetInnerHTML={{ __html: previewHtml }}
          />
        </div>
      ) : null}
    </div>
  );
};
