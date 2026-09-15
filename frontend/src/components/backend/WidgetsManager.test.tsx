import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { WidgetsManager } from './WidgetsManager';
import { renderWithProviders } from '../../test/renderWithProviders';
import type { WidgetTypeDefinition } from '../../api/widgets';

const kpi: WidgetTypeDefinition = {
  id: 'kpi',
  selfClosing: true,
  fields: [{ key: 'title', kind: 'string' }],
  defaults: { title: 'Visitors' },
};

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
    list: vi.fn(async () => [kpi]),
    preview: vi.fn(async () => ({
      success: true,
      data: { html: '<div class="pg-widget-kpi"></div>', markup: '[widget type="kpi" /]' },
    })),
    get: vi.fn(),
    save: vi.fn(),
    delete: vi.fn(),
  },
}));

describe('WidgetsManager', () => {
  it('renders the visual studio', async () => {
    const writeText = vi.fn(async () => undefined);
    Object.defineProperty(navigator, 'clipboard', {
      configurable: true,
      value: { writeText },
    });

    renderWithProviders(<WidgetsManager />);

    await waitFor(() => {
      expect(screen.getByTestId('widgets-manager')).toBeInTheDocument();
      expect(screen.getByTestId('widget-type-kpi')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByTestId('widget-action'));
    await waitFor(() => {
      expect(writeText).toHaveBeenCalled();
    });
  });
});
