// frontend/src/test/setup.ts
import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

// Izolácia DOM medzi testami v tom istom súbore.
afterEach(() => {
  cleanup();
});
