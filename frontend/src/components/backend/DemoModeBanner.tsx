// frontend/src/components/backend/DemoModeBanner.tsx
import React from 'react';
import { FlaskConical } from 'lucide-react';
import { useSettings } from '../../hooks/useSettings';
import { useI18n } from '../../context/I18nContext';

export const DemoModeBanner: React.FC = () => {
  const { settings } = useSettings();
  const { t } = useI18n();
  const enabled = Boolean(settings.demo?.enabled);

  if (!enabled) {
    return null;
  }

  return (
    <div className="bg-amber-500 text-amber-950 px-4 py-2 text-sm font-bold flex items-center justify-center gap-2">
      <FlaskConical size={16} />
      {t('platform.demoBanner.message')}
    </div>
  );
};

export default DemoModeBanner;
