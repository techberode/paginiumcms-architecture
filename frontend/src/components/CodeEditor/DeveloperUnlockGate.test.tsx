// frontend/src/components/CodeEditor/DeveloperUnlockGate.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { DeveloperUnlockGate } from './DeveloperUnlockGate';
import * as developerApi from '../../api/developer';

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

vi.mock('../../api/developer', () => ({
  getDeveloperStatus: vi.fn(),
  unlockDeveloperMode: vi.fn(),
}));

describe('DeveloperUnlockGate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows unlock form when developer mode is locked', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: false,
    });

    render(
      <DeveloperUnlockGate>
        <div>Editor content</div>
      </DeveloperUnlockGate>
    );

    expect(await screen.findByText('Developer Mode required')).toBeInTheDocument();
    expect(screen.queryByText('Editor content')).not.toBeInTheDocument();
  });

  it('renders children when unlocked', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: true,
    });

    render(
      <DeveloperUnlockGate>
        <div>Editor content</div>
      </DeveloperUnlockGate>
    );

    expect(await screen.findByText('Editor content')).toBeInTheDocument();
  });

  it('submits dev token unlock request', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: false,
    });
    vi.mocked(developerApi.unlockDeveloperMode).mockResolvedValue(true);

    render(
      <DeveloperUnlockGate>
        <div>Editor content</div>
      </DeveloperUnlockGate>
    );

    await screen.findByText('Developer Mode required');
    fireEvent.change(screen.getByPlaceholderText('Dev token (optional)'), {
      target: { value: 'pagdev_test.token' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Unlock Developer Mode' }));

    expect(await screen.findByText('Editor content')).toBeInTheDocument();
    expect(developerApi.unlockDeveloperMode).toHaveBeenCalledWith({
      totp_code: undefined,
      token: 'pagdev_test.token',
    });
  });
});
