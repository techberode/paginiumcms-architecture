import { describe, expect, it, vi } from 'vitest';
import { within } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { MarkdownContentEditor } from './MarkdownContentEditor';
import { getEditorProfile } from '../../utils/editorProfiles';
import * as editorComponents from '../../utils/editorComponents';

describe('MarkdownContentEditor custom components', () => {
  it('shows custom block button when component is allowed', async () => {
    vi.spyOn(editorComponents, 'loadAllowedEditorComponents').mockResolvedValue([
      {
        id: 'hello-widget',
        label: 'Hello Widget',
        markdownInsert: () => ':::hello-widget\nHello\n:::\n',
        tiptapNodeName: 'helloWidget',
        loadTiptapExtension: async () => ({}) as never,
      },
    ]);

    const profile = {
      ...getEditorProfile('blog'),
      customComponents: ['hello-widget'],
    };

    const { container } = renderWithProviders(
      <MarkdownContentEditor value="" onChange={() => undefined} profile={profile} />
    );

    expect(await within(container).findByTitle('Hello Widget')).toBeInTheDocument();
  });
});
