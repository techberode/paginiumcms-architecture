import { describe, expect, it, vi, afterEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { MailInboxView } from './MailInboxView';
import { renderWithProviders } from '../../test/renderWithProviders';
import { mailApi } from '../../api/mail';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../api/mail', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../api/mail')>();
  return {
    ...actual,
    mailApi: {
      status: vi.fn(async () => ({
        enabled: true,
        configured: true,
        siteHost: 'paginium.test',
        mailbox: 'editor@paginium.test',
        mailboxAllowed: true,
        hasPassword: true,
        canReadAll: false,
        smtpEnabled: true,
        canSend: true,
        accounts: [{ mailbox: 'editor@paginium.test', primary: true, hasPassword: true }],
      })),
      savePassword: vi.fn(),
      addAccount: vi.fn(async () => ({ success: true, data: { added: true } })),
      removeAccount: vi.fn(),
      selectAccount: vi.fn(async () => ({ success: true, data: { mailbox: 'info@paginium.test' } })),
      folders: vi.fn(async () => [
        { name: 'Junk', spam: true },
        { name: 'Trash', spam: false },
        { name: 'INBOX', spam: false },
      ]),
      createFolder: vi.fn(),
      deleteFolder: vi.fn(),
      messages: vi.fn(async () => [
        {
          uid: 1,
          subject: 'Welcome',
          from: 'zebra@paginium.test',
          date: '2026-09-15',
          flags: [],
          tags: ['intro'],
          snippet: 'Thanks',
          seen: false,
          flagged: false,
        },
        {
          uid: 2,
          subject: 'Alpha note',
          from: 'alpha@paginium.test',
          date: '2026-09-01',
          flags: [],
          tags: [],
          snippet: 'Hello',
          seen: true,
          flagged: false,
        },
      ]),
      message: vi.fn(async () => ({
        uid: 1,
        subject: 'Welcome',
        from: 'noreply@paginium.test',
        date: '2026-09-15',
        flags: [],
        tags: ['intro'],
        snippet: 'Thanks',
        body: 'Thanks for hosting with us.',
        seen: true,
        flagged: false,
      })),
      setTags: vi.fn(),
      changeFlags: vi.fn(async () => ({ success: true, data: { updated: true } })),
      hide: vi.fn(async () => ({ success: true, data: { hidden: true } })),
      unhide: vi.fn(),
      moveSpam: vi.fn(),
      blockSender: vi.fn(async () => ({ success: true, data: { blocked: 'zebra@paginium.test', moved: true } })),
      autocleanSpam: vi.fn(async () => ({ success: true, data: { purged: 0 } })),
      blockedSenders: vi.fn(async () => ({ mailbox: 'editor@paginium.test', blocked: ['spam@evil.test'] })),
      unblockSender: vi.fn(async () => ({ success: true, data: { unblocked: 'spam@evil.test', removed: true } })),
      signature: vi.fn(async () => ({
        mailbox: 'editor@paginium.test',
        templates: [{ id: 'classic' }, { id: 'minimal' }],
        prefs: { enabled: false, templateId: 'classic', overrides: {} },
        fields: {
          displayName: 'Editor',
          jobTitle: '',
          phone: '',
          contactEmail: 'editor@paginium.test',
          bio: '',
          companyName: '',
          website: '',
          avatarUrl: '',
        },
        previewHtml: '<div>preview</div>',
      })),
      saveSignature: vi.fn(),
      importSignatureProfile: vi.fn(),
      send: vi.fn(async () => ({ success: true, data: { sent: true } })),
      saveDraft: vi.fn(async () => ({ success: true, data: { uid: -101 } })),
      deleteLocalMessage: vi.fn(async () => ({ success: true, data: { deleted: true } })),
    },
  };
});

