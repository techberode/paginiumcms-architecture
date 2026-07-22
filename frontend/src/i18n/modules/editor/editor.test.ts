import { describe, expect, it, afterEach } from 'vitest';
import { editorEn } from './en';
import { editorSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('editor i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers editor catalogs', () => {
    registerModuleMessages('sk', 'editor', editorSk);
    registerModuleMessages('en', 'editor', editorEn);

    expect(translate('sk', 'editor.shell.createArticle')).toBe('Vytvoriť článok');
    expect(translate('en', 'editor.seo.seoTitle')).toBe('SEO title');
    expect(translate('sk', 'editor.comments.title')).toBe('Komentáre k článku');
    expect(translate('en', 'editor.markdown.toast.saved')).toBe('Content saved');
    expect(translate('sk', 'editor.wysiwyg.blocked.images')).toBe(
      'Profil editora nepovoľuje obrázky.'
    );
    expect(translate('en', 'editor.sitePreview.title')).toBe('Page preview');
    expect(translate('sk', 'editor.tags.add')).toBe('Pridať tag');
  });
});
