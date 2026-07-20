import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { CompanyInfoPanel, CompanyMapEmbed } from './CompanyInfoPanel';
import { renderWithRouter } from '../../test/renderWithRouter';

vi.mock('../../context/SettingsContext', () => ({
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
}));

describe('CompanyInfoPanel', () => {
  it('renders company details from settings', () => {
    renderWithRouter(<CompanyInfoPanel />);
    expect(screen.getByText('Paginium s.r.o.')).toBeInTheDocument();
    expect(screen.getByText('12345678')).toBeInTheDocument();
    expect(screen.getByText('Bratislava')).toBeInTheDocument();
  });

  it('renders map iframe for safe embed URL', () => {
    renderWithRouter(<CompanyMapEmbed />);
    const frame = screen.getByTitle('Mapa — firemná adresa');
    expect(frame).toHaveAttribute('src', 'https://www.google.com/maps/embed?pb=test');
  });
});
