import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { WidgetInsertModal } from './WidgetInsertModal';
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
  },
}));

describe('WidgetInsertModal', () => {
  it('inserts markup and closes', async () => {
    const onInsert = vi.fn();
    const onClose = vi.fn();
    renderWithProviders(<WidgetInsertModal open onClose={onClose} onInsert={onInsert} />);

    await waitFor(() => {
      expect(screen.getByTestId('widget-type-kpi')).toBeInTheDocument();
    });

    fireEvent.click(screen.getByTestId('widget-action'));
    expect(onInsert).toHaveBeenCalled();
    expect(onClose).toHaveBeenCalled();
  });
});
