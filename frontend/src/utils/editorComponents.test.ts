import { describe, expect, it } from 'vitest';
import { profileAllowsCustomComponent, parseProfileCustomComponents } from './editorComponents';
import { getEditorProfile } from './editorProfiles';

describe('editorComponents utils', () => {
  it('parses profile custom component map from JSON string', () => {
    expect(parseProfileCustomComponents('{"blog":["hello-widget"]}')).toEqual({
      blog: ['hello-widget'],
    });
  });

  it('allows component when enabled in settings map', () => {
    const profile = getEditorProfile('blog');
    expect(
      profileAllowsCustomComponent(profile, 'hello-widget', {
        customComponentsEnabled: true,
        profileCustomComponents: '{"blog":["hello-widget"]}',
      })
    ).toBe(true);
  });

  it('denies component when master switch is off', () => {
    const profile = getEditorProfile('blog');
    expect(
      profileAllowsCustomComponent(profile, 'hello-widget', {
        customComponentsEnabled: false,
        profileCustomComponents: '{"blog":["hello-widget"]}',
      })
    ).toBe(false);
  });
});
