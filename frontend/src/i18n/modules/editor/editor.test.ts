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
    expect(translate('sk', 'editor.shell.statusLabels.scheduled')).toBe('Naplánované');
    expect(translate('en', 'editor.shell.scheduledAt')).toBe('Publish at');
    expect(translate('sk', 'editor.outline.title')).toBe('Bloky stránky');
    expect(translate('en', 'editor.outline.blocks.landing-hero')).toBe('Hero');
    expect(translate('sk', 'editor.outline.livePreviewHint')).toContain('živý náhľad');
    expect(translate('sk', 'editor.outline.starter.portfolio')).toBe('Portfolio starter');
    expect(translate('en', 'editor.outline.moveUp')).toBe('Move up');
    expect(translate('sk', 'editor.widgets.insert')).toBe('Vložiť widget');
    expect(translate('en', 'editor.widgets.title')).toBe('Insert widget');
    expect(translate('sk', 'editor.shell.workspace')).toBe('Workspace');
    expect(translate('en', 'editor.shell.workspaceExit')).toBe('Exit workspace');
    expect(translate('sk', 'editor.outline.pickMedia')).toBe('Vybrať z knižnice');
    expect(translate('en', 'editor.outline.fields.image')).toBe('Hero image');
    expect(translate('sk', 'editor.outline.blocks.feature-gallery')).toBe('Galéria');
    expect(translate('en', 'editor.outline.blocks.staff-card')).toBe('Staff card');
    expect(translate('sk', 'editor.outline.blocks.staff-team')).toBe('Karty tímu');
    expect(translate('en', 'editor.outline.fields.tag')).toBe('Feature tag');
    expect(translate('sk', 'editor.shell.builderHelp.outline')).toContain('Galérie funkcií');
    expect(translate('en', 'editor.shell.builderHelp.developer')).toContain('live preview');
    expect(translate('sk', 'editor.outline.fieldHelp.tag')).toContain('Nálepka');
    expect(translate('en', 'editor.outline.fieldHelp.tag')).toContain('Filter sticker');
  });
});
