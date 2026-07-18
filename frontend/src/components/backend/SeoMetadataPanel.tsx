// frontend/src/components/backend/SeoMetadataPanel.tsx
import React, { useState } from 'react';
import { FolderOpen, X } from 'lucide-react';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../../api/media';
import { MediaPickerModal } from './MediaPickerModal';

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
}) => {
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const patch = (partial: Partial<SeoFormValues>) => onChange({ ...values, ...partial });
  const previewSrc = seoImagePreviewSrc(values.ogImage);

  return (
    <div className="space-y-4">
      {!compact && (
        <p className="text-sm text-gray-500 dark:text-gray-400">
          Tieto polia napájame na verejné SEO meta tagy. Ak SEO titulok necháte prázdny, použije sa názov stránky.
        </p>
      )}

      <div className="form-group">
        <label className="form-label flex justify-between">
          <span>SEO titulok</span>
          <span className="text-xs font-normal text-gray-400">{values.seoTitle.length}/60</span>
        </label>
        <input
          type="text"
          value={values.seoTitle}
          onChange={(e) => patch({ seoTitle: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="Alternatívny titulok vo vyhľadávaní"
          maxLength={120}
        />
      </div>

      {!compact && (
        <div className="form-group">
          <label className="form-label flex justify-between">
            <span>Meta popis</span>
            <span className="text-xs font-normal text-gray-400">{values.seoDescription.length}/160</span>
          </label>
          <textarea
            value={values.seoDescription}
            onChange={(e) => patch({ seoDescription: e.target.value })}
            disabled={disabled}
            className="form-input min-h-[88px]"
            placeholder="Krátky súhrn pre vyhľadávače a sociálne siete"
            maxLength={300}
          />
        </div>
      )}

      <div className="form-group">
        <label className="form-label">OG / náhľadový obrázok</label>
        <div className="flex flex-wrap gap-2">
          <input
            type="url"
            value={values.ogImage}
            onChange={(e) => patch({ ogImage: e.target.value })}
            disabled={disabled}
            className="form-input min-w-0 flex-1"
            placeholder="/storage/app/content/media/… alebo https://…"
          />
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2 whitespace-nowrap text-sm"
            disabled={disabled}
            onClick={() => setMediaPickerOpen(true)}
          >
            <FolderOpen className="h-4 w-4" />
            Vybrať z médií
          </button>
          {values.ogImage.trim() !== '' && (
            <button
              type="button"
              className="btn btn-secondary px-2"
              disabled={disabled}
              title="Odstrániť náhľad"
              aria-label="Odstrániť náhľad"
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
              alt="Náhľad OG obrázka"
              className="max-h-40 w-full object-cover"
              onError={(event) => {
                event.currentTarget.style.display = 'none';
              }}
            />
          </div>
        )}
        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
          Použije sa pre sociálne siete{showTags ? ' a ako náhľad článku v zozname' : ''}.
        </p>
      </div>

      <div className="form-group">
        <label className="form-label">Kanonická URL (voliteľné)</label>
        <input
          type="url"
          value={values.canonical}
          onChange={(e) => patch({ canonical: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="Prázdne = automatická kanonická URL"
        />
      </div>

      {showTags && (
        <div className="form-group">
          <label className="form-label">Tagy (oddelené čiarkou)</label>
          <input
            type="text"
            value={values.tags}
            onChange={(e) => patch({ tags: e.target.value })}
            disabled={disabled}
            className="form-input"
            placeholder="tech, cms, flat-file"
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
        Skryť pred vyhľadávačmi (noindex)
      </label>

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        title="Vybrať náhľadový obrázok"
        urlFormat="storage"
        onSelect={(url) => {
          patch({ ogImage: url });
        }}
      />
    </div>
  );
};

export default SeoMetadataPanel;
