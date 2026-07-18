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
}

export const SeoMetadataPanel: React.FC<SeoMetadataPanelProps> = ({
  values,
  onChange,
  disabled = false,
  showTags = false,
}) => {
  const patch = (partial: Partial<SeoFormValues>) => onChange({ ...values, ...partial });

  return (
    <div className="space-y-4">
      <p className="text-sm text-gray-500 dark:text-gray-400">
        These fields feed the public SEO API (`SeoMetaBuilder`). Title template still applies when SEO title is empty.
      </p>

      <div className="form-group">
        <label className="form-label flex justify-between">
          <span>SEO title</span>
          <span className="text-xs font-normal text-gray-400">{values.seoTitle.length}/60</span>
        </label>
        <input
          type="text"
          value={values.seoTitle}
          onChange={(e) => patch({ seoTitle: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="Override page title in search results"
          maxLength={120}
        />
      </div>

      <div className="form-group">
        <label className="form-label flex justify-between">
          <span>Meta description</span>
          <span className="text-xs font-normal text-gray-400">{values.seoDescription.length}/160</span>
        </label>
        <textarea
          value={values.seoDescription}
          onChange={(e) => patch({ seoDescription: e.target.value })}
          disabled={disabled}
          className="form-input min-h-[88px]"
          placeholder="Short summary for search engines and social previews"
          maxLength={300}
        />
      </div>

      <div className="form-group">
        <label className="form-label">OG / featured image URL</label>
        <input
          type="url"
          value={values.ogImage}
          onChange={(e) => patch({ ogImage: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="/storage/app/content/media/… or https://…"
        />
      </div>

      <div className="form-group">
        <label className="form-label">Canonical URL (optional)</label>
        <input
          type="url"
          value={values.canonical}
          onChange={(e) => patch({ canonical: e.target.value })}
          disabled={disabled}
          className="form-input"
          placeholder="Leave empty for automatic canonical"
        />
      </div>

      {showTags && (
        <div className="form-group">
          <label className="form-label">Tags (comma-separated)</label>
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
        Hide from search engines (noindex)
      </label>
    </div>
  );
};

export default SeoMetadataPanel;
