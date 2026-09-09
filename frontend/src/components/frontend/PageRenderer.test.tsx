import { describe, expect, it } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import type { Page } from '../../api/types';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PageRenderer } from './PageRenderer';

function landingPage(overrides: Partial<Page> = {}): Page {
  return {
    id: 'paginium-cms',
    title: 'PaginiumCMS',
    slug: 'paginium-cms',
    content: '[showcase-hero title="Hello"/]',
    html: '<section class="pg-showcase-hero pg-reveal"><div class="pg-showcase-hero-inner"><h1>Hello</h1></div></section>',
    frontMatter: {
      layoutTemplate: 'landing',
      template: 'landing',
      seoImage: '/storage/app/content/media/hero.jpg',
    },
    ogImage: '/storage/app/content/media/hero.jpg',
    status: 'published',
    author: 'Paginium',
    createdAt: '2026-09-09T00:00:00+00:00',
    updatedAt: '2026-09-09T00:00:00+00:00',
    template: 'landing',
    layoutTemplate: 'landing',
    ...overrides,
  };
}

describe('PageRenderer landing hero', () => {
  it('applies SEO ogImage as landing hero background and wraps landing shell', () => {
    const { container } = renderWithProviders(
      <MemoryRouter>
        <PageRenderer page={landingPage()} />
      </MemoryRouter>
    );

    const landing = container.querySelector('.pg-landing-content');
    expect(landing).not.toBeNull();
    expect(landing?.getAttribute('data-has-hero-image')).toBe('true');
    expect((landing as HTMLElement).style.getPropertyValue('--pg-hero-image')).toContain(
      '/storage/app/content/media/hero.jpg'
    );
    expect(container.querySelector('[data-layout-template="landing"]')).toBeInTheDocument();
    expect(container.querySelector('.pg-landing-seo-hero img')?.getAttribute('src')).toContain(
      '/storage/app/content/media/hero.jpg'
    );
  });

  it('uses landing shell for home slug so showcase-hero CSS applies', () => {
    const { container } = renderWithProviders(
      <MemoryRouter>
        <PageRenderer
          page={landingPage({
            id: 'home',
            slug: 'home',
            template: 'home',
            frontMatter: {
              layoutTemplate: 'landing',
              template: 'home',
              seoImage: '/storage/app/content/media/hero.jpg',
            },
          })}
        />
      </MemoryRouter>
    );

    expect(container.querySelector('[data-layout-template="landing"]')).toBeInTheDocument();
    expect(container.querySelector('.public-hero')).toBeNull();
  });

  it('renders the SEO image inside the home public hero', () => {
    const { container } = renderWithProviders(
      <MemoryRouter>
        <PageRenderer
          page={landingPage({
            slug: 'paginium-cms',
            template: 'home',
            layoutTemplate: 'hero-content',
            frontMatter: {
              template: 'home',
              layoutTemplate: 'hero-content',
              seoImage: '/storage/app/content/media/uploads/hero.png',
            },
            ogImage: '/storage/app/content/media/uploads/hero.png',
          })}
        />
      </MemoryRouter>
    );

    const heroImg = container.querySelector('.public-hero img');
    expect(heroImg).not.toBeNull();
    expect(heroImg?.getAttribute('src')).toContain(
      '/storage/app/content/media/uploads/hero.png'
    );
  });

  it('does not mark landing hero when no SEO image is set', () => {
    const { container } = renderWithProviders(
      <MemoryRouter>
        <PageRenderer
          page={landingPage({
            ogImage: '',
            frontMatter: { layoutTemplate: 'landing', template: 'landing' },
          })}
        />
      </MemoryRouter>
    );

    const landing = container.querySelector('.pg-landing-content');
    expect(landing?.getAttribute('data-has-hero-image')).toBe('false');
    expect(container.querySelector('.pg-landing-seo-hero')).toBeNull();
  });
});
