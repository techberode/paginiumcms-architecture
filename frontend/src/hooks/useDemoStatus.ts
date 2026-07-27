import { useEffect, useState } from 'react';
import { demoApi, type DemoStatus } from '../api/demo';

export function useDemoStatus(enabled: boolean, pollMs = 30000): DemoStatus | null {
  const [status, setStatus] = useState<DemoStatus | null>(null);

  useEffect(() => {
    if (!enabled) {
      setStatus(null);
      return;
    }

    let active = true;
    const load = async () => {
      const next = await demoApi.status();
      if (active) {
        setStatus(next);
      }
    };

    void load();
    const id = window.setInterval(() => void load(), pollMs);

    return () => {
      active = false;
      window.clearInterval(id);
    };
  }, [enabled, pollMs]);

  return status;
}

export function formatDemoCountdown(seconds: number | null | undefined): string | null {
  if (seconds == null || seconds <= 0) {
    return null;
  }

  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }

  return `${minutes}:${String(secs).padStart(2, '0')}`;
}
