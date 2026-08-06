import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { screen, fireEvent } from '@testing-library/react';
import { BackToTopButton } from './BackToTopButton';
import { useRef } from 'react';
import { renderWithProviders } from '../../test/renderWithProviders';

function ContainerHarness() {
  const scrollContainerRef = useRef<HTMLDivElement>(null);

  return (
    <div
      ref={scrollContainerRef}
      data-testid="scroll-root"
      style={{ height: 200, overflow: 'auto' }}
    >
      <div style={{ height: 800 }} />
      <BackToTopButton scrollContainerRef={scrollContainerRef} variant="admin" />
    </div>
  );
}

describe('BackToTopButton', () => {
  beforeEach(() => {
    vi.spyOn(window, 'scrollY', 'get').mockReturnValue(500);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('scrolls window to top by default', () => {
    const scrollTo = vi.fn();
    vi.spyOn(window, 'scrollTo').mockImplementation(scrollTo);

    renderWithProviders(<BackToTopButton />);

    fireEvent.click(screen.getByRole('button', { name: /späť hore|back to top/i }));

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
  });

  it('scrolls container when scrollContainerRef is provided', () => {
    const scrollTo = vi.fn();
    renderWithProviders(<ContainerHarness />);

    const root = screen.getByTestId('scroll-root');
    Object.defineProperty(root, 'scrollTop', { value: 500, configurable: true });
    root.scrollTo = scrollTo;
    fireEvent.scroll(root);

    fireEvent.click(screen.getByRole('button', { name: /späť hore|back to top/i }));

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
  });
});
