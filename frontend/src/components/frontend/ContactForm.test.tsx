import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { ContactForm } from './ContactForm';
import { renderWithRouter } from '../../test/renderWithRouter';

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

vi.mock('../../api/contact', () => ({
  submitContactForm: vi.fn(),
}));

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: () => ({
      settings: {
        contact: {
          subjects: 'Predaj\nPodpora',
          allowCustomSubject: true,
        },
      },
      loading: false,
      get: vi.fn(),
      reload: vi.fn(),
    }),
  };
});

describe('ContactForm', () => {
  it('renders subjects from public settings', () => {
    renderWithRouter(<ContactForm />);

    expect(screen.getByLabelText('Predmet')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Predaj' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Podpora' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Vlastný predmet…' })).toBeInTheDocument();
  });
});
