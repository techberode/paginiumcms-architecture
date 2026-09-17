import { describe, expect, it, vi, beforeEach } from 'vitest';
import { waitFor } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { MarkdownContentEditor } from './MarkdownContentEditor';
import { getEditorProfile } from '../../utils/editorProfiles';
import { contentApi } from '../../api/content';

vi.mock('../../utils/editorComponents', () => ({
  loadAllowedEditorComponents: vi.fn(() => Promise.resolve([])),
}));

vi.mock('../../api/content', () => ({
  contentApi: {
    renderPreview: vi.fn(),
  },
}));

describe('MarkdownContentEditor live preview', () => {
  beforeEach(() => {
    vi.mocked(contentApi.renderPreview).mockReset();
    vi.mocked(contentApi.renderPreview).mockResolvedValue('<section class="pg-widget-kpi">Used</section>');
  });

  it('asks the server to expand shortcodes instead of rendering raw tags', async () => {
    const { getByTestId } = renderWithProviders(
      <MarkdownContentEditor
        value={'[widget type="progress" title="Storage" value="72" /]'}
        onChange={() => undefined}
        profile={getEditorProfile('developer')}
      />
    );

    const frame = await waitFor(() => getByTestId('admin-body-preview-frame'));
    expect(frame.tagName).toBe('IFRAME');
    expect(contentApi.renderPreview).toHaveBeenCalledWith({
      body: '[widget type="progress" title="Storage" value="72" /]',
      bodyFormat: 'markdown',
    });
  });
});
