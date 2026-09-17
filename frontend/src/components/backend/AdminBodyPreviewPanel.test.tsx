import { describe, expect, it, vi, beforeEach } from 'vitest';
import { waitFor } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AdminBodyPreviewPanel } from './AdminBodyPreviewPanel';
import { contentApi } from '../../api/content';
import { THEME_STUDIO_PREVIEW_SANDBOX } from '../../utils/themeStudioPreview';

vi.mock('../../api/content', () => ({
  contentApi: {
    renderPreview: vi.fn(),
  },
}));

describe('AdminBodyPreviewPanel', () => {
  beforeEach(() => {
    vi.mocked(contentApi.renderPreview).mockReset();
    vi.mocked(contentApi.renderPreview).mockResolvedValue('<h1>Prehľad</h1>');
  });

  it('renders expanded HTML in an empty-sandbox iframe', async () => {
    const { getByTestId } = renderWithProviders(
      <AdminBodyPreviewPanel
        body={'[landing-hero title="Studio" /]'}
        bodyFormat="markdown"
        sandbox
        debounceMs={0}
      />
    );

    const frame = await waitFor(() => getByTestId('admin-body-preview-frame'));
    expect(frame.tagName).toBe('IFRAME');
    expect(frame).toHaveAttribute('sandbox', THEME_STUDIO_PREVIEW_SANDBOX);
    expect(frame).toHaveClass('min-w-0');
    expect(contentApi.renderPreview).toHaveBeenCalledWith({
      body: '[landing-hero title="Studio" /]',
      bodyFormat: 'markdown',
    });
  });

  it('shows an empty hint without calling the preview API', () => {
    const { getByTestId, queryByTestId } = renderWithProviders(
      <AdminBodyPreviewPanel body="   " bodyFormat="markdown" sandbox debounceMs={0} />
    );

    expect(getByTestId('admin-body-preview-panel')).toBeInTheDocument();
    expect(queryByTestId('admin-body-preview-frame')).toBeNull();
    expect(contentApi.renderPreview).not.toHaveBeenCalled();
  });

  it('opens the full page preview from the pane chrome', async () => {
    const onOpenFullPreview = vi.fn();
    const { getByTestId } = renderWithProviders(
      <AdminBodyPreviewPanel
        body={'[landing-hero title="Studio" /]'}
        bodyFormat="markdown"
        sandbox
        debounceMs={0}
        onOpenFullPreview={onOpenFullPreview}
      />
    );

    await waitFor(() => getByTestId('admin-body-preview-frame'));
    getByTestId('admin-body-preview-open').click();
    expect(onOpenFullPreview).toHaveBeenCalled();
  });
});
