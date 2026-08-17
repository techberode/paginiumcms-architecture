import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { categoriesApi, type ContentCategory } from '../../api/categories';
import { useI18n } from '../../context/I18nContext';

export interface ArticleCategoryPickerProps {
  value: string;
  onChange: (slug: string) => void;
  disabled?: boolean;
}

export const ArticleCategoryPicker: React.FC<ArticleCategoryPickerProps> = ({
  value,
  onChange,
  disabled = false,
}) => {
  const { t } = useI18n();
  const [categories, setCategories] = useState<ContentCategory[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      setLoading(true);
      try {
        const items = await categoriesApi.listPublic();
        if (!cancelled) {
          setCategories(items);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-2">
      <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
        {t('editor.category.title')}
      </label>
      <select
        className="form-input w-full"
        value={value}
        disabled={disabled || loading}
        onChange={(event) => onChange(event.target.value)}
      >
        <option value="">{t('editor.category.none')}</option>
        {categories.map((category) => (
          <option key={category.slug} value={category.slug}>
            {category.label}
          </option>
        ))}
      </select>
      <p className="text-xs text-slate-500">
        {t('editor.category.hint')}{' '}
        <Link to="/categories" className="font-semibold text-indigo-600 hover:underline">
          {t('editor.category.manageLink')}
        </Link>
      </p>
    </div>
  );
};

export default ArticleCategoryPicker;
