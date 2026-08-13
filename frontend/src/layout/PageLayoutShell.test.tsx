import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { PageLayoutShell } from './PageLayoutShell';
import { PAGE_LAYOUT_TEMPLATE_IDS } from './pageLayoutTemplates';

describe('PageLayoutShell', () => {
  it('renders a shell for every layout template id', () => {
    for (const templateId of PAGE_LAYOUT_TEMPLATE_IDS) {
      const { unmount } = render(
        <PageLayoutShell layoutTemplate={templateId} hero={<div data-testid="hero-slot">Hero</div>}>
          <p>Body</p>
        </PageLayoutShell>
      );

      const shell = screen.getByTestId('page-layout-shell');
      expect(shell).toBeInTheDocument();
      expect(shell.getAttribute('data-layout-template')).toBe(templateId);
      expect(screen.getByText('Body')).toBeInTheDocument();

      if (templateId === 'hero-content' || templateId === 'landing') {
        expect(screen.getByTestId('hero-slot')).toBeInTheDocument();
      }

      unmount();
    }
  });

  it('falls back to hero-content for unknown template ids', () => {
    render(
      <PageLayoutShell layoutTemplate="unknown-layout">
        <p>Fallback body</p>
      </PageLayoutShell>
    );

    expect(screen.getByTestId('page-layout-shell').getAttribute('data-layout-template')).toBe('hero-content');
  });
});
