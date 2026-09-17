import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, waitFor } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PageOutlineEditor } from './PageOutlineEditor';
import { PageLivePreviewSplit } from './AdminBodyPreviewPanel';
import { shortcodesApi, type ShortcodeDefinition } from '../../api/shortcodes';

vi.mock('../../api/shortcodes', () => ({
  shortcodesApi: {
    list: vi.fn(),
    get: vi.fn(),
  },
}));

vi.mock('../../api/content', () => ({
  contentApi: {
    renderPreview: vi.fn(async () => ''),
  },
}));

describe('PageOutlineEditor', () => {
  beforeEach(() => {
    vi.mocked(shortcodesApi.list).mockResolvedValue([
      { name: 'landing-hero', enabled: true, version: 1, updatedAt: '' },
      { name: 'feature-grid', enabled: true, version: 1, updatedAt: '' },
      { name: 'feature-gallery', enabled: true, version: 1, updatedAt: '' },
    ]);
    vi.mocked(shortcodesApi.get).mockImplementation(async (name: string) => {
      const definition: ShortcodeDefinition =
        name === 'landing-hero'
          ? {
              name,
              version: 1,
              expand: '',
              attrs: {
                title: { type: 'string' },
                subtitle: { type: 'string' },
                cta: { type: 'string' },
                href: { type: 'string' },
                image: { type: 'media', accept: 'image' },
                src: { type: 'media', accept: 'video' },
              },
            }
          : name === 'feature-gallery'
            ? {
                name,
                version: 1,
                expand: '',
                attrs: {
                  title: { type: 'string' },
                  tag: { type: 'string' },
                },
              }
          : {
              name,
              version: 1,
              expand: '',
              attrs: {
                columns: { type: 'enum', options: ['2', '3'] },
              },
            };

      return {
        success: true,
        data: {
          record: { name, enabled: true, version: 1, updatedAt: '' },
          definition,
        },
      };
    });
  });

  it('adds a hero from the palette and writes shortcode markdown', async () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageOutlineEditor value="" onChange={onChange} />
    );

    fireEvent.click(getByTestId('page-outline-add-landing-hero'));

    expect(onChange).toHaveBeenCalled();
    const markdown = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(markdown).toContain('[landing-hero');
    expect(markdown).toContain('title=');

    fireEvent.click(getByTestId('page-outline-card-0'));
    await waitFor(() => {
      expect(getByTestId('page-outline-attr-title')).toBeInTheDocument();
    });

    fireEvent.change(getByTestId('page-outline-attr-title'), { target: { value: 'Studio' } });

    const updated = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(updated).toContain('title="Studio"');
    expect(getByTestId('page-outline-attr-image-pick')).toBeInTheDocument();
    expect(getByTestId('page-outline-attr-src-pick')).toBeInTheDocument();
  });

  it('inserts text, video, and callout from the palette', () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageOutlineEditor value="" onChange={onChange} />
    );

    fireEvent.click(getByTestId('page-outline-add-prose'));
    fireEvent.click(getByTestId('page-outline-add-video'));
    fireEvent.click(getByTestId('page-outline-add-callout-tip'));

    expect(onChange).toHaveBeenCalled();
    const markdown = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(markdown).toContain(':::video');
    expect(markdown).toContain(':::tip');
  });

  it('inserts a hero while the live preview pane is mounted', () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageLivePreviewSplit body="" bodyFormat="markdown">
        <PageOutlineEditor value="" onChange={onChange} />
      </PageLivePreviewSplit>
    );

    expect(getByTestId('page-live-preview-split')).toHaveClass('min-w-0');
    expect(getByTestId('page-live-preview-split')).toHaveClass('isolate');
    fireEvent.click(getByTestId('page-outline-add-landing-hero'));
    expect(onChange).toHaveBeenCalled();
    expect(String(onChange.mock.calls.at(-1)?.[0] ?? '')).toContain('[landing-hero');
  });

  it('inserts the portfolio starter pack as Markdown shortcodes', () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageOutlineEditor value="" onChange={onChange} />
    );

    fireEvent.click(getByTestId('page-outline-empty-starter-portfolio'));
    expect(onChange).toHaveBeenCalled();
    const markdown = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(markdown).toContain('[landing-hero');
    expect(markdown).toContain('[feature-grid');
    expect(markdown).toContain('[cta-banner');
  });

  it('reorders stacked blocks with the move controls', () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageOutlineEditor
        value={'[landing-hero title="A"/]\n\n[cta-banner title="B"/]\n'}
        onChange={onChange}
      />
    );

    fireEvent.click(getByTestId('page-outline-move-down-0'));
    const markdown = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(markdown.indexOf('cta-banner')).toBeGreaterThan(-1);
    expect(markdown.indexOf('cta-banner')).toBeLessThan(markdown.indexOf('landing-hero'));
  });

  it('adds a feature-gallery block that writes the It.65 shortcode', async () => {
    const onChange = vi.fn();
    const { getByTestId } = renderWithProviders(
      <PageOutlineEditor value="" onChange={onChange} />
    );

    fireEvent.click(getByTestId('page-outline-add-feature-gallery'));
    expect(onChange).toHaveBeenCalled();
    const markdown = String(onChange.mock.calls.at(-1)?.[0] ?? '');
    expect(markdown).toContain('[feature-gallery');
    expect(markdown).toContain('title="Selected work"');

    fireEvent.click(getByTestId('page-outline-card-0'));
    await waitFor(() => {
      expect(getByTestId('page-outline-attr-title')).toBeInTheDocument();
      expect(getByTestId('page-outline-attr-tag')).toBeInTheDocument();
    });
    expect(getByTestId('page-outline-attr-tag').closest('label')?.textContent ?? '').toMatch(
      /nálepka|filter sticker/i
    );
  });
});
