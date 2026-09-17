import { describe, it, expect, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { SitePreviewModal } from './SitePreviewModal';

vi.mock('../frontend/Navbar', () => ({
  Navbar: () => <div data-testid="preview-navbar">Navbar</div>,
}));

vi.mock('../frontend/Footer', () => ({
  Footer: () => <div data-testid="preview-footer">Footer</div>,
}));

vi.mock('../frontend/PageRenderer', () => ({
  PageRenderer: ({ page }: { page: { title: string } }) => <div>{page.title}</div>,
}));

vi.mock('../../api/content', () => ({
  contentApi: {
    renderPreview: vi.fn(async () => '<p>Hello</p>'),
  },
}));

describe('SitePreviewModal', () => {
  it('renders page preview with chrome', async () => {
    renderWithProviders(
      <SitePreviewModal
        open
        onClose={() => undefined}
        draft={{
          type: 'page',
          title: 'O nás',
          slug: 'about',
          content: '# Hello',
          contentFormat: 'markdown',
        }}
      />
    );

    await waitFor(() => {
      expect(screen.getByText('O nás')).toBeInTheDocument();
    });
    expect(screen.getByTestId('preview-navbar')).toBeInTheDocument();
    expect(screen.getByTestId('preview-footer')).toBeInTheDocument();
    expect(screen.getByText('Náhľad stránky')).toBeInTheDocument();

    const modal = screen.getByTestId('site-preview-modal');
    expect(modal.parentElement).toBe(document.body);
    expect(modal).toHaveClass('z-[200]');
    expect(screen.getByTestId('site-preview-toolbar')).toBeInTheDocument();
    expect(screen.getByTestId('site-preview-scale-100')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Zavrieť náhľad' })).toBeInTheDocument();
  });

  it('closes on Escape', () => {
    const onClose = vi.fn();
    renderWithProviders(
      <SitePreviewModal
        open
        onClose={onClose}
        draft={{
          type: 'article',
          title: 'Calendar Test Article',
          slug: 'calendar-test',
          content: 'Hello',
          contentFormat: 'markdown',
        }}
      />
    );

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(onClose).toHaveBeenCalled();
  });

  it('returns null when closed', () => {
    const { container } = renderWithProviders(
      <SitePreviewModal open={false} onClose={() => undefined} draft={null} />
    );
    expect(container).toBeEmptyDOMElement();
  });
});
