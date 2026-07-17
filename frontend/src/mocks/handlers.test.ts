import { describe, it, expect, beforeAll, afterAll, afterEach } from 'vitest';
import { server } from './server';

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

describe('MSW API contract handlers', () => {
  it('health returns success envelope', async () => {
    const res = await fetch('/api/health');
    const json = await res.json();

    expect(res.status).toBe(200);
    expect(json.success).toBe(true);
    expect(json.data).toBeDefined();
  });

  it('login returns legacy user envelope', async () => {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: 'admin@example.com', password: 'StrongP@ssw0rd123!' }),
    });
    const json = await res.json();

    expect(json.success).toBe(true);
    expect(json.user).toMatchObject({ email: 'admin@example.com' });
  });

  it('paginated pages include meta', async () => {
    const res = await fetch('/api/pages?page=1&per_page=10');
    const json = await res.json();

    expect(json.success).toBe(true);
    expect(Array.isArray(json.data)).toBe(true);
    expect(json.meta).toMatchObject({ page: 1, per_page: 10, total: 1, total_pages: 1 });
  });

  it('unknown page returns error envelope', async () => {
    const res = await fetch('/api/pages/unknown');
    const json = await res.json();

    expect(res.status).toBe(404);
    expect(json.success).toBe(false);
    expect(typeof json.error).toBe('string');
  });
});
