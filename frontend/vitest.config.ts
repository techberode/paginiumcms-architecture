/// <reference types="vitest/config" />
// frontend/vitest.config.ts
// Konfigurácia Vitest (Iterácia 3 – testovacia infraštruktúra).
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    // jsdom pre komponentové testy (React Testing Library).
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
    css: false,
  },
});
