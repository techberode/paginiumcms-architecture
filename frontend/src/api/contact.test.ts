// frontend/src/api/contact.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { submitContactForm } from './contact';

const mocks = vi.hoisted(() => ({
  post: vi.fn(),
}));

vi.mock('./client', () => ({
  default: {
    post: mocks.post,
  },
}));

describe('contact API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('submitContactForm returns ok with id on success', async () => {
    mocks.post.mockResolvedValue({
      success: true,
      data: { id: 'msg_123' },
      message: 'Thank you',
    });

    const result = await submitContactForm({
      name: 'Jane',
      email: 'jane@example.com',
      subject: 'Technická podpora',
      message: 'Hello from the contact form.',
    });

    expect(mocks.post).toHaveBeenCalledWith('/api/contact', {
      name: 'Jane',
      email: 'jane@example.com',
      subject: 'Technická podpora',
      message: 'Hello from the contact form.',
    });
    expect(result).toEqual({ ok: true, id: 'msg_123', message: 'Thank you' });
  });

  it('submitContactForm returns error on failure', async () => {
    mocks.post.mockResolvedValue({ success: false, error: 'Validation failed' });
    const result = await submitContactForm({
      name: 'Jane',
      email: 'jane@example.com',
      message: 'Too short',
    });
    expect(result).toEqual({ ok: false, error: 'Validation failed' });
  });
});
