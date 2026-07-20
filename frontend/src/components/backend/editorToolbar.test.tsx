import { describe, expect, it } from 'vitest';
import { render, within } from '@testing-library/react';
import { MarkdownContentEditor } from './MarkdownContentEditor';
import { getEditorProfile } from '../../utils/editorProfiles';

describe('MarkdownContentEditor toolbar profiles', () => {
  it('renders fewer formatting buttons for minimal profile', () => {
    const { container: minimalRoot } = render(
      <MarkdownContentEditor
        value=""
        onChange={() => undefined}
        profile={getEditorProfile('minimal')}
      />
    );

    const { container: developerRoot } = render(
      <MarkdownContentEditor
        value=""
        onChange={() => undefined}
        profile={getEditorProfile('developer')}
      />
    );

    const minimalButtons = minimalRoot.querySelectorAll('button[title]');
    const developerButtons = developerRoot.querySelectorAll('button[title]');

    expect(minimalButtons.length).toBeLessThan(developerButtons.length);
    expect(within(minimalRoot).queryByTitle('Obrázok')).not.toBeInTheDocument();
    expect(within(developerRoot).queryByTitle('Obrázok')).toBeInTheDocument();
  });
});
