// frontend/src/components/backend/SeoMetadataPanel.tsx
import React, { useState } from 'react';
import { FolderOpen, X } from 'lucide-react';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';
import { MediaPickerModal } from './MediaPickerModal';
import { SeoHealthChecklist } from './SeoHealthChecklist';
import type { ContentEditorStatus } from '../../utils/contentScheduling';
import type { ContentType } from '../../api/drafts';
import { useI18n } from '../../context/I18nContext';

export interface SeoFormValues {
  seoTitle: string;
  seoDescription: string;
  canonical: string;
  ogImage: string;
  noIndex: boolean;
  tags: string;
}

export interface SeoMetadataPanelProps {
  values: SeoFormValues;
  onChange: (values: SeoFormValues) => void;
  disabled?: boolean;
  showTags?: boolean;
  compact?: boolean;
  contentStatus?: ContentEditorStatus;
  contentType?: ContentType;
}

function seoImagePreviewSrc(url: string): string {
  const raw = url.trim();
  if (!raw) {
    return '';
  }

  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/api/')) {
    return raw;
  }

  if (raw.startsWith('/storage/')) {
    return resolvePublicMediaUrl(raw);
  }

  if (raw.startsWith('media/')) {
    return resolveAdminMediaPreviewUrl(raw);
  }

  return raw;
}

export const SeoMetadataPanel: React.FC<SeoMetadataPanelProps> = ({
  values,
  onChange,
  disabled = false,
  showTags = false,
  compact = false,
  contentStatus = 'draft',
  contentType = 'page',
}) => {
  const { t } = useI18n();
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const patch = (partial: Partial<SeoFormValues>) => onChange({ ...values, ...partial });
  const previewSrc = seoImagePreviewSrc(values.ogImage);

  return (
    <div className="space-y-4">
      {!compact && <p className="text-sm text-gray-500 dark:text-gray-400">{t('editor.seo.intro')}</p>}

      <SeoHealthChecklist
        compact={compact}
        input={{
          status: contentStatus,
          checkAsPublished: true,
          contentType,
          seoTitle: values.seoTitle,
          seoDescription: values.seoDescription,
          ogImage: values.ogImage,
          tags: values.tags,
        }}
      />

      <div className="form-group">
        <label className="form-label flex justify-between">
          <span>{t('editor.seo.seoTitle')}</span>
          <span className="text-xs font-normal text-gray-400">{values.seoTitle.length}/60</span>
        </label>
        <input
          type="text"
          value={values.seoTitle}
          onChange={(e) => patch({ seoTitle: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder={t('editor.seo.seoTitlePlaceholder')}
          maxLength={120}
        />
      </div>

      {!compact && (
        <div className="form-group">
          <label className="form-label flex justify-between">
            <span>{t('editor.seo.metaDescription')}</span>
            <span className="text-xs font-normal text-gray-400">{values.seoDescription.length}/160</span>
          </label>
          <textarea
            value={values.seoDescription}
            onChange={(e) => patch({ seoDescription: e.target.value })}
            disabled={disabled}
            className="form-input min-h-[88px]"
            placeholder={t('editor.seo.metaDescriptionPlaceholder')}
            maxLength={300}
          />
        </div>
      )}

      <div className="form-group">
        <label className="form-label">{t('editor.seo.ogImage')}</label>
        <div className="flex flex-wrap gap-2">
          <input
            type="url"
            value={values.ogImage}
            onChange={(e) => patch({ ogImage: e.target.value })}
            disabled={disabled}
            className="form-input min-w-0 flex-1"
            placeholder={t('editor.seo.ogImagePlaceholder')}
          />
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2 whitespace-nowrap text-sm"
            disabled={disabled}
            onClick={() => setMediaPickerOpen(true)}
          >
            <FolderOpen className="h-4 w-4" />
            {t('editor.seo.pickFromMedia')}
          </button>
          {values.ogImage.trim() !== '' && (
            <button
              type="button"
              className="btn btn-secondary px-2"
              disabled={disabled}
              title={t('editor.seo.removePreview')}
              aria-label={t('editor.seo.removePreview')}
              onClick={() => patch({ ogImage: '' })}
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>
        {previewSrc !== '' && (
          <div className="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
            <img
              src={previewSrc}
              alt={t('editor.seo.ogPreviewAlt')}
              className="max-h-40 w-full object-cover"
              onError={(event) => {
                event.currentTarget.style.display = 'none';
              }}
            />
          </div>
        )}
        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
          {showTags ? t('editor.seo.ogHintArticle') : t('editor.seo.ogHint')}
        </p>
      </div>

      <div className="form-group">
        <label className="form-label">{t('editor.seo.canonical')}</label>
        <input
          type="url"
          value={values.canonical}
          onChange={(e) => patch({ canonical: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder={t('editor.seo.canonicalPlaceholder')}
        />
      </div>

      {showTags && (
        <div className="form-group">
          <label className="form-label">{t('editor.seo.tags')}</label>
          <input
            type="text"
            value={values.tags}
            onChange={(e) => patch({ tags: e.target.value })}
            disabled={disabled}
            className="form-input"
            placeholder={t('editor.seo.tagsPlaceholder')}
          />
        </div>
      )}

      <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
        <input
          type="checkbox"
          checked={values.noIndex}
          onChange={(e) => patch({ noIndex: e.target.checked })}
          disabled={disabled}
          className="rounded border-gray-300"
        />
        {t('editor.seo.noIndex')}
      </label>

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        title={t('editor.seo.pickImageTitle')}
        urlFormat="storage"
        onSelect={(url) => {
          patch({ ogImage: url });
        }}
      />
    </div>
  );
};

export default SeoMetadataPanel;
