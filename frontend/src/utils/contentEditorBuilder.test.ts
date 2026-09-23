import { describe, expect, it } from 'vitest';
import {
  contentEditorShowsShortcodePicker,
  contentEditorUsesOutline,
} from './contentEditorBuilder';

describe('contentEditorBuilder', () => {
  it('enables outline editor for articles when site builder mode is outline', () => {
    expect(contentEditorUsesOutline('article', 'outline')).toBe(true);
    expect(contentEditorUsesOutline('page', 'outline')).toBe(true);
    expect(contentEditorUsesOutline('article', 'shortcodes')).toBe(false);
  });

  it('shows shortcode picker for articles except in outline mode', () => {
    expect(contentEditorShowsShortcodePicker('article', 'outline')).toBe(false);
    expect(contentEditorShowsShortcodePicker('article', 'shortcodes')).toBe(true);
    expect(contentEditorShowsShortcodePicker('article', 'developer')).toBe(true);
  });

  it('keeps shortcode picker pages-only in shortcodes mode', () => {
    expect(contentEditorShowsShortcodePicker('page', 'shortcodes')).toBe(true);
    expect(contentEditorShowsShortcodePicker('page', 'outline')).toBe(false);
  });
});
