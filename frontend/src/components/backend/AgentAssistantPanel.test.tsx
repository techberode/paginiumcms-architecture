import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { AgentAssistantPanel } from './AgentAssistantPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  enqueue: vi.fn(),
  execute: vi.fn(),
  apply: vi.fn(),
  discard: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  },
}));

vi.mock('../../api/agent', () => ({
  agentApi: {
    enqueue: mocks.enqueue,
    execute: mocks.execute,
    apply: mocks.apply,
    discard: mocks.discard,
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

describe('AgentAssistantPanel', () => {
  const onApplied = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    mocks.enqueue.mockResolvedValue({
      success: true,
      data: {
        id: 'run1',
        status: 'queued',
        resourceType: 'article',
        resourceId: 'hello',
        allowedTools: ['content.read', 'seo.suggest_meta'],
        provider: 'ollama',
        model: 'llama3.2',
        tokens: 0,
        steps: 0,
        error: null,
        proposal: null,
      },
    });
    mocks.execute.mockResolvedValue({
      success: true,
      data: {
        id: 'run1',
        status: 'succeeded',
        resourceType: 'article',
        resourceId: 'hello',
        allowedTools: ['content.read', 'seo.suggest_meta'],
        provider: 'ollama',
        model: 'llama3.2',
        tokens: 12,
        steps: 2,
        error: null,
        proposal: {
          id: 'prop1',
          kind: 'seo',
          payload: {
            kind: 'seo',
            fields: {
              seoTitle: 'Hello SEO',
              seoDescription: 'A short description of the article.',
            },
          },
        },
      },
    });
    mocks.apply.mockResolvedValue({
      success: true,
      data: { proposalId: 'prop1', kind: 'seo', published: false, revision: 'rev2' },
    });
  });

  it('requests an SEO proposal and applies it separately', async () => {
    renderWithProviders(
      <AgentAssistantPanel
        type="article"
        slug="hello"
        locale="sk"
        sourceRevision="rev1"
        canEdit
        onApplied={onApplied}
      />,
      { locale: 'en' }
    );

    await fastUser.click(screen.getByRole('button', { name: 'Suggest SEO for this article' }));
    expect(await screen.findByText('Hello SEO')).toBeInTheDocument();
    expect(mocks.enqueue).toHaveBeenCalled();
    expect(mocks.execute).toHaveBeenCalledWith('run1');

    await fastUser.click(screen.getByRole('button', { name: 'Apply selected' }));
    await waitFor(() => {
      expect(mocks.apply).toHaveBeenCalledWith('prop1');
    });
    expect(onApplied).toHaveBeenCalled();
    expect(mocks.toast.success).toHaveBeenCalled();
  });

  it('hides on a new unsaved article', () => {
    const { container } = renderWithProviders(
      <AgentAssistantPanel
        type="article"
        slug="new"
        locale="sk"
        sourceRevision=""
        canEdit
        onApplied={onApplied}
      />,
      { locale: 'en' }
    );
    expect(container).toBeEmptyDOMElement();
  });
});
