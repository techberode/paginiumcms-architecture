// frontend/src/test/setup.ts
import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';
import { resetI18nModulesForTests } from '../i18n';
import { registerAllI18nModules } from '../i18n/registerModules';

registerAllI18nModules();

// Izolácia DOM medzi testami v tom istom súbore.
afterEach(() => {
  cleanup();
  resetI18nModulesForTests();
  registerAllI18nModules();
});
