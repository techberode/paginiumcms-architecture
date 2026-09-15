import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { WidgetCustomEditor } from './WidgetCustomEditor';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}));

vi.mock('../../api/widgets', () => ({
  widgetsApi: {
    list: vi.fn(async () => []),
    save: vi.fn(async () => ({ success: true, data: { widget: { id: 'promo' } } })),
    get: vi.fn(),
    delete: vi.fn(),
  },
}));

describe('WidgetCustomEditor', () => {
  it('renders the custom widget form', async () => {
    renderWithProviders(<WidgetCustomEditor onChanged={() => undefined} />);
    await waitFor(() => {
      expect(screen.getByTestId('widget-custom-editor')).toBeInTheDocument();
    });
    fireEvent.change(screen.getByTestId('widget-custom-id'), { target: { value: 'promo' } });
    expect(screen.getByTestId('widget-custom-expand')).toBeInTheDocument();
  });

  it('keeps the field-name input mounted while typing', async () => {
    renderWithProviders(<WidgetCustomEditor onChanged={() => undefined} />);
    const input = await screen.findByTestId('widget-field-key-0');
    input.focus();
    fireEvent.change(input, { target: { value: 'headline' } });
    expect(screen.getByTestId('widget-field-key-0')).toHaveValue('headline');
    expect(screen.getByTestId('widget-field-key-0')).toHaveFocus();
  });
});