describe('MailInboxView', () => {
  afterEach(() => {
    window.localStorage.clear();
  });

  const renderMail = () =>
    renderWithProviders(
      <MemoryRouter>
        <MailInboxView />
      </MemoryRouter>
    );

  it('hides the mailbox password field after a successful login', async () => {
    renderMail();
    expect(await screen.findByTestId('mail-folder-INBOX')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-password')).not.toBeInTheDocument();
    expect(screen.queryByTestId('mail-save-password')).not.toBeInTheDocument();
    expect(screen.getByTestId('mail-account')).toHaveTextContent('editor@paginium.test');
    expect(screen.getByTestId('mail-folder-INBOX')).toHaveClass('mail-nav-on');
  });

  it('asks for the mailbox password only before login', async () => {
    vi.mocked(mailApi.status).mockResolvedValueOnce({
      enabled: true,
      configured: true,
      siteHost: 'paginium.test',
      mailbox: 'editor@paginium.test',
      mailboxAllowed: true,
      hasPassword: false,
      canReadAll: false,
      smtpEnabled: true,
      canSend: true,
      accounts: [{ mailbox: 'editor@paginium.test', primary: true, hasPassword: false }],
    });
    renderMail();
    expect(await screen.findByTestId('mail-password')).toBeVisible();
    expect(screen.getByTestId('mail-save-password')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-folder-INBOX')).not.toBeInTheDocument();
  });

  it('renders folders and a readable new-folder field', async () => {
    renderMail();
    expect(await screen.findByTestId('mail-inbox')).toBeInTheDocument();
    expect(await screen.findByTestId('mail-folder-INBOX')).toBeInTheDocument();
    expect(await screen.findByTestId('mail-row-1')).toHaveTextContent('Welcome');
    const folderInput = await screen.findByTestId('mail-new-folder');
    expect(folderInput).toBeVisible();
    expect(folderInput).toHaveAttribute('placeholder');
    expect(mailApi.folders).toHaveBeenCalled();
  });

  it('opens a message pane', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    await waitFor(() => {
      expect(screen.getByText('Thanks for hosting with us.')).toBeInTheDocument();
    });
    expect(await screen.findByTestId('mail-hide')).toBeInTheDocument();
    expect(await screen.findByTestId('mail-tag-input')).toBeVisible();
  });

  it('removes a label from an open message', async () => {
    vi.mocked(mailApi.changeFlags).mockClear();
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    await screen.findByTestId('mail-message-labels');
    fireEvent.click(await screen.findByTestId('mail-tag-remove-intro'));
    await waitFor(() => {
      expect(mailApi.changeFlags).toHaveBeenCalledWith('INBOX', 1, [], ['intro']);
    });
  });

  it('shows signature panel for active mailbox', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-signature-toggle'));
    expect(await screen.findByTestId('mail-signature-panel')).toBeInTheDocument();
    expect(screen.getByTestId('mail-signature-field-displayName')).toHaveValue('Editor');
  });

  it('shows blocked senders panel and can unblock', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-blocked-toggle'));
    expect(await screen.findByTestId('mail-blocked-panel')).toBeInTheDocument();
    expect(screen.getByText('spam@evil.test')).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('mail-unblock-spam@evil.test'));
    await waitFor(() => {
      expect(mailApi.unblockSender).toHaveBeenCalledWith('spam@evil.test');
    });
  });

  it('requests mail body with remote images off by default', async () => {
    localStorage.clear();
    vi.mocked(mailApi.message).mockClear();
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    await waitFor(() => {
      expect(mailApi.message).toHaveBeenCalled();
    });
    expect(vi.mocked(mailApi.message).mock.calls[0]?.[2]).toBe(false);
  });

  it('loads remote images when the sender is trusted for this mailbox', async () => {
    localStorage.setItem(
      'paginium.mail.trustedImageSenders:editor@paginium.test',
      JSON.stringify(['zebra@paginium.test'])
    );
    vi.mocked(mailApi.message).mockClear();
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    await waitFor(() => {
      expect(vi.mocked(mailApi.message).mock.calls[0]?.[2]).toBe(true);
    });
  });

  it('remembers sender when showing remote images once', async () => {
    localStorage.clear();
    vi.mocked(mailApi.message).mockResolvedValueOnce({
      uid: 1,
      subject: 'Newsletter',
      from: 'News <news@partner.com>',
      date: '2026-09-15',
      flags: [],
      tags: [],
      snippet: 'Hello',
      html: '<html><body><img data-pg-blocked-src="https://track.example/p.gif" src="data:image/gif;base64,R0l"></body></html>',
      remoteImagesBlocked: true,
      seen: true,
      flagged: false,
    });
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    fireEvent.click(await screen.findByTestId('mail-load-remote-images'));
    expect(localStorage.getItem('paginium.mail.trustedImageSenders:editor@paginium.test')).toContain(
      'news@partner.com'
    );
  });

  it('offers load remote images when the server blocked tracking pixels', async () => {
    vi.mocked(mailApi.message).mockResolvedValueOnce({
      uid: 1,
      subject: 'Newsletter',
      from: 'news@paginium.test',
      date: '2026-09-15',
      flags: [],
      tags: [],
      snippet: 'Hello',
      html: '<html><body><img data-pg-blocked-src="https://track.example/p.gif" src="data:image/gif;base64,R0l"></body></html>',
      remoteImagesBlocked: true,
      seen: true,
      flagged: false,
    });
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    expect(await screen.findByTestId('mail-load-remote-images')).toBeInTheDocument();
  });

  it('renders HTML mail in a sandboxed iframe', async () => {
    vi.mocked(mailApi.message).mockResolvedValueOnce({
      uid: 1,
      subject: 'Monitoring report',
      from: 'noreply@paginium.test',
      date: '2026-09-15',
      flags: [],
      tags: [],
      snippet: 'PaginiumCMS',
      body: 'PaginiumCMS',
      html: '<html><body><h1>PaginiumCMS</h1></body></html>',
      seen: true,
      flagged: false,
    });
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    const frame = await screen.findByTestId('mail-html-frame');
    expect(frame.tagName).toBe('IFRAME');
    expect(frame).toHaveAttribute('sandbox', 'allow-popups allow-popups-to-escape-sandbox');
    expect(frame).toHaveAttribute('srcdoc', expect.stringContaining('PaginiumCMS'));
  });

  it('explains a mailbox outside the CMS domain', async () => {
    vi.mocked(mailApi.status).mockResolvedValueOnce({
      enabled: true,
      configured: true,
      siteHost: '',
      mailbox: 'admin@gmail.com',
      mailboxAllowed: false,
      hasPassword: false,
      canReadAll: true,
      smtpEnabled: true,
      canSend: false,
      accounts: [{ mailbox: 'admin@gmail.com', primary: true, hasPassword: false }],
    });
    renderMail();
    expect(await screen.findByTestId('mail-domain-settings')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-save-password')).not.toBeInTheDocument();
  });

  it('composes a new message from the working mailbox', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-compose'));
    fireEvent.change(screen.getByTestId('mail-compose-to'), { target: { value: 'guest@example.com' } });
    fireEvent.change(screen.getByTestId('mail-compose-subject'), { target: { value: 'Hello' } });
    fireEvent.change(screen.getByTestId('mail-compose-body'), { target: { value: 'Hi there' } });
    fireEvent.click(screen.getByTestId('mail-send'));
    await waitFor(() => {
      expect(mailApi.send).toHaveBeenCalledWith({ to: 'guest@example.com', subject: 'Hello', body: 'Hi there' });
    });
  });

  it('adds another domain mailbox', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-add-account-toggle'));
    fireEvent.change(await screen.findByTestId('mail-add-account-email'), { target: { value: 'info@paginium.test' } });
    fireEvent.change(screen.getByTestId('mail-add-account-password'), { target: { value: 'secret' } });
    fireEvent.click(screen.getByTestId('mail-add-account-save'));
    await waitFor(() => {
      expect(mailApi.addAccount).toHaveBeenCalledWith('info@paginium.test', 'secret');
    });
  });

  it('opens mail folder drawer from mobile menu control', async () => {
    renderMail();
    const app = await screen.findByTestId('mail-inbox');
    const mailApp = app.querySelector('.mail-app');
    expect(mailApp).toBeTruthy();
    fireEvent.click(await screen.findByTestId('mail-nav-toggle'));
    expect(mailApp?.classList.contains('mail-app-nav-drawer-open')).toBe(true);
    fireEvent.click(screen.getByTestId('mail-nav-backdrop'));
    expect(mailApp?.classList.contains('mail-app-nav-drawer-open')).toBe(false);
  });

  it('opens message in main pane and returns to list with back control', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    expect(await screen.findByTestId('mail-message-view')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-row-1')).not.toBeInTheDocument();
    fireEvent.click(screen.getByTestId('mail-back-to-list'));
    expect(await screen.findByTestId('mail-row-1')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-message-view')).not.toBeInTheDocument();
  });

  it('prefills a reply from the opened message', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    fireEvent.click(await screen.findByTestId('mail-reply'));
    expect(screen.getByTestId('mail-compose-to')).toHaveValue('noreply@paginium.test');
    expect(screen.getByTestId('mail-compose-subject')).toHaveValue('Re: Welcome');
  });

  it('filters starred messages from the side nav', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-filter-starred'));
    expect(screen.queryByTestId('mail-row-1')).not.toBeInTheDocument();
  });

  it('hides the floating compose while the top compose is in view', async () => {
    renderMail();
    expect(await screen.findByTestId('mail-compose')).toBeInTheDocument();
    expect(screen.queryByTestId('mail-compose-fab')).not.toBeInTheDocument();
  });

  it('lists INBOX first in the folder nav', async () => {
    renderMail();
    const inbox = await screen.findByTestId('mail-folder-INBOX');
    const junk = screen.getByTestId('mail-folder-Junk');
    expect(inbox.compareDocumentPosition(junk) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });

  it('shows IMAP Trash as Kôš', async () => {
    renderMail();
    expect(await screen.findByTestId('mail-folder-Trash')).toHaveTextContent('Kôš');
  });

  it('keeps the floating compose control above Top when the top compose is off-screen', async () => {
    const rect = (top: number, height: number): DOMRect =>
      ({
        x: 0,
        y: top,
        top,
        bottom: top + height,
        left: 0,
        right: 160,
        width: 160,
        height,
        toJSON: () => ({}),
      }) as DOMRect;

    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockImplementation(function (this: HTMLElement) {
      if (this.getAttribute('data-testid') === 'mail-compose') {
        return rect(-80, 40);
      }
      return rect(0, 600);
    });

    renderMail();
    const fab = await screen.findByTestId('mail-compose-fab');
    expect(fab).toHaveClass('mail-compose-fab');
    expect(fab).not.toHaveClass('bottom-6');
    vi.restoreAllMocks();
  });

  it('creates a custom label in the side nav', async () => {
    renderMail();
    fireEvent.change(await screen.findByTestId('mail-new-label'), { target: { value: 'Family' } });
    fireEvent.click(screen.getByTestId('mail-add-label'));
    expect(await screen.findByTestId('mail-label-family')).toBeInTheDocument();
    expect(screen.getByTestId('mail-label-family')).toHaveTextContent('Family');
  });

  it('shows message tags as colored chips', async () => {
    renderMail();
    const row = await screen.findByTestId('mail-row-1');
    expect(row.querySelector('.mail-tag')).toHaveTextContent('intro');
    expect(await screen.findByTestId('mail-label-intro')).toBeInTheDocument();
  });

  it('sorts messages by sender like other admin lists', async () => {
    renderMail();
    expect(await screen.findByTestId('mail-sort')).toBeInTheDocument();
    const byDate = screen.getAllByTestId(/mail-row-/);
    expect(byDate[0]).toHaveAttribute('data-testid', 'mail-row-1');
    fireEvent.click(screen.getByRole('button', { name: /Zoradiť podľa Od/i }));
    const byFrom = screen.getAllByTestId(/mail-row-/);
    expect(byFrom[0]).toHaveAttribute('data-testid', 'mail-row-2');
    expect(byFrom[1]).toHaveAttribute('data-testid', 'mail-row-1');
  });
});
