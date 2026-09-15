import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { LucideIconPicker } from './LucideIconPicker';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('LucideIconPicker', () => {
  it('opens a compact searchable dropdown with icon names', () => {
    const onChange = vi.fn();
    renderWithProviders(<LucideIconPicker value="" onChange={onChange} />);

    expect(screen.getByTestId('nav-lucide-picker')).toBeInTheDocument();
    expect(screen.queryByTestId('nav-lucide-icon-Home')).not.toBeInTheDocument();

    fireEvent.click(screen.getByTestId('nav-lucide-toggle'));
    expect(screen.getByTestId('nav-lucide-menu')).toHaveClass('admin-popover');
    expect(screen.getByTestId('nav-lucide-icon-Home')).toHaveTextContent('Home');
    expect(screen.getByTestId('nav-lucide-icon-Mail')).toHaveTextContent('Mail');

    fireEvent.change(screen.getByTestId('nav-lucide-search'), { target: { value: 'Mail' } });
    expect(screen.getByTestId('nav-lucide-icon-Mail')).toBeInTheDocument();
    expect(screen.queryByTestId('nav-lucide-icon-Home')).not.toBeInTheDocument();

    fireEvent.click(screen.getByTestId('nav-lucide-icon-Mail'));
    expect(onChange).toHaveBeenCalledWith('Mail');
    expect(screen.queryByTestId('nav-lucide-icon-Mail')).not.toBeInTheDocument();
  });
});
