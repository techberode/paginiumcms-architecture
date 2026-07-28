import { describe, it, expect, vi } from 'vitest';
import { renderToStaticMarkup } from 'react-dom/server';
import { screen } from '@testing-library/react';
import { CompanyInfoPanel, CompanyMapEmbed } from './CompanyInfoPanel';
import { renderWithRouter } from '../../test/renderWithRouter';
import { TestI18nProvider } from '../../context/I18nContext';

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: () => ({
      settings: {
        company: {
          showOnContactPage: true,
          name: 'Paginium s.r.o.',
          ico: '12345678',
          address: 'Bratislava',
          mapEmbedUrl: 'https://www.google.com/maps/embed?pb=test',
        },
      },
      loading: false,
      get: vi.fn(),
      reload: vi.fn(),
    }),
  };
});

describe('CompanyInfoPanel', () => {
  it('renders company details from settings', () => {
    renderWithRouter(<CompanyInfoPanel />);
    expect(screen.getByText('Paginium s.r.o.')).toBeInTheDocument();
    expect(screen.getByText('12345678')).toBeInTheDocument();
    expect(screen.getByText('Bratislava')).toBeInTheDocument();
  });

  it('renders map iframe markup for safe embed URL', () => {
    const html = renderToStaticMarkup(
      <TestI18nProvider locale="sk">
        <CompanyMapEmbed />
      </TestI18nProvider>
    );

    expect(html).toContain('https://www.google.com/maps/embed?pb=test');
    expect(html).toContain('iframe');
  });
});
