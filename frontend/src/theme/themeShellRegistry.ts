import type { ReactNode } from 'react';
import type { PublicNavItem } from '../context/PublicSiteContext';
import type { NavigationLayoutSettings } from '../utils/navigationLayoutSettings';
import type { PublicNavChrome } from '../utils/publicNavChrome';
import { TerminalBreachShell } from '../themes/terminal-breach/PublicShell';
import { CleanJournalShell } from '../themes/clean-journal/PublicShell';

export interface ThemeShellProps {
  children: ReactNode;
  siteName: string;
  onOpenSearch: () => void;
  showPrimaryNav: boolean;
  navLayout: NavigationLayoutSettings;
  chrome?: PublicNavChrome;
  secondaryItems?: PublicNavItem[];
  wideHeader?: boolean;
  headerPrefix?: ReactNode;
}

export type ThemeShellComponent = React.FC<ThemeShellProps>;

const REGISTRY: Record<string, ThemeShellComponent> = {
  'terminal-breach': TerminalBreachShell,
  'clean-journal': CleanJournalShell,
};

export function resolveThemeShell(themeId: string): ThemeShellComponent | null {
  if (themeId === 'paginium-core') {
    return null;
  }

  return REGISTRY[themeId] ?? null;
}

export const THEME_SHELL_IDS = Object.keys(REGISTRY);
