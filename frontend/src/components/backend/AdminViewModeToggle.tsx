// frontend/src/components/backend/AdminViewModeToggle.tsx
import React, { useMemo } from 'react';
import { Grid3X3, LayoutList, List } from 'lucide-react';
import type { AdminViewMode } from '../../hooks/useAdminViewMode';
import { useI18n } from '../../context/I18nContext';

export interface AdminViewModeToggleProps {
  mode: AdminViewMode;
  onChange: (mode: AdminViewMode) => void;
  className?: string;
}

export const AdminViewModeToggle: React.FC<AdminViewModeToggleProps> = ({
  mode,
  onChange,
  className = '',
}) => {
  const { t } = useI18n();
  const modes = useMemo(
    () =>
      [
        { id: 'list' as const, label: t('list.viewMode.list'), icon: LayoutList },
        { id: 'list-preview' as const, label: t('list.viewMode.listPreview'), icon: List },
        { id: 'preview' as const, label: t('list.viewMode.grid'), icon: Grid3X3 },
      ],
    [t]
  );

  return (
    <div
      className={`inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden ${className}`}
      role="group"
      aria-label={t('list.viewMode.ariaLabel')}
    >
      {modes.map(({ id, label, icon: Icon }) => (
        <button
          key={id}
          type="button"
          className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium transition-colors ${
            mode === id
              ? 'bg-indigo-600 text-white'
              : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
          }`}
          onClick={() => onChange(id)}
          aria-pressed={mode === id}
          title={label}
        >
          <Icon className="w-4 h-4" />
          <span className="hidden sm:inline">{label}</span>
          <span className="sm:hidden sr-only">{label}</span>
        </button>
      ))}
    </div>
  );
};

export default AdminViewModeToggle;
