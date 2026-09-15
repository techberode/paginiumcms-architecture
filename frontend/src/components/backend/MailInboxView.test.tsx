import { describe, expect, it, vi } from 'vitest';
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
        { name: 'INBOX', spam: false },
        { name: 'Junk', spam: true },
      ]),
      createFolder: vi.fn(),
      deleteFolder: vi.fn(),
      messages: vi.fn(async () => [
        {
          uid: 1,
          subject: 'Welcome',
          from: 'noreply@paginium.test',
          date: '2026-09-15',
          flags: [],
          tags: ['intro'],
          snippet: 'Thanks',
          seen: false,
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
      send: vi.fn(async () => ({ success: true, data: { sent: true } })),
    },
  };
});

describe('MailInboxView', () => {
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
    expect(screen.getByTestId('mail-folder-INBOX')).toHaveClass('admin-tab-on');
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
    expect(await screen.findByTestId('mail-compose-fab')).toBeInTheDocument();
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

  it('prefills a reply from the opened message', async () => {
    renderMail();
    fireEvent.click(await screen.findByTestId('mail-row-1'));
    fireEvent.click(await screen.findByTestId('mail-reply'));
    expect(screen.getByTestId('mail-compose-to')).toHaveValue('noreply@paginium.test');
    expect(screen.getByTestId('mail-compose-subject')).toHaveValue('Re: Welcome');
  });
});
