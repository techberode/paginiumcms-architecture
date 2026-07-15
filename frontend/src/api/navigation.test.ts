// frontend/src/api/navigation.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { getNavigation, updateNavigation } from './navigation';

const mocks = vi.hoisted(() => ({
  get: vi.fn(),
  put: vi.fn(),
}));

vi.mock('./client', () => ({
  default: {
    get: mocks.get,
    put: mocks.put,
  },
}));

describe('navigation API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getNavigation returns items array on success', async () => {
    mocks.get.mockResolvedValue({
      success: true,
      data: [{ id: '1', label: 'Home', path: '/', order: 0 }],
    });

    const items = await getNavigation();
    expect(mocks.get).toHaveBeenCalledWith('/api/navigation');
    expect(items).toHaveLength(1);
    expect(items[0].label).toBe('Home');
  });

  it('getNavigation returns empty array on failure', async () => {
    mocks.get.mockResolvedValue({ success: false });
    expect(await getNavigation()).toEqual([]);
  });

  it('updateNavigation sends items payload', async () => {
    const payload = [{ id: '1', label: 'Blog', path: '/blog', order: 1 }];
    mocks.put.mockResolvedValue({ success: true, data: payload });

    const saved = await updateNavigation(payload);
    expect(mocks.put).toHaveBeenCalledWith('/api/admin/navigation', { items: payload });
    expect(saved).toEqual(payload);
  });
});
