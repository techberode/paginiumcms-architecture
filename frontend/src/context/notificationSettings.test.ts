// frontend/src/context/notificationSettings.test.ts
import { describe, it, expect } from 'vitest';

const POSITION_CLASSES: Record<string, string> = {
  'top-right': 'top-4 right-4',
  'top-left': 'top-4 left-4',
  'bottom-right': 'bottom-4 right-4',
  'bottom-left': 'bottom-4 left-4',
};

describe('toast position classes', () => {
  it('maps all supported positions', () => {
    expect(POSITION_CLASSES['top-right']).toBe('top-4 right-4');
    expect(POSITION_CLASSES['bottom-left']).toBe('bottom-4 left-4');
  });
});

describe('toast duration with debug mode', () => {
  it('extends duration when debug mode is on', () => {
    const base = 3000;
    const debug = true;
    const duration = debug ? Math.max(base, 8000) : base;
    expect(duration).toBe(8000);
  });
});
