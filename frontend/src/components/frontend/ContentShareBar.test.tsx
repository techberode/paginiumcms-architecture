import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { ContentShareBar } from './ContentShareBar';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('ContentShareBar', () => {
  beforeEach(() => {
    Object.defineProperty(navigator, 'clipboard', {
      configurable: true,
      value: { writeText: vi.fn().mockResolvedValue(undefined) },
    });
  });

  it('renders intent links for the default article networks', () => {
    renderWithProviders(<ContentShareBar title="Hello" surface="article" />);

    expect(screen.getByTestId('content-share-bar')).toBeInTheDocument();
    expect(screen.getByTestId('content-share-facebook')).toHaveAttribute('href');
    expect(screen.getByTestId('content-share-facebook').getAttribute('href')).toContain('facebook.com/sharer');
    expect(screen.getByTestId('content-share-x').getAttribute('href')).toContain('twitter.com/intent/tweet');
  });

  it('hides on pages by default', () => {
    const { container } = renderWithProviders(<ContentShareBar title="About" surface="page" />);
    expect(container.querySelector('[data-testid="content-share-bar"]')).toBeNull();
  });

  it('copies the current URL', async () => {
    renderWithProviders(<ContentShareBar title="Hello" surface="article" />);
    fireEvent.click(screen.getByTestId('content-share-copy'));
    expect(navigator.clipboard.writeText).toHaveBeenCalled();
  });
});
