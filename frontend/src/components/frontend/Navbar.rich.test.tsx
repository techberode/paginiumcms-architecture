import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { NavDropdownEntry } from './NavMenuVisual';
import type { PublicNavItem } from '../../context/PublicSiteContext';

const navUi = {
  defaultPreviewScale: 1.5,
  maxTooltipWidthPx: 280,
  enableHoverAnimations: false,
};

const richItem: PublicNavItem = {
  id: 'nav-blog',
  label: 'Blog',
  path: '/blog',
  order: 0,
  description: 'Tips and news',
  iconType: 'lucide',
  iconValue: 'Newspaper',
  previewOnHover: true,
  previewScale: 2,
  thumbnailSize: 'md',
};

describe('NavDropdownEntry', () => {
  it('renders label and description', () => {
    render(
      <NavDropdownEntry item={richItem} onNavigate={() => undefined} isActive={false} navUi={navUi} />
    );

    expect(screen.getByText('Blog')).toBeInTheDocument();
    expect(screen.getByText('Tips and news')).toBeInTheDocument();
  });

  it('calls onNavigate when clicked', async () => {
    const user = userEvent.setup();
    const onNavigate = vi.fn();

    render(
      <NavDropdownEntry item={richItem} onNavigate={onNavigate} isActive={false} navUi={navUi} />
    );

    await user.click(screen.getByRole('button', { name: /Blog/i }));
    expect(onNavigate).toHaveBeenCalledWith('/blog');
  });
});
