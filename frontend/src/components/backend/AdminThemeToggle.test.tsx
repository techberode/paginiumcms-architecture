import { describe, expect, it, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { renderWithProviders } from '../../test/renderWithProviders';
import { ThemeProvider } from '../../context/ThemeContext';
import { AdminThemeToggle } from './AdminThemeToggle';
import { ADMIN_THEME_STORAGE_KEY } from '../../utils/adminTheme';

describe('AdminThemeToggle', () => {
  beforeEach(() => {
    window.localStorage.clear();
    document.documentElement.classList.remove('dark');
  });

  it('switches admin chrome from light to dark and persists the choice', async () => {
    window.localStorage.setItem(ADMIN_THEME_STORAGE_KEY, 'light');

    renderWithProviders(
      <MemoryRouter initialEntries={['/dashboard']}>
        <ThemeProvider>
          <AdminThemeToggle />
        </ThemeProvider>
      </MemoryRouter>
    );

    const button = screen.getByRole('button', { name: 'Zapnúť tmavý režim' });
    expect(button).toHaveAttribute('aria-pressed', 'false');

    fireEvent.click(button);

    await waitFor(() => {
      expect(window.localStorage.getItem(ADMIN_THEME_STORAGE_KEY)).toBe('dark');
      expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
    expect(screen.getByRole('button', { name: 'Zapnúť svetlý režim' })).toHaveAttribute(
      'aria-pressed',
      'true'
    );
  });

  it('switches theme on /categories (workspace route, not /platform/*)', async () => {
    window.localStorage.setItem(ADMIN_THEME_STORAGE_KEY, 'light');

    renderWithProviders(
      <MemoryRouter initialEntries={['/categories']}>
        <ThemeProvider>
          <AdminThemeToggle />
        </ThemeProvider>
      </MemoryRouter>
    );

    fireEvent.click(screen.getByRole('button', { name: 'Zapnúť tmavý režim' }));

    await waitFor(() => {
      expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
  });
});
