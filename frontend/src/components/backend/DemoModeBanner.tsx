// frontend/src/components/backend/DemoModeBanner.tsx
import React from 'react';
import { FlaskConical } from 'lucide-react';
import { useSettings } from '../../hooks/useSettings';

export const DemoModeBanner: React.FC = () => {
  const { settings } = useSettings();
  const enabled = Boolean(settings.demo?.enabled);

  if (!enabled) {
    return null;
  }

  return (
    <div className="bg-amber-500 text-amber-950 px-4 py-2 text-sm font-bold flex items-center justify-center gap-2">
      <FlaskConical size={16} />
      Demo režim je aktívny — zmeny v sandboxe neovplyvňujú produkčný obsah
    </div>
  );
};

export default DemoModeBanner;
