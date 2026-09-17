import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { screen, fireEvent } from '@testing-library/react';
import { AdminFormActions } from './AdminFormActions';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('AdminFormActions', () => {
  beforeEach(() => {
    class ImmediateHideObserver {
      callback: IntersectionObserverCallback;

      constructor(callback: IntersectionObserverCallback) {
        this.callback = callback;
      }

      observe(): void {
        this.callback(
          [{ isIntersecting: false } as IntersectionObserverEntry],
          this as unknown as IntersectionObserver
        );
      }

      unobserve(): void {}
      disconnect(): void {}
      takeRecords(): IntersectionObserverEntry[] {
        return [];
      }
    }

    vi.stubGlobal('IntersectionObserver', ImmediateHideObserver);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders apply + save and mirrors them into the floating dock', () => {
    const onApply = vi.fn();
    const onSave = vi.fn();

    renderWithProviders(
      <AdminFormActions
        showApply
        onApply={onApply}
        onSave={onSave}
        applyLabel="Použiť"
        saveLabel="Uložiť zmeny"
        saveTestId="inline-save"
      />
    );

    fireEvent.click(screen.getAllByRole('button', { name: /použiť/i })[0]);
    fireEvent.click(screen.getByTestId('inline-save'));

    expect(onApply).toHaveBeenCalledTimes(1);
    expect(onSave).toHaveBeenCalledTimes(1);
    expect(screen.getByTestId('admin-floating-actions')).toBeInTheDocument();
    expect(screen.getByTestId('admin-floating-actions').querySelectorAll('button')).toHaveLength(2);
  });

  it('does not float a save dock when the inline bar is inside a hidden card', () => {
    renderWithProviders(
      <div className="hidden">
        <AdminFormActions onSave={vi.fn()} saveLabel="Uložiť zmeny" saveTestId="hidden-save" />
      </div>
    );

    expect(screen.queryByTestId('admin-floating-actions')).not.toBeInTheDocument();
  });
});
