import React from 'react';
import { Moon, Sun } from 'lucide-react';
import { useTheme } from '../../context/ThemeContext';
import { useI18n } from '../../context/I18nContext';

export const AdminThemeToggle: React.FC = () => {
  const { t } = useI18n();
  const { isDark, toggleTheme } = useTheme();
  const label = isDark ? t('admin.header.themeToLight') : t('admin.header.themeToDark');

  return (
    <button
      type="button"
      onClick={toggleTheme}
      title={label}
      aria-label={label}
      aria-pressed={isDark}
      className="admin-topbar-ghost p-2 rounded-lg transition-colors"
    >
      {isDark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
    </button>
  );
};

export default AdminThemeToggle;
