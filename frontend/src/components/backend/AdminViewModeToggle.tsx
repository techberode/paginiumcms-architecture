// frontend/src/components/backend/AdminViewModeToggle.tsx
import React from 'react';
import { Grid3X3, LayoutList, List } from 'lucide-react';
import type { AdminViewMode } from '../../hooks/useAdminViewMode';

export interface AdminViewModeToggleProps {
  mode: AdminViewMode;
  onChange: (mode: AdminViewMode) => void;
  className?: string;
}

const MODES: { id: AdminViewMode; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
  { id: 'list', label: 'Zoznam', icon: LayoutList },
  { id: 'list-preview', label: 'Zoznam + náhľad', icon: List },
  { id: 'preview', label: 'Mriežka', icon: Grid3X3 },
];

export const AdminViewModeToggle: React.FC<AdminViewModeToggleProps> = ({
  mode,
  onChange,
  className = '',
}) => (
  <div
    className={`inline-flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden ${className}`}
    role="group"
    aria-label="View mode"
  >
    {MODES.map(({ id, label, icon: Icon }) => (
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

export default AdminViewModeToggle;
