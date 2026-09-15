import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { WidgetPicker } from './WidgetPicker';
import { renderWithProviders } from '../../test/renderWithProviders';
import type { WidgetTypeDefinition } from '../../api/widgets';

const kpi: WidgetTypeDefinition = {
  id: 'kpi',
  selfClosing: true,
  fields: [
    { key: 'title', kind: 'string' },
    { key: 'value', kind: 'string' },
    { key: 'tone', kind: 'tone', options: ['primary', 'success', 'warn', 'muted'] },
  ],
  defaults: { title: 'Visitors', value: '12k', tone: 'primary' },
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
      data: { html: '<div class="pg-widget-kpi">Visitors</div>', markup: '[widget type="kpi" /]' },
    })),
  },
}));

describe('WidgetPicker', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('loads catalog and reports markup to the action', async () => {
    const onAction = vi.fn();
    renderWithProviders(<WidgetPicker actionLabel="Copy" onAction={onAction} />);

    await waitFor(() => {
      expect(screen.getByTestId('widget-type-kpi')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByTestId('widget-action'));
    expect(onAction).toHaveBeenCalledWith('[widget type="kpi" title="Visitors" value="12k" tone="primary" /]');
  });
});
