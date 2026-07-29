import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { FooterSocialLinks } from './FooterSocialLinks';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: () => ({
      settings: {
        ui: { openLinksInNewTab: false },
        social: {
          enabled: true,
          links: [
            {
              platform: 'github',
              url: 'https://github.com/example',
              label: 'GitHub',
            },
          ],
        },
      },
      loading: false,
      get: () => undefined,
      reload: async () => undefined,
    }),
  };
});

describe('FooterSocialLinks', () => {
  it('renders social icons when enabled', () => {
    renderWithProviders(<FooterSocialLinks />);
    expect(screen.getByLabelText('GitHub')).toBeInTheDocument();
  });
});
