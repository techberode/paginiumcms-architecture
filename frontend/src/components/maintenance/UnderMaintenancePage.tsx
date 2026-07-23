import React from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { MaintenanceShell } from './MaintenanceShell';
import { MaintenanceNewsletterForm } from './MaintenanceNewsletterForm';
import { MaintenanceContactPanel } from './MaintenanceContactPanel';

export const UnderMaintenancePage: React.FC = () => {
  const { settings } = useSettingsContext();
  const maintenance = settings.maintenance;

  return (
    <MaintenanceShell
      variant="under_maintenance"
      badge={maintenance?.maintenanceBadge ?? 'Údržba'}
      title={maintenance?.maintenanceTitle ?? 'Momentálne prebieha údržba'}
      subtitle={maintenance?.maintenanceSubtitle ?? 'Pracujeme na vylepšeniach. Skúste to prosím neskôr.'}
      body={maintenance?.maintenanceBody}
    >
      {maintenance?.maintenanceShowContactForm !== false ? <MaintenanceContactPanel /> : null}
      {maintenance?.newsletterEnabled !== false ? (
        <MaintenanceNewsletterForm
          source="under_maintenance"
          hint={maintenance?.newsletterHint}
        />
      ) : null}
    </MaintenanceShell>
  );
};
