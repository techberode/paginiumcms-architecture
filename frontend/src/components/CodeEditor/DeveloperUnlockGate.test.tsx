// frontend/src/components/CodeEditor/DeveloperUnlockGate.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { DeveloperUnlockGate } from './DeveloperUnlockGate';
import * as developerApi from '../../api/developer';
import { renderWithRouter } from '../../test/renderWithRouter';
import { fastUser } from '../../test/userEvent';

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
  lockDeveloperMode: vi.fn(),
}));

function renderGate(children: React.ReactNode = <div>Editor content</div>) {
  return renderWithRouter(<DeveloperUnlockGate>{children}</DeveloperUnlockGate>);
}

describe('DeveloperUnlockGate', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows unlock form when developer mode is locked', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: false,
    });

    renderGate();

    expect(await screen.findByText(/Odomknutie Developer Mode/i)).toBeInTheDocument();
    expect(screen.queryByText('Editor content')).not.toBeInTheDocument();
  });

  it('renders children when unlocked', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: true,
    });

    renderGate();

    expect(await screen.findByText('Editor content')).toBeInTheDocument();
  });

  it('submits dev token unlock request', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: true,
      unlocked: false,
    });
    vi.mocked(developerApi.unlockDeveloperMode).mockResolvedValue({ success: true });

    renderGate();
    const user = fastUser;

    await screen.findByText(/Odomknutie Developer Mode/i);
    await user.type(screen.getByPlaceholderText('Dev token (voliteľné)'), 'pagdev_test.token');
    await user.click(screen.getByRole('button', { name: /Odomknúť Developer Mode/i }));

    await waitFor(() => {
      expect(developerApi.unlockDeveloperMode).toHaveBeenCalledWith({
        totp_code: undefined,
        token: 'pagdev_test.token',
      });
    });
  });

  it('shows config hint when developer mode feature is disabled', async () => {
    vi.mocked(developerApi.getDeveloperStatus).mockResolvedValue({
      feature_available: false,
      unlocked: false,
    });

    renderGate();

    expect(await screen.findByText(/Developer Mode nie je povolený/i)).toBeInTheDocument();
  });
});
