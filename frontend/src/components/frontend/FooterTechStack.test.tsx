import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { FooterTechStack } from './FooterTechStack';
import { renderWithRouter } from '../../test/renderWithRouter';

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: vi.fn(() => ({
      settings: {
        footerTechStack: {
          enabled: true,
          items: [{ id: 'php', label: 'PHP 8.5', url: 'https://www.php.net/', icon: 'php' }],
        },
        ui: { openLinksInNewTab: true },
      },
      loading: false,
      get: vi.fn(),
      reload: vi.fn(),
      applyPreview: vi.fn(),
      clearPreview: vi.fn(),
      clearPreviewGroup: vi.fn(),
      hasUnsavedPreview: false,
    })),
  };
});

describe('FooterTechStack', () => {
  it('renders enabled stack links', () => {
    renderWithRouter(<FooterTechStack />);
    const link = screen.getByRole('link', { name: /PHP 8\.5/i });
    expect(link).toHaveAttribute('href', 'https://www.php.net/');
    expect(link).toHaveAttribute('target', '_blank');
  });
});
