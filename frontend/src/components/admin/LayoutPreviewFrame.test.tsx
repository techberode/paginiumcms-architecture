import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { LayoutPreviewFrame } from './LayoutPreviewFrame';
import { PAGE_LAYOUT_TEMPLATE_IDS } from '../../layout/pageLayoutTemplates';

describe('LayoutPreviewFrame', () => {
  it('renders a distinct wireframe for every layout template', () => {
    for (const templateId of PAGE_LAYOUT_TEMPLATE_IDS) {
      const { unmount } = render(
        <LayoutPreviewFrame templateId={templateId} schemeId="indigo-classic" mode="light" />
      );
      const frame = screen.getByTestId('layout-preview-frame');
      expect(frame).toBeInTheDocument();
      expect(frame.getAttribute('data-layout-template')).toBe(templateId);
      expect(screen.getByText('Header · logo + nav')).toBeInTheDocument();
      expect(screen.getByText('Footer')).toBeInTheDocument();
      unmount();
    }
  });

  it('falls back to default template for unknown ids', () => {
    render(<LayoutPreviewFrame templateId="not-real" mode="light" />);
    expect(screen.getByTestId('layout-preview-frame').getAttribute('data-layout-template')).toBe(
      'hero-content'
    );
    expect(screen.getByTestId('layout-slot-hero')).toBeInTheDocument();
  });
});
