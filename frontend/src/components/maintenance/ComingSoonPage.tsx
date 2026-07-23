import React from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { MaintenanceShell } from './MaintenanceShell';
import { MaintenanceNewsletterForm } from './MaintenanceNewsletterForm';

export const ComingSoonPage: React.FC = () => {
  const { settings } = useSettingsContext();
  const maintenance = settings.maintenance;

  return (
    <MaintenanceShell
      variant="coming_soon"
      badge={maintenance?.comingSoonBadge ?? 'Pripravujeme'}
      title={maintenance?.comingSoonTitle ?? 'Už čoskoro'}
      subtitle={maintenance?.comingSoonSubtitle ?? 'Pracujeme na niečom výnimočnom.'}
      body={maintenance?.comingSoonBody}
    >
      {maintenance?.newsletterEnabled !== false ? (
        <MaintenanceNewsletterForm
          source="coming_soon"
          hint={maintenance?.newsletterHint}
        />
      ) : null}
    </MaintenanceShell>
  );
};
