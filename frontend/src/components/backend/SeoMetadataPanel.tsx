// frontend/src/components/backend/SeoMetadataPanel.tsx
import React from 'react';

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

export const SeoMetadataPanel: React.FC<SeoMetadataPanelProps> = ({
  values,
  onChange,
  disabled = false,
  showTags = false,
  compact = false,
}) => {
  const patch = (partial: Partial<SeoFormValues>) => onChange({ ...values, ...partial });

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
        <input
          type="url"
          value={values.ogImage}
          onChange={(e) => patch({ ogImage: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="/storage/app/content/media/… alebo https://…"
        />
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
    </div>
  );
};

export default SeoMetadataPanel;
