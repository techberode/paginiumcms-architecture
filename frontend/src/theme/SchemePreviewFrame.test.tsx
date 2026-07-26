import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { SchemePreviewFrame } from '../components/admin/SchemePreviewFrame';
import { COLOR_SCHEME_IDS } from './colorSchemes';

describe('SchemePreviewFrame', () => {
  it('renders wireframe preview for every scheme id', () => {
    for (const schemeId of COLOR_SCHEME_IDS) {
      const { unmount } = render(<SchemePreviewFrame schemeId={schemeId} mode="light" />);
      expect(screen.getByTestId('scheme-preview-frame')).toBeInTheDocument();
      expect(screen.getByText('Header · logo + nav')).toBeInTheDocument();
      expect(screen.getByText('Footer')).toBeInTheDocument();
      unmount();
    }
  });

  it('applies scheme tokens on preview root', () => {
    render(<SchemePreviewFrame schemeId="sunset-rose" mode="dark" />);
    const frame = screen.getByTestId('scheme-preview-frame');
    expect(frame.dataset.scheme).toBe('sunset-rose');
    expect(frame.dataset.theme).toBe('dark');
  });
});
