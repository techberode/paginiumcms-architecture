import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { TwoFactorSettings } from './TwoFactorSettings';

vi.mock('../../api/auth', () => ({
  authApi: {
    twoFactor: {
      getStatus: vi.fn(),
      enable: vi.fn(),
      disable: vi.fn(),
      verify: vi.fn(),
      getQrCode: vi.fn(),
    },
  },
}));

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ refreshUser: vi.fn() }),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    info: vi.fn(),
    success: vi.fn(),
    error: vi.fn(),
  }),
}));

import { authApi } from '../../api/auth';

describe('TwoFactorSettings', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows setup button when 2FA is disabled', async () => {
    vi.mocked(authApi.twoFactor.getStatus).mockResolvedValue({ enabled: false, verified: false });

    render(<TwoFactorSettings />);

    expect(await screen.findByRole('button', { name: /Začať nastavenie 2FA/i })).toBeInTheDocument();
  });

  it('loads QR when enabled but not verified', async () => {
    vi.mocked(authApi.twoFactor.getStatus).mockResolvedValue({ enabled: true, verified: false });
    vi.mocked(authApi.twoFactor.getQrCode).mockResolvedValue({
      qr_code: 'data:image/svg+xml;base64,PHN2Zy8+',
      provisioning_uri: 'otpauth://totp/test',
    });

    render(<TwoFactorSettings />);

    await waitFor(() => {
      expect(authApi.twoFactor.getQrCode).toHaveBeenCalled();
    });
    expect(await screen.findByAltText(/QR kód pre Google Authenticator/i)).toBeInTheDocument();
  });
});
