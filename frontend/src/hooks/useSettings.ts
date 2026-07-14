// frontend/src/hooks/useSettings.ts
// === Hook: useSettings (Iterácia 4) ===
// Skrátený prístup k SettingsContext – auto-save interval, editor, siteName, …
import { useSettingsContext } from '../context/SettingsContext';

export function useSettings() {
  return useSettingsContext();
}

export default useSettings;
