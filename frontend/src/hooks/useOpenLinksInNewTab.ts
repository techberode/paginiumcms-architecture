import { useContext } from 'react';
import { SettingsContext } from '../context/SettingsContext';

/** Reads Admin UI setting `ui.openLinksInNewTab` (default: false = same tab). */
export function useOpenLinksInNewTab(): boolean {
  const context = useContext(SettingsContext);
  return context?.settings.ui?.openLinksInNewTab === true;
}

export default useOpenLinksInNewTab;
