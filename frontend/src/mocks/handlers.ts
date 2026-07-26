import { http, HttpResponse } from 'msw';

const mockUser = {
  id: 'mock-user-1',
  email: 'admin@example.com',
  name: 'Mock Admin',
  roles: ['ADMIN'],
  twoFactorEnabled: false,
  createdAt: 1710000000,
  updatedAt: 1710000000,
};

const mockPage = {
  id: 'page-home',
  title: 'Home',
  slug: 'home',
  content: '# Home',
  frontMatter: {},
  html: '<h1>Home</h1>',
  status: 'published' as const,
  author: 'Mock Admin',
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

export const handlers = [
  http.get('/api/health', () =>
    HttpResponse.json({ success: true, data: { status: 'healthy', version: '2.0.9' } })
  ),

  http.post('/api/auth/login', async ({ request }) => {
    const body = (await request.json()) as { email?: string; password?: string };
    if (body.email === 'admin@example.com' && body.password === 'StrongP@ssw0rd123!') {
      return HttpResponse.json({ success: true, user: mockUser });
    }
    return HttpResponse.json({ success: false, error: 'Neplatné prihlasovacie údaje' }, { status: 401 });
  }),

  http.get('/api/auth/me', () => HttpResponse.json({ success: true, user: mockUser })),

  http.get('/api/pages', ({ request }) => {
    const url = new URL(request.url);
    const page = Number(url.searchParams.get('page') ?? '0');
    const perPage = Number(url.searchParams.get('per_page') ?? '0');

    if (page > 0 && perPage > 0) {
      return HttpResponse.json({
        success: true,
        data: [mockPage],
        meta: { page, per_page: perPage, total: 1, total_pages: 1 },
      });
    }

    return HttpResponse.json({ success: true, data: [mockPage] });
  }),

  http.get('/api/pages/:slug', ({ params }) => {
    if (params.slug === 'home') {
      return HttpResponse.json({ success: true, data: mockPage });
    }
    return HttpResponse.json({ success: false, error: 'Stránka neexistuje' }, { status: 404 });
  }),

  http.get('/api/media', () =>
    HttpResponse.json({
      success: true,
      data: [
        {
          id: 'media-1',
          path: 'uploads/logo.png',
          fileName: 'logo.png',
          url: '/storage/uploads/logo.png',
          sizeBytes: 1024,
          mimeType: 'image/png',
          uploadedAt: 1710000000,
          altText: 'Logo',
        },
      ],
    })
  ),

  http.get('/api/settings/public', () =>
    HttpResponse.json({
      success: true,
      data: {
        general: { siteName: 'PaginiumCMS', language: 'sk' },
        content: { autoSaveInterval: 60 },
        appearance: {
          colorScheme: 'indigo-classic',
          mode: 'system',
          allowUserToggle: true,
        },
      },
    })
  ),
];
