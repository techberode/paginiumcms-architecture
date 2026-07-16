import { describe, expect, it, afterEach } from 'vitest';
import { registerModuleMessages, resetI18nModulesForTests, translate } from './index';

describe('i18n security/core', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('uses Slovak as default locale', () => {
    expect(translate('sk', 'common.save')).toBe('Uložiť');
    expect(translate('en', 'common.save')).toBe('Save');
  });

  it('supports module-level language blocks', () => {
    registerModuleMessages('sk', 'media', { upload_success: 'Súbor bol nahraný' });
    registerModuleMessages('en', 'media', { upload_success: 'File uploaded' });

    expect(translate('sk', 'media.upload_success')).toBe('Súbor bol nahraný');
    expect(translate('en', 'media.upload_success')).toBe('File uploaded');
  });

  it('replaces placeholders in messages', () => {
    registerModuleMessages('en', 'content', { slug_exists: 'Slug :slug already exists' });
    expect(translate('en', 'content.slug_exists', { slug: 'home' })).toBe('Slug home already exists');
  });
});
