import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, waitFor } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PageOutlineEditor } from './PageOutlineEditor';
import { shortcodesApi, type ShortcodeDefinition } from '../../api/shortcodes';

vi.mock('../../api/shortcodes', () => ({
  shortcodesApi: {
    list: vi.fn(),
    get: vi.fn(),
  },
}));

describe('PageOutlineEditor', () => {
  beforeEach(() => {
    vi.mocked(shortcodesApi.list).mockResolvedValue([
      { name: 'landing-hero', enabled: true, version: 1, updatedAt: '' },
      { name: 'feature-grid', enabled: true, version: 1, updatedAt: '' },
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
  });
});
