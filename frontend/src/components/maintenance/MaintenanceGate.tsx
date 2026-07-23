import React from 'react';
import { useLocation } from 'react-router-dom';
import { isMaintenanceActive } from '../../api/maintenance';
import { useAuth } from '../../hooks/useAuth';
import { useSettingsContext } from '../../context/SettingsContext';
import { ComingSoonPage } from './ComingSoonPage';
import { UnderMaintenancePage } from './UnderMaintenancePage';

const STAFF_ROLES = ['EDITOR', 'ADMIN', 'SUPER_ADMIN'];

interface MaintenanceGateProps {
  children: React.ReactNode;
}

export const MaintenanceGate: React.FC<MaintenanceGateProps> = ({ children }) => {
  const { settings, loading } = useSettingsContext();
  const { user, pendingTwoFactor } = useAuth();
  const { pathname } = useLocation();

  const mode = settings.maintenance?.mode ?? 'off';
  const maintenanceActive = isMaintenanceActive(mode);

  const isStaff =
    Boolean(user && !pendingTwoFactor) &&
    (user?.roles ?? []).some((role) => STAFF_ROLES.includes(role));

  if (loading || !maintenanceActive || isStaff) {
    return <>{children}</>;
  }

  if (pathname === '/login' || pathname.startsWith('/reset-password')) {
    return <>{children}</>;
  }

  if (mode === 'coming_soon') {
    return <ComingSoonPage />;
  }

  if (mode === 'under_maintenance') {
    return <UnderMaintenancePage />;
  }

  return <>{children}</>;
};
