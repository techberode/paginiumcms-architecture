import { createContext, useContext } from 'react';

export interface DeveloperUnlockGateContextValue {
  lock: () => Promise<boolean>;
  isUnlocked: boolean;
  locking: boolean;
}

export const DeveloperUnlockGateContext = createContext<DeveloperUnlockGateContextValue | null>(null);

export function useDeveloperUnlockGate(): DeveloperUnlockGateContextValue {
  const ctx = useContext(DeveloperUnlockGateContext);
  if (!ctx) {
    throw new Error('useDeveloperUnlockGate must be used within DeveloperUnlockGate');
  }
  return ctx;
}
