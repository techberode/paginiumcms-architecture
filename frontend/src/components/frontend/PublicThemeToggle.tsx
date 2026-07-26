import React from 'react';
import { Moon, Sun } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

interface PublicThemeToggleProps {
  resolvedTheme: 'light' | 'dark';
  onToggle: () => void;
}

export const PublicThemeToggle: React.FC<PublicThemeToggleProps> = ({ resolvedTheme, onToggle }) => {
  const { t } = useI18n();
  const isDark = resolvedTheme === 'dark';

  return (
    <button
      type="button"
      onClick={onToggle}
      className="p-2 rounded-xl text-theme-text hover:bg-theme-surface transition-colors"
      aria-label={isDark ? t('settings.appearance.modes.light') : t('settings.appearance.modes.dark')}
      title={isDark ? t('settings.appearance.modes.light') : t('settings.appearance.modes.dark')}
    >
      {isDark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
    </button>
  );
};

export default PublicThemeToggle;
