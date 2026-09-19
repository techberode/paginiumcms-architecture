import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { TranslateMissingPanel } from './TranslateMissingPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  propose: vi.fn(),
  apply: vi.fn(),
  discard: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  },
}));

vi.mock('../../api/contentTranslations', () => ({
  contentTranslationsApi: {
    propose: mocks.propose,
    apply: mocks.apply,
    discard: mocks.discard,
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

describe('TranslateMissingPanel', () => {
  const onApplied = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    mocks.propose.mockResolvedValue({
      success: true,
      data: {
        id: 'job1',
        type: 'page',
        slug: 'about',
        sourceLocale: 'sk',
        targetLocales: ['en'],
        fields: ['title'],
        sourceRevision: 'rev1',
        provider: 'libretranslate',
        characters: 4,
        locales: {
          en: { status: 'ok', fields: { title: 'Hello' } },
        },
      },
    });
    mocks.apply.mockResolvedValue({
      success: true,
      data: { jobId: 'job1', appliedLocales: ['en'], status: 'draft', published: false, revision: 'rev2' },
    });
  });

  it('requests a proposal and applies it as a draft', async () => {
    renderWithProviders(
      <TranslateMissingPanel
        type="page"
        slug="about"
        sourceLocale="sk"
        missingLocales={['en']}
        sourceRevision="rev1"
        canEdit
        onApplied={onApplied}
      />,
      { locale: 'en' }
    );

    await fastUser.click(screen.getByRole('button', { name: 'Translate missing' }));
    expect(await screen.findByText('Hello')).toBeInTheDocument();

    await fastUser.click(screen.getByRole('button', { name: 'Apply as draft' }));
    await waitFor(() => {
      expect(mocks.apply).toHaveBeenCalledWith('job1');
    });
    expect(onApplied).toHaveBeenCalled();
    expect(mocks.toast.success).toHaveBeenCalled();
  });

  it('hides when there is nothing missing', () => {
    const { container } = renderWithProviders(
      <TranslateMissingPanel
        type="page"
        slug="about"
        sourceLocale="sk"
        missingLocales={[]}
        sourceRevision="rev1"
        canEdit
        onApplied={onApplied}
      />,
      { locale: 'en' }
    );

    expect(container).toBeEmptyDOMElement();
  });
});
