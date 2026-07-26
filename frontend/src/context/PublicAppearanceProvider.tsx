import React, { createContext, useContext } from 'react';
import { useLocation } from 'react-router-dom';
import { usePublicAppearance, type PublicAppearanceSettings } from '../hooks/usePublicAppearance';
import { isAdminAppRoute } from '../utils/appRoutes';
import type { AppearanceMode, ColorSchemeId, ResolvedTheme } from '../theme/colorSchemes';

interface PublicAppearanceContextValue {
  colorSchemeId: ColorSchemeId;
  siteMode: AppearanceMode;
  effectiveMode: AppearanceMode;
  resolvedTheme: ResolvedTheme;
  allowUserToggle: boolean;
  visitorMode: AppearanceMode | null;
  setVisitorMode: (mode: AppearanceMode | null) => void;
  toggleVisitorTheme: () => void;
}

const PublicAppearanceContext = createContext<PublicAppearanceContextValue | undefined>(undefined);

export const PublicAppearanceProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const location = useLocation();
  const enabled = !isAdminAppRoute(location.pathname);
  const appearance = usePublicAppearance({ enabled });

  return (
    <PublicAppearanceContext.Provider value={appearance}>{children}</PublicAppearanceContext.Provider>
  );
};

export function usePublicAppearanceContext(): PublicAppearanceContextValue {
  const context = useContext(PublicAppearanceContext);
  if (!context) {
    throw new Error('usePublicAppearanceContext must be used within PublicAppearanceProvider');
  }
  return context;
}

export type { PublicAppearanceSettings };

export default PublicAppearanceProvider;
