// frontend/src/components/backend/BulkActionBar.tsx
import React from 'react';
import { X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export interface BulkActionDefinition {
  id: string;
  label: string;
  variant?: 'primary' | 'secondary' | 'danger';
  onClick: () => void;
  disabled?: boolean;
}

interface BulkActionBarProps {
  count: number;
  itemLabel?: string;
  onClear: () => void;
  actions: BulkActionDefinition[];
}

export const BulkActionBar: React.FC<BulkActionBarProps> = ({
  count,
  itemLabel,
  onClear,
  actions,
}) => {
  const { t } = useI18n();
  const resolvedItemLabel = itemLabel ?? t('list.bulk.selectedItems');

  if (count <= 0) {
    return null;
  }

  return (
    <div className="sticky top-0 z-20 flex flex-wrap items-center gap-3 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/40 px-4 py-3">
      <p className="text-sm font-medium text-indigo-900 dark:text-indigo-100">
        {count} {resolvedItemLabel}
      </p>
      <div className="flex flex-wrap gap-2">
        {actions.map((action) => (
          <button
            key={action.id}
            type="button"
            className={`btn text-xs px-3 py-1.5 ${
              action.variant === 'danger'
                ? 'btn-danger'
                : action.variant === 'primary'
                  ? 'btn-primary'
                  : 'btn-secondary'
            }`}
            onClick={action.onClick}
            disabled={action.disabled}
          >
            {action.label}
          </button>
        ))}
      </div>
      <button
        type="button"
        className="btn btn-secondary text-xs px-2 py-1 ml-auto"
        onClick={onClear}
        aria-label={t('list.bulk.clearSelection')}
      >
        <X className="w-3 h-3" />
      </button>
    </div>
  );
};
