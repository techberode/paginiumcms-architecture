import type { ReactNode } from 'react';
import type { NavigationLayoutSettings } from '../utils/navigationLayoutSettings';
import { TerminalBreachShell } from '../themes/terminal-breach/PublicShell';
import { CleanJournalShell } from '../themes/clean-journal/PublicShell';

export interface ThemeShellProps {
  children: ReactNode;
  siteName: string;
  onOpenSearch: () => void;
  showPrimaryNav: boolean;
  navLayout: NavigationLayoutSettings;
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
