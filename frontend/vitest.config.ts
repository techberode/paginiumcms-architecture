/// <reference types="vitest/config" />
// frontend/vitest.config.ts
// DOM prostredie: happy-dom (nie jsdom) – rýchlejší štart testov.
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'happy-dom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: [
      'src/**/*.{test,spec}.{ts,tsx}',
      'src/**/*.security.test.{ts,tsx}',
    ],
    css: false,
    pool: 'threads',
    // Kratší default timeout – testy by mali padať skôr, nie visieť.
    testTimeout: 5_000,
    // Predbalenie testovacích knižníc zrýchli import fázu.
    deps: {
      optimizer: {
        web: {
          include: [
            '@testing-library/react',
            '@testing-library/user-event',
            '@testing-library/jest-dom',
          ],
        },
      },
    },
  },
});
