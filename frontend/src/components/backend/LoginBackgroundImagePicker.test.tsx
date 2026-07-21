import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';
import { LoginBackgroundImagePicker } from './LoginBackgroundImagePicker';
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

describe('LoginBackgroundImagePicker', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders translated action buttons, not raw i18n keys', () => {
    renderWithRouter(
      <LoginBackgroundImagePicker
        value="/storage/app/content/media/hero.png"
        onChange={vi.fn()}
        label="URL obrázka pozadia"
        help="Pomocný text"
      />
    );

    expect(
      screen.getByRole('button', { name: 'Vybrať z médií' })
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: 'Nahrať z disku' })
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: 'Odstrániť pozadie' })
    ).toBeInTheDocument();
    expect(screen.queryByText('settings.fields.login.backgroundPicker.pickFromMedia')).not.toBeInTheDocument();
  });
});
