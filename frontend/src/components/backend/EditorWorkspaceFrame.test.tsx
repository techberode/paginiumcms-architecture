import { describe, expect, it, vi, afterEach } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PageLivePreviewSplit } from './AdminBodyPreviewPanel';
import { EditorWorkspaceFrame } from './EditorWorkspaceFrame';

const frameProps = {
  heading: 'Upraviť stránku',
  title: 'Home',
  titlePlaceholder: 'Titulok',
  canEdit: true,
  saving: false,
  detailsOpen: false,
  details: <p>meta</p>,
  statsLabel: '10 znakov',
  onTitleChange: () => undefined,
  onDetailsOpenChange: () => undefined,
  onSave: () => undefined,
  onCloseEditor: () => undefined,
};

describe('EditorWorkspaceFrame', () => {
  afterEach(() => {
    document.documentElement.classList.remove('dark');
    document.getElementById('root')?.remove();
  });

  it('portals a fullscreen canvas and exits on the toolbar button', () => {
    const onExit = vi.fn();
    renderWithProviders(
      <EditorWorkspaceFrame {...frameProps} onExit={onExit}>
        <p>canvas</p>
      </EditorWorkspaceFrame>
    );

    const frame = screen.getByTestId('editor-workspace');
    expect(frame.parentElement).toBe(document.body);
    expect(frame).toHaveClass('admin-shell', 'bg-admin-canvas', 'z-[190]');
    expect(screen.getByTestId('editor-workspace-canvas')).toBeInTheDocument();
    expect(screen.getByText('canvas')).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('editor-workspace-exit'));
    expect(onExit).toHaveBeenCalled();
  });

  it('inherits the document dark class and focuses the title field', () => {
    document.documentElement.classList.add('dark');
    renderWithProviders(
      <EditorWorkspaceFrame {...frameProps} onExit={() => undefined}>
        <p>canvas</p>
      </EditorWorkspaceFrame>
    );

    expect(screen.getByTestId('editor-workspace')).toHaveClass('dark');
    expect(screen.getByTestId('editor-workspace-title')).toHaveFocus();
  });

  it('fills the remaining viewport when the live preview split is inside', () => {
    renderWithProviders(
      <EditorWorkspaceFrame {...frameProps} onExit={() => undefined}>
        <PageLivePreviewSplit body="" bodyFormat="markdown">
          <p>editor</p>
        </PageLivePreviewSplit>
      </EditorWorkspaceFrame>
    );

    const split = screen.getByTestId('page-live-preview-split');
    expect(split).toHaveAttribute('data-fill-viewport', 'true');
    expect(split).toHaveClass('h-full', 'min-h-0', 'flex-1');
    expect(screen.getByTestId('admin-body-preview-panel').className).not.toMatch(/xl:sticky/);
  });

  it('marks #root inert and restores it on unmount', () => {
    const root = document.createElement('div');
    root.id = 'root';
    document.body.appendChild(root);

    const { unmount } = renderWithProviders(
      <EditorWorkspaceFrame {...frameProps} onExit={() => undefined}>
        <p>canvas</p>
      </EditorWorkspaceFrame>
    );

    expect(root).toHaveAttribute('inert');
    unmount();
    expect(root).not.toHaveAttribute('inert');
  });

  it('exits on Escape when no overlay is open', () => {
    const onExit = vi.fn();
    renderWithProviders(
      <EditorWorkspaceFrame {...frameProps} onExit={onExit}>
        <p>canvas</p>
      </EditorWorkspaceFrame>
    );

    fireEvent.keyDown(window, { key: 'Escape' });
    expect(onExit).toHaveBeenCalled();
  });

  it('does not exit on Escape while the site preview overlay is open', () => {
    const onExit = vi.fn();
    const preview = document.createElement('div');
    preview.setAttribute('data-testid', 'site-preview-modal');
    document.body.appendChild(preview);

    try {
      renderWithProviders(
        <EditorWorkspaceFrame {...frameProps} onExit={onExit}>
          <p>canvas</p>
        </EditorWorkspaceFrame>
      );

      fireEvent.keyDown(window, { key: 'Escape' });
      expect(onExit).not.toHaveBeenCalled();
    } finally {
      preview.remove();
    }
  });

  it('does not exit on Escape while the media picker overlay is open', () => {
    const onExit = vi.fn();
    const picker = document.createElement('div');
    picker.setAttribute('data-testid', 'media-picker-modal');
    document.body.appendChild(picker);

    try {
      renderWithProviders(
        <EditorWorkspaceFrame {...frameProps} onExit={onExit}>
          <p>canvas</p>
        </EditorWorkspaceFrame>
      );

      fireEvent.keyDown(window, { key: 'Escape' });
      expect(onExit).not.toHaveBeenCalled();
    } finally {
      picker.remove();
    }
  });
});
