import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
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

describe('SitePreviewModal', () => {
  it('renders page preview with chrome', () => {
    render(
      <SitePreviewModal
        open
        onClose={() => undefined}
        draft={{
          type: 'page',
          title: 'O nás',
          slug: 'about',
          content: '# Hello',
        }}
      />
    );

    expect(screen.getByText('O nás')).toBeInTheDocument();
    expect(screen.getByTestId('preview-navbar')).toBeInTheDocument();
    expect(screen.getByTestId('preview-footer')).toBeInTheDocument();
    expect(screen.getByText('Náhľad stránky')).toBeInTheDocument();
  });

  it('returns null when closed', () => {
    const { container } = render(
      <SitePreviewModal open={false} onClose={() => undefined} draft={null} />
    );
    expect(container).toBeEmptyDOMElement();
  });
});
