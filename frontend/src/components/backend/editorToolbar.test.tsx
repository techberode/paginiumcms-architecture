import { describe, expect, it, vi } from 'vitest';
import { waitFor, within } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { MarkdownContentEditor } from './MarkdownContentEditor';
import { getEditorProfile } from '../../utils/editorProfiles';

vi.mock('../../utils/editorComponents', () => ({
  loadAllowedEditorComponents: vi.fn(() => Promise.resolve([])),
}));

describe('MarkdownContentEditor toolbar profiles', () => {
  it('renders fewer formatting buttons for minimal profile', async () => {
    const { container: minimalRoot } = renderWithProviders(
      <MarkdownContentEditor
        value=""
        onChange={() => undefined}
        profile={getEditorProfile('minimal')}
      />
    );

    const { container: developerRoot } = renderWithProviders(
      <MarkdownContentEditor
        value=""
        onChange={() => undefined}
        profile={getEditorProfile('developer')}
      />
    );

    await waitFor(() => {
      expect(minimalRoot.querySelectorAll('button[title]').length).toBeGreaterThan(0);
      expect(developerRoot.querySelectorAll('button[title]').length).toBeGreaterThan(0);
    });

    const minimalButtons = minimalRoot.querySelectorAll('button[title]');
    const developerButtons = developerRoot.querySelectorAll('button[title]');

    expect(minimalButtons.length).toBeLessThan(developerButtons.length);
    expect(within(minimalRoot).queryByTitle('Obrázok')).not.toBeInTheDocument();
    expect(within(developerRoot).queryByTitle('Obrázok')).toBeInTheDocument();
  });
});
