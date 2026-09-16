import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Ban, FileText, Folder, Inbox, Mail, MailOpen, MailPlus, Plus, Reply, RotateCcw, Send, Star, Trash2 } from 'lucide-react';
import {
  MAIL_LOCAL_TRASH,
  mailApi,
  type MailFolder,
  type MailMessage,
  type MailStatus,
} from '../../api/mail';
import type { ApiResponse } from '../../api/client';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { applyClientListView } from '../../utils/clientListView';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { AdminHintCard } from './AdminHintCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListSortBar } from './SortableTableHeader';
import { AdminListPagination } from './AdminListPagination';
import { BulkActionBar } from './BulkActionBar';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { AdminEmptyState } from '../ui/AdminEmptyState';
import { AdminInboxList, AdminInboxListHeader, AdminInboxRow } from './AdminInboxList';

const MAIL_FIELD =
  'w-full min-w-0 rounded-lg border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm text-admin-text placeholder:text-admin-muted';

const ADMIN_SCROLL_ROOT = '[data-testid="admin-scroll-pane"]';

const PROTECTED_FOLDERS = new Set(['inbox', 'sent', 'drafts', 'trash', 'junk', 'spam', MAIL_LOCAL_TRASH]);

type MailNavKind = 'inbox' | 'sent' | 'drafts' | 'trash' | 'spam' | 'custom';

function mailNavKind(name: string): MailNavKind {
  const key = name.toLowerCase();
  if (key === 'inbox') {
    return 'inbox';
  }
  if (key.includes('sent')) {
    return 'sent';
  }
  if (key.includes('draft')) {
    return 'drafts';
  }
  if (key === MAIL_LOCAL_TRASH || key.includes('trash') || key.includes('deleted')) {
    return 'trash';
  }
  if (key.includes('junk') || key.includes('spam')) {
    return 'spam';
  }

  return 'custom';
}

function inboxFirst(items: MailFolder[]): MailFolder[] {
  const inbox = items.filter((item) => mailNavKind(item.name) === 'inbox');
  const rest = items.filter((item) => mailNavKind(item.name) !== 'inbox');
  return [...inbox, ...rest];
}

function isComposeAnchorVisible(el: HTMLElement, root: Element | null): boolean {
  const rect = el.getBoundingClientRect();
  if (rect.width === 0 && rect.height === 0) {
    return true;
  }

  const bounds = root
    ? root.getBoundingClientRect()
    : { top: 0, bottom: window.innerHeight };

  return rect.bottom > bounds.top && rect.top < bounds.bottom;
}

function mailNavIcon(kind: MailNavKind) {
  switch (kind) {
    case 'inbox':
      return Inbox;
    case 'sent':
      return Send;
    case 'drafts':
      return FileText;
    case 'trash':
      return Trash2;
    case 'spam':
      return Ban;
    default:
      return Folder;
  }
}

function mailNavClass(active: boolean): string {
  return active ? 'mail-nav-item mail-nav-on' : 'mail-nav-item';
}

function displayFrom(from: string): string {
  const named = from.match(/^\s*"?([^"<]+?)"?\s*</);
  if (named?.[1] !== undefined && named[1].trim() !== '') {
    return named[1].trim();
  }

  return from.trim();
}

function mailDateValue(date: string): number {
  const cleaned = date.replace(/\s*\([^)]*\)\s*$/, '');
  const parsed = Date.parse(cleaned);
  return Number.isNaN(parsed) ? 0 : parsed;
}

function tagToneClass(tag: string): string {
  let sum = 0;
  for (let i = 0; i < tag.length; i += 1) {
    sum += tag.charCodeAt(i);
  }

  return `mail-tag-${(sum % 5) + 1}`;
}

function mailLabelsKey(mailbox: string): string {
  return `paginium.mail.labels:${mailbox}`;
}

function normalizeLabelName(raw: string): string {
  return raw.trim().replace(/\s+/g, ' ');
}

function labelKeyword(name: string): string {
  const ascii = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  return ascii.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function readSavedLabels(mailbox: string): string[] {
  if (mailbox === '') {
    return [];
  }
  try {
    const raw = window.localStorage.getItem(mailLabelsKey(mailbox));
    if (raw === null || raw === '') {
      return [];
    }
    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      return [];
    }
    return parsed.filter((item): item is string => typeof item === 'string' && normalizeLabelName(item) !== '');
  } catch {
    return [];
  }
}

function writeSavedLabels(mailbox: string, labels: string[]): void {
  if (mailbox === '') {
    return;
  }
  window.localStorage.setItem(mailLabelsKey(mailbox), JSON.stringify(labels));
}

function labelMatches(tags: string[], label: string): boolean {
  const keyword = labelKeyword(label);
  const lowered = label.toLowerCase();
  return tags.some((tag) => {
    const value = tag.toLowerCase();
    return value === lowered || labelKeyword(tag) === keyword;
  });
}

function displayLabel(name: string): string {
  if (name === '') {
    return name;
  }
  return name.charAt(0).toUpperCase() + name.slice(1);
}

function firstError(response: ApiResponse<unknown>): string {
  const mail: unknown = response.errors?.mail;
  if (typeof mail === 'string' && mail !== '') {
    return mail;
  }
  if (Array.isArray(mail) && typeof mail[0] === 'string' && mail[0] !== '') {
    return mail[0];
  }

  return response.error ?? '';
}

function canDeleteFolder(folder: MailFolder): boolean {
  return folder.virtual !== true && !PROTECTED_FOLDERS.has(folder.name.toLowerCase());
}

function imapFolderOf(message: MailMessage, activeFolder: string): string {
  return message.originFolder && message.originFolder !== '' ? message.originFolder : activeFolder;
}

function rowId(message: MailMessage, activeFolder: string): string {
  return `${imapFolderOf(message, activeFolder)}:${message.uid}`;
}

function parseFromAddress(from: string): string {
  const angled = from.match(/<([^>]+)>/);
  if (angled?.[1] !== undefined && angled[1].trim() !== '') {
    return angled[1].trim();
  }
  const email = from.trim();
  return email.includes('@') ? email : '';
}

function replySubject(subject: string): string {
  const trimmed = subject.trim();
  if (trimmed === '') {
    return 'Re:';
  }
  return /^re:\s/i.test(trimmed) ? trimmed : `Re: ${trimmed}`;
}

type MailCompose = { to: string; subject: string; body: string };

export const MailInboxView: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [status, setStatus] = useState<MailStatus | null>(null);
  const [folders, setFolders] = useState<MailFolder[]>([]);
  const [folder, setFolder] = useState('INBOX');
  const [messages, setMessages] = useState<MailMessage[]>([]);
  const [details, setDetails] = useState<Record<string, MailMessage>>({});
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [password, setPassword] = useState('');
  const [newFolder, setNewFolder] = useState('');
  const [tagDraft, setTagDraft] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('mail');
  const { sortField, sortDirection, handleSort } = useColumnSort('date', 'desc');
  const [compose, setCompose] = useState<MailCompose | null>(null);
  const [sending, setSending] = useState(false);
  const [extraMailbox, setExtraMailbox] = useState('');
  const [extraPassword, setExtraPassword] = useState('');
  const [showAddAccount, setShowAddAccount] = useState(false);
  const [listFilter, setListFilter] = useState<'all' | 'starred'>('all');
  const [labelFilter, setLabelFilter] = useState<string | null>(null);
  const [customLabels, setCustomLabels] = useState<string[]>([]);
  const [newLabel, setNewLabel] = useState('');
  const composeAnchorRef = useRef<HTMLButtonElement>(null);
  const [composeAnchorVisible, setComposeAnchorVisible] = useState(true);

  const describeMailError = useCallback(
    (raw: string, fallbackKey: 'platform.mail.toast.loadFailed' | 'platform.mail.toast.saveFailed' | 'platform.mail.toast.sendFailed') => {
      switch (raw) {
        case 'IMAP is disabled.':
          return t('platform.mail.notEnabled');
        case 'SMTP is disabled.':
          return t('platform.mail.smtpOff');
        case 'Site domain is not configured.':
          return t('platform.mail.siteDomainMissing');
        case 'Only mailboxes on the CMS domain are allowed.':
          return t('platform.mail.forbiddenDomain');
        case 'Mailbox password is required.':
          return t('platform.mail.needPassword');
        case 'Mailbox is already added.':
          return t('platform.mail.toast.accountExists');
        case 'This mailbox cannot be removed.':
          return t('platform.mail.toast.accountPrimary');
        case 'Too many mailboxes.':
          return t('platform.mail.toast.accountLimit');
        default:
          return raw !== '' ? raw : t(fallbackKey);
      }
    },
    [t]
  );

  const loadStatus = useCallback(async () => {
    const next = await mailApi.status();
    setStatus(next);
    return next;
  }, []);

  const loadMailbox = useCallback(async (activeFolder: string) => {
    const nextFolders = await mailApi.folders();
    setFolders(nextFolders);
    const nextMessages = await mailApi.messages(activeFolder);
    setMessages(nextMessages);
    setDetails({});
    setExpandedId(null);
  }, []);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    void loadStatus()
      .then(async (next) => {
        if (cancelled || next === null || !next.enabled || !next.hasPassword || !next.mailboxAllowed) {
          return;
        }
        await loadMailbox(folder);
      })
      .catch(() => {
        if (!cancelled) {
          toast.error(t('platform.mail.toast.loadFailed'));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });
    return () => {
      cancelled = true;
    };
  }, [folder, loadMailbox, loadStatus, t, toast]);

  useEffect(() => {
    setPage(1);
  }, [search, folder, pageSize, listFilter, labelFilter, sortField, sortDirection]);

  useEffect(() => {
    setListFilter('all');
    setLabelFilter(null);
  }, [folder]);

  useEffect(() => {
    setCustomLabels(readSavedLabels(status?.mailbox ?? ''));
  }, [status?.mailbox]);

  const persistLabel = (name: string) => {
    const normalized = normalizeLabelName(name);
    const keyword = labelKeyword(normalized);
    if (keyword === '') {
      return normalized;
    }
    const mailbox = status?.mailbox ?? '';
    setCustomLabels((current) => {
      if (current.some((item) => labelKeyword(item) === keyword)) {
        return current;
      }
      const next = [...current, normalized];
      writeSavedLabels(mailbox, next);
      return next;
    });
    return normalized;
  };

  const labels = useMemo(() => {
    const unique = new Map<string, string>();
    for (const name of customLabels) {
      const key = labelKeyword(name);
      if (key !== '') {
        unique.set(key, name);
      }
    }
    for (const item of messages) {
      for (const tag of item.tags ?? []) {
        const key = labelKeyword(tag);
        if (key !== '' && !unique.has(key)) {
          unique.set(key, tag);
        }
      }
    }
    return [...unique.values()].sort((a, b) => a.localeCompare(b));
  }, [customLabels, messages]);

  const navFolders = useMemo(() => inboxFirst(folders), [folders]);

  const visibleMessages = useMemo(
    () =>
      messages.filter((item) => {
        if (listFilter === 'starred' && item.flagged !== true) {
          return false;
        }
        if (labelFilter !== null && !labelMatches(item.tags ?? [], labelFilter)) {
          return false;
        }
        return true;
      }),
    [labelFilter, listFilter, messages]
  );

  const listView = useMemo(
    () =>
      applyClientListView(visibleMessages, {
        search,
        searchText: (item) => `${item.subject} ${item.from} ${item.snippet} ${item.date}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'date', label: t('platform.mail.date'), getValue: (item) => mailDateValue(item.date) },
          { value: 'from', label: t('platform.mail.from'), getValue: (item) => displayFrom(item.from) },
          { value: 'subject', label: t('platform.mail.subject'), getValue: (item) => item.subject },
        ],
        page,
        pageSize,
      }),
    [visibleMessages, page, pageSize, search, sortDirection, sortField, t]
  );

  const bulkSelection = useBulkSelection(
    listView.items.map((item) => rowId(item, folder)),
    `${folder}:${page}:${search}:${pageSize}:${listFilter}:${labelFilter ?? ''}:${sortField}:${sortDirection}`
  );

  const unread = messages.filter((item) => item.seen !== true).length;
  const starredCount = messages.filter((item) => item.flagged === true).length;
  const inLocalTrash = folder === MAIL_LOCAL_TRASH;
  const folderNavActive = listFilter === 'all' && labelFilter === null;

  const folderLabel = (item: MailFolder): string => {
    if (item.name === MAIL_LOCAL_TRASH) {
      return t('platform.mail.localTrash');
    }
    const kind = mailNavKind(item.name);
    if (kind === 'inbox') {
      return t('platform.mail.inbox');
    }
    if (kind === 'sent') {
      return t('platform.mail.sent');
    }
    if (kind === 'drafts') {
      return t('platform.mail.drafts');
    }
    if (kind === 'trash') {
      return t('platform.mail.trash');
    }
    if (item.spam || kind === 'spam') {
      return t('platform.mail.spam');
    }
    return item.name;
  };

  const savePassword = async () => {
    const response = await mailApi.savePassword(password);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.saveFailed'));
      return;
    }
    setPassword('');
    toast.success(t('platform.mail.passwordSaved'));
    const next = await loadStatus();
    if (next?.enabled && next.hasPassword && next.mailboxAllowed) {
      await loadMailbox(folder);
    }
  };

  const openMessage = async (row: MailMessage) => {
    const sourceFolder = imapFolderOf(row, folder);
    const id = rowId(row, folder);
    const message = await mailApi.message(sourceFolder, row.uid);
    if (message === null) {
      toast.error(t('platform.mail.toast.loadFailed'));
      return;
    }
    const merged = { ...message, originFolder: sourceFolder, seen: true, flagged: message.flagged ?? row.flagged };
    setDetails((current) => ({ ...current, [id]: merged }));
    setMessages((current) => current.map((item) => (rowId(item, folder) === id ? { ...item, seen: true } : item)));
  };

  const toggleExpand = (id: string) => {
    setExpandedId((current) => {
      const next = current === id ? null : id;
      if (next !== null) {
        const row = messages.find((item) => rowId(item, folder) === next);
        if (row) {
          void openMessage(row);
        }
      }
      return next;
    });
  };

  const addFolder = async () => {
    const name = newFolder.trim();
    if (name === '') {
      return;
    }
    const response = await mailApi.createFolder(name);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    setNewFolder('');
    toast.success(t('platform.mail.toast.folderCreated'));
    await loadMailbox(folder);
  };

  const removeFolder = async (name: string) => {
    if (!window.confirm(t('platform.mail.confirmDeleteFolder', { name }))) {
      return;
    }
    const response = await mailApi.deleteFolder(name);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.folderDeleted'));
    const nextFolder = folder === name ? 'INBOX' : folder;
    setFolder(nextFolder);
    await loadMailbox(nextFolder);
  };

  const patchRow = (id: string, patch: Partial<MailMessage>) => {
    setMessages((current) => current.map((item) => (rowId(item, folder) === id ? { ...item, ...patch } : item)));
    setDetails((current) => (current[id] ? { ...current, [id]: { ...current[id], ...patch } } : current));
  };

  const toggleFlag = async (message: MailMessage, flag: 'flagged' | 'seen') => {
    const sourceFolder = imapFolderOf(message, folder);
    const id = rowId(message, folder);
    const current = details[id] ?? message;
    const enabled = flag === 'flagged' ? Boolean(current.flagged) : Boolean(current.seen);
    const response = await mailApi.changeFlags(sourceFolder, message.uid, enabled ? [] : [flag], enabled ? [flag] : []);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    patchRow(id, { [flag]: !enabled });
  };

  const addTag = async (message: MailMessage) => {
    const name = persistLabel(tagDraft);
    const tag = labelKeyword(name);
    if (tag === '') {
      return;
    }
    const sourceFolder = imapFolderOf(message, folder);
    const id = rowId(message, folder);
    const current = details[id] ?? message;
    const response = await mailApi.changeFlags(sourceFolder, message.uid, [tag], []);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    setTagDraft('');
    patchRow(id, { tags: Array.from(new Set([...(current.tags ?? []), tag])) });
  };

  const addCustomLabel = () => {
    const name = persistLabel(newLabel);
    if (labelKeyword(name) === '') {
      return;
    }
    setNewLabel('');
    setListFilter('all');
    setLabelFilter(name);
  };

  const removeTag = async (message: MailMessage, tag: string) => {
    const sourceFolder = imapFolderOf(message, folder);
    const id = rowId(message, folder);
    const current = details[id] ?? message;
    const response = await mailApi.changeFlags(sourceFolder, message.uid, [], [tag]);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    patchRow(id, { tags: (current.tags ?? []).filter((item) => item !== tag) });
  };

  const hideLocal = async (message: MailMessage) => {
    const sourceFolder = imapFolderOf(message, folder);
    const response = await mailApi.hide(sourceFolder, message.uid, {
      subject: message.subject,
      from: message.from,
      date: message.date,
    });
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.hidden'));
    await loadMailbox(folder);
  };

  const restoreLocal = async (message: MailMessage) => {
    const sourceFolder = imapFolderOf(message, folder);
    const response = await mailApi.unhide(sourceFolder, message.uid);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.restored'));
    await loadMailbox(folder);
  };

  const moveSpam = async (message: MailMessage) => {
    const sourceFolder = imapFolderOf(message, folder);
    const response = await mailApi.moveSpam(sourceFolder, message.uid);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.spamMoved'));
    await loadMailbox(folder);
  };

  const selectedRows = listView.items.filter((item) => bulkSelection.isSelected(rowId(item, folder)));

  const handleBulk = async (action: 'read' | 'unread' | 'star' | 'hide' | 'restore') => {
    if (selectedRows.length === 0) {
      return;
    }
    for (const row of selectedRows) {
      const sourceFolder = imapFolderOf(row, folder);
      if (action === 'hide') {
        await mailApi.hide(sourceFolder, row.uid, { subject: row.subject, from: row.from, date: row.date });
      } else if (action === 'restore') {
        await mailApi.unhide(sourceFolder, row.uid);
      } else if (action === 'star') {
        await mailApi.changeFlags(sourceFolder, row.uid, ['flagged'], []);
      } else if (action === 'read') {
        await mailApi.changeFlags(sourceFolder, row.uid, ['seen'], []);
      } else {
        await mailApi.changeFlags(sourceFolder, row.uid, [], ['seen']);
      }
    }
    bulkSelection.clear();
    await loadMailbox(folder);
  };

  const openCompose = () => {
    setCompose({ to: '', subject: '', body: '' });
  };

  const openReply = (message: MailMessage) => {
    const quoted = message.body && message.body !== '' ? message.body : (message.snippet ?? '');
    setCompose({
      to: parseFromAddress(message.from),
      subject: replySubject(message.subject),
      body: quoted === '' ? '' : `\n\n---\n${message.from} (${message.date}):\n${quoted}`,
    });
  };

  const sendCompose = async () => {
    if (compose === null || sending) {
      return;
    }
    setSending(true);
    try {
      const response = await mailApi.send(compose);
      if (!response.success) {
        toast.error(describeMailError(firstError(response), 'platform.mail.toast.sendFailed'));
        return;
      }
      toast.success(t('platform.mail.toast.sent'));
      setCompose(null);
    } finally {
      setSending(false);
    }
  };

  const switchAccount = async (mailbox: string) => {
    if (mailbox === '' || mailbox === status?.mailbox) {
      return;
    }
    const response = await mailApi.selectAccount(mailbox);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    setFolder('INBOX');
    setCompose(null);
    const next = await loadStatus();
    if (next?.enabled && next.hasPassword && next.mailboxAllowed) {
      await loadMailbox('INBOX');
    } else {
      setFolders([]);
      setMessages([]);
    }
  };

  const addExtraAccount = async () => {
    const response = await mailApi.addAccount(extraMailbox, extraPassword);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.saveFailed'));
      return;
    }
    setExtraMailbox('');
    setExtraPassword('');
    setShowAddAccount(false);
    toast.success(t('platform.mail.toast.accountAdded'));
    setFolder('INBOX');
    const next = await loadStatus();
    if (next?.enabled && next.hasPassword && next.mailboxAllowed) {
      await loadMailbox('INBOX');
    }
  };

  const removeExtraAccount = async (mailbox: string) => {
    if (!window.confirm(t('platform.mail.confirmRemoveAccount', { mailbox }))) {
      return;
    }
    const response = await mailApi.removeAccount(mailbox);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.accountRemoved'));
    const next = await loadStatus();
    if (next?.enabled && next.hasPassword && next.mailboxAllowed) {
      await loadMailbox(folder);
    }
  };

  const mailboxReady = Boolean(status?.enabled && status.mailboxAllowed && status.hasPassword);
  const canSend = Boolean(status?.mailboxAllowed && status.canSend);
  const accounts = status?.accounts ?? [];
  const activeAccount = accounts.find((item) => item.mailbox === status?.mailbox);

  useEffect(() => {
    const el = composeAnchorRef.current;
    if (!el || !canSend || compose !== null) {
      setComposeAnchorVisible(true);
      return undefined;
    }

    const root = el.closest(ADMIN_SCROLL_ROOT);

    const update = (intersecting?: boolean) => {
      const geometryVisible = isComposeAnchorVisible(el, root instanceof Element ? root : null);
      const rect = el.getBoundingClientRect();
      const unmeasured = rect.width === 0 && rect.height === 0;
      if (unmeasured) {
        setComposeAnchorVisible(true);
        return;
      }
      if (typeof intersecting === 'boolean') {
        setComposeAnchorVisible(intersecting && geometryVisible);
        return;
      }
      setComposeAnchorVisible(geometryVisible);
    };

    update();

    let observer: IntersectionObserver | null = null;
    if (typeof IntersectionObserver !== 'undefined') {
      observer = new IntersectionObserver(
        ([entry]) => {
          update(entry.isIntersecting);
        },
        {
          root: root instanceof Element ? root : null,
          threshold: [0, 0.01, 1],
        }
      );
      observer.observe(el);
    }

    const onScrollOrResize = () => update();
    const scrollTarget: EventTarget = root ?? window;
    scrollTarget.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize);

    return () => {
      observer?.disconnect();
      scrollTarget.removeEventListener('scroll', onScrollOrResize);
      window.removeEventListener('resize', onScrollOrResize);
    };
  }, [canSend, compose, mailboxReady, folders.length]);

  return (
    <div className="relative space-y-6 w-full max-w-none pb-24" data-testid="mail-inbox">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0 grow">
          <h1 className={`${ADMIN_PAGE_TITLE} flex items-center gap-2`}>
            <Mail className="w-6 h-6 text-admin-primary" />
            {t('platform.mail.title')}
          </h1>
          <div className={`${ADMIN_PAGE_SUBTITLE} flex flex-wrap items-center gap-2`}>
            {accounts.length > 1 ? (
              <label className="inline-flex min-w-0 items-center gap-2">
                <span className="sr-only">{t('platform.mail.accounts')}</span>
                <select
                  className="max-w-full rounded-lg border border-admin-border bg-admin-canvas px-2 py-1 font-medium text-admin-text"
                  value={status?.mailbox ?? ''}
                  onChange={(event) => void switchAccount(event.target.value)}
                  data-testid="mail-account"
                >
                  {accounts.map((item) => (
                    <option key={item.mailbox} value={item.mailbox}>
                      {item.mailbox}
                      {item.primary ? ` (${t('platform.mail.primaryAccount')})` : ''}
                    </option>
                  ))}
                </select>
              </label>
            ) : status?.mailbox ? (
              <span data-testid="mail-account" className="font-medium text-admin-text">
                {status.mailbox}
              </span>
            ) : null}
            {status?.enabled ? (
              <button
                type="button"
                className="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-admin-primary hover:bg-admin-sidebar-hover"
                onClick={() => setShowAddAccount((current) => !current)}
                data-testid="mail-add-account-toggle"
              >
                <Plus className="h-3.5 w-3.5" />
                {t('platform.mail.addAccount')}
              </button>
            ) : null}
            {activeAccount && !activeAccount.primary ? (
              <button
                type="button"
                className="text-xs text-admin-muted hover:text-red-600"
                onClick={() => void removeExtraAccount(activeAccount.mailbox)}
                data-testid="mail-remove-account"
              >
                {t('platform.mail.removeAccount')}
              </button>
            ) : null}
            {status?.mailbox && mailboxReady ? <span>· {t('platform.mail.unread', { count: String(unread) })}</span> : null}
          </div>
        </div>
        {canSend && !mailboxReady ? (
          <button
            type="button"
            ref={composeAnchorRef}
            className="btn btn-primary"
            onClick={openCompose}
            data-testid="mail-compose"
          >
            <MailPlus className="mr-1 inline h-4 w-4" />
            {t('platform.mail.compose')}
          </button>
        ) : null}
      </div>

      {status !== null && !status.enabled ? (
        <AdminHintCard title={t('platform.mail.title')}>
          <p>{t('platform.mail.notEnabled')}</p>
          <Link
            to={settingsGroupPath('imap')}
            className="mt-2 inline-block font-semibold text-admin-primary hover:underline"
            data-testid="mail-open-settings"
          >
            {t('platform.mail.openSettings')}
          </Link>
        </AdminHintCard>
      ) : null}

      {status?.enabled && !status.mailboxAllowed ? (
        <AdminHintCard title={t('platform.mail.forbiddenDomain')}>
          <p>
            {status.siteHost === ''
              ? t('platform.mail.siteDomainMissing')
              : t('platform.mail.mailboxMismatch', {
                  mailbox: status.mailbox,
                  domain: status.siteHost,
                })}
          </p>
          <Link
            to={settingsGroupPath('imap')}
            className="mt-2 inline-block font-semibold text-admin-primary hover:underline"
            data-testid="mail-domain-settings"
          >
            {t('platform.mail.openSettings')}
          </Link>
        </AdminHintCard>
      ) : null}

      {status?.enabled && status.mailboxAllowed && !status.hasPassword ? (
        <AdminWidgetCard title={status.mailbox || t('platform.mail.title')}>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label className="block grow text-sm text-admin-muted">
              {t('platform.mail.password')}
              <input
                type="password"
                className={`${MAIL_FIELD} mt-1`}
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                autoComplete="off"
                data-testid="mail-password"
              />
            </label>
            <button type="button" className="btn btn-primary" onClick={() => void savePassword()} data-testid="mail-save-password">
              {t('platform.mail.savePassword')}
            </button>
          </div>
          <p className="mt-2 text-sm text-admin-muted">{t('platform.mail.needPassword')}</p>
        </AdminWidgetCard>
      ) : null}

      {status?.enabled && showAddAccount ? (
        <AdminWidgetCard title={t('platform.mail.addAccount')}>
          <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <label className="block min-w-0 grow text-sm text-admin-muted">
              {t('platform.mail.accountEmail')}
              <input
                className={`${MAIL_FIELD} mt-1`}
                value={extraMailbox}
                onChange={(event) => setExtraMailbox(event.target.value)}
                placeholder={t('platform.mail.accountPlaceholder')}
                autoComplete="off"
                data-testid="mail-add-account-email"
              />
            </label>
            <label className="block min-w-0 grow text-sm text-admin-muted">
              {t('platform.mail.password')}
              <input
                type="password"
                className={`${MAIL_FIELD} mt-1`}
                value={extraPassword}
                onChange={(event) => setExtraPassword(event.target.value)}
                autoComplete="off"
                data-testid="mail-add-account-password"
              />
            </label>
            <button type="button" className="btn btn-primary shrink-0" onClick={() => void addExtraAccount()} data-testid="mail-add-account-save">
              {t('platform.mail.addAccount')}
            </button>
          </div>
        </AdminWidgetCard>
      ) : null}

      {status?.enabled && status.mailboxAllowed && !status.canSend ? (
        <p className="text-sm text-admin-muted">
          {t('platform.mail.smtpOff')}{' '}
          <Link to={settingsGroupPath('smtp')} className="font-semibold text-admin-primary hover:underline">
            {t('platform.mail.openSmtp')}
          </Link>
        </p>
      ) : null}

      {compose !== null ? (
        <AdminWidgetCard title={t('platform.mail.compose')}>
          <div className="space-y-3">
            <p className="text-sm text-admin-muted">
              {t('platform.mail.from')}: <span className="text-admin-text">{status?.mailbox}</span>
            </p>
            <label className="block text-sm text-admin-muted">
              {t('platform.mail.to')}
              <input
                className={`${MAIL_FIELD} mt-1`}
                value={compose.to}
                onChange={(event) => setCompose({ ...compose, to: event.target.value })}
                autoComplete="off"
                data-testid="mail-compose-to"
              />
            </label>
            <label className="block text-sm text-admin-muted">
              {t('platform.mail.subject')}
              <input
                className={`${MAIL_FIELD} mt-1`}
                value={compose.subject}
                onChange={(event) => setCompose({ ...compose, subject: event.target.value })}
                autoComplete="off"
                data-testid="mail-compose-subject"
              />
            </label>
            <label className="block text-sm text-admin-muted">
              {t('platform.mail.body')}
              <textarea
                className={`${MAIL_FIELD} mt-1 min-h-40`}
                value={compose.body}
                onChange={(event) => setCompose({ ...compose, body: event.target.value })}
                data-testid="mail-compose-body"
              />
            </label>
            <div className="flex flex-wrap gap-2">
              <button type="button" className="btn btn-primary" onClick={() => void sendCompose()} disabled={sending} data-testid="mail-send">
                {sending ? t('platform.mail.sending') : t('platform.mail.send')}
              </button>
              <button type="button" className="btn btn-secondary" onClick={() => setCompose(null)} disabled={sending}>
                {t('common.cancel')}
              </button>
            </div>
          </div>
        </AdminWidgetCard>
      ) : null}

      {mailboxReady ? (
        <div className="mail-app">
          <aside className="mail-app-nav">
            {canSend ? (
              <button
                type="button"
                ref={composeAnchorRef}
                className="btn btn-primary mb-2 w-full"
                onClick={openCompose}
                data-testid="mail-compose"
              >
                <MailPlus className="mr-1 inline h-4 w-4" />
                {t('platform.mail.compose')}
              </button>
            ) : null}
            <p className="mail-nav-heading">{t('platform.mail.folders')}</p>
            <nav className="flex flex-col gap-0.5" aria-label={t('platform.mail.folders')}>
              {navFolders.map((item) => {
                const kind = mailNavKind(item.name);
                const Icon = mailNavIcon(kind);
                const active = folderNavActive && folder === item.name;
                return (
                  <div key={item.name} className="flex items-center gap-0.5">
                    <button
                      type="button"
                      className={mailNavClass(active)}
                      onClick={() => {
                        setListFilter('all');
                        setLabelFilter(null);
                        setFolder(item.name);
                      }}
                      aria-current={active ? 'page' : undefined}
                      data-testid={`mail-folder-${item.name}`}
                    >
                      <Icon className="h-4 w-4 shrink-0" aria-hidden />
                      <span className="min-w-0 truncate">{folderLabel(item)}</span>
                      {kind === 'inbox' && unread > 0 && folder === item.name ? (
                        <span className="mail-nav-count">{unread}</span>
                      ) : null}
                    </button>
                    {canDeleteFolder(item) ? (
                      <button
                        type="button"
                        className="shrink-0 rounded-full p-1.5 text-admin-muted hover:text-red-600"
                        aria-label={t('platform.mail.deleteFolder')}
                        onClick={() => void removeFolder(item.name)}
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </button>
                    ) : null}
                  </div>
                );
              })}
              <button
                type="button"
                className={mailNavClass(listFilter === 'starred')}
                onClick={() => {
                  setLabelFilter(null);
                  setListFilter('starred');
                }}
                aria-current={listFilter === 'starred' ? 'page' : undefined}
                data-testid="mail-filter-starred"
              >
                <Star className="h-4 w-4 shrink-0" aria-hidden />
                <span className="min-w-0 truncate">{t('platform.mail.starred')}</span>
                {starredCount > 0 ? <span className="mail-nav-count">{starredCount}</span> : null}
              </button>
            </nav>
            <p className="mail-nav-heading">{t('platform.mail.labels')}</p>
            <nav className="flex flex-col gap-0.5" aria-label={t('platform.mail.labels')}>
              {labels.map((tag) => (
                <button
                  key={labelKeyword(tag)}
                  type="button"
                  className={mailNavClass(labelFilter !== null && labelMatches([labelFilter], tag))}
                  onClick={() => {
                    setListFilter('all');
                    setLabelFilter(tag);
                  }}
                  aria-current={labelFilter !== null && labelMatches([labelFilter], tag) ? 'page' : undefined}
                  data-testid={`mail-label-${labelKeyword(tag)}`}
                >
                  <span className={`mail-tag-dot ${tagToneClass(labelKeyword(tag))}`} aria-hidden />
                  <span className="min-w-0 truncate">{displayLabel(tag)}</span>
                </button>
              ))}
            </nav>
            <form
              className="mt-2 flex flex-col gap-2"
              onSubmit={(event) => {
                event.preventDefault();
                addCustomLabel();
              }}
            >
              <label className="block min-w-0 text-sm font-medium text-[#132337] dark:text-admin-text">
                {t('platform.mail.newLabel')}
                <input
                  className={`${MAIL_FIELD} mt-1`}
                  value={newLabel}
                  onChange={(event) => setNewLabel(event.target.value)}
                  placeholder={t('platform.mail.newLabelPlaceholder')}
                  autoComplete="off"
                  data-testid="mail-new-label"
                />
              </label>
              <button type="submit" className="btn btn-secondary w-full" data-testid="mail-add-label">
                {t('platform.mail.addLabel')}
              </button>
            </form>
            <form
              className="mt-auto flex flex-col gap-2 pt-4"
              onSubmit={(event) => {
                event.preventDefault();
                void addFolder();
              }}
            >
              <label className="block min-w-0 text-sm font-medium text-[#132337] dark:text-admin-text">
                {t('platform.mail.newCategory')}
                <input
                  className={`${MAIL_FIELD} mt-1`}
                  value={newFolder}
                  onChange={(event) => setNewFolder(event.target.value)}
                  placeholder={t('platform.mail.newCategoryPlaceholder')}
                  autoComplete="off"
                  data-testid="mail-new-folder"
                />
              </label>
              <button type="submit" className="btn btn-secondary w-full" data-testid="mail-add-folder">
                {t('platform.mail.addCategory')}
              </button>
            </form>
          </aside>

          <div className="mail-app-main">
            <div className="mail-app-toolbar">
              <AdminListToolbar
                search={search}
                onSearchChange={setSearch}
                searchPlaceholder={t('platform.mail.search')}
                pageSize={pageSize}
                onPageSizeChange={setPageSize}
                pageSizeOptions={[5, 10, 20, 50]}
              />
              <div className="px-4 pb-2" data-testid="mail-sort">
                <AdminListSortBar
                  columns={[
                    { field: 'date', label: t('platform.mail.date') },
                    { field: 'from', label: t('platform.mail.from') },
                    { field: 'subject', label: t('platform.mail.subject') },
                  ]}
                  activeField={sortField}
                  direction={sortDirection}
                  onSort={handleSort}
                />
              </div>
            </div>

            <BulkActionBar
              count={bulkSelection.count}
              totalCount={listView.total}
              itemLabel={t('platform.mail.bulk.itemLabel')}
              onClear={bulkSelection.clear}
              actions={
                inLocalTrash
                  ? [{ id: 'restore', label: t('platform.mail.restore'), variant: 'secondary', onClick: () => void handleBulk('restore') }]
                  : [
                      { id: 'read', label: t('platform.mail.markRead'), variant: 'secondary', onClick: () => void handleBulk('read') },
                      { id: 'unread', label: t('platform.mail.markUnread'), variant: 'secondary', onClick: () => void handleBulk('unread') },
                      { id: 'star', label: t('platform.mail.star'), variant: 'secondary', onClick: () => void handleBulk('star') },
                      { id: 'hide', label: t('platform.mail.hideLocal'), variant: 'danger', onClick: () => void handleBulk('hide') },
                    ]
              }
            />

            {loading ? (
              <AdminListSkeleton rows={8} />
            ) : listView.total === 0 ? (
              <AdminEmptyState title={messages.length === 0 ? t('platform.mail.empty') : t('platform.mail.emptyFilter')} />
            ) : (
              <>
                <AdminInboxList className="rounded-none border-0 shadow-none">
                  <AdminInboxListHeader
                    allSelected={bulkSelection.allSelected && listView.items.length > 0}
                    onToggleAll={bulkSelection.toggleAll}
                  />
                  {listView.items.map((message, index) => {
                    const id = rowId(message, folder);
                    const detail = details[id];
                    const starred = (detail?.flagged ?? message.flagged) === true;
                    const tags = message.tags ?? [];
                    return (
                      <AdminInboxRow
                        key={id}
                        id={id}
                        index={index}
                        expanded={expandedId === id}
                        onToggleExpand={toggleExpand}
                        selected={bulkSelection.isSelected(id)}
                        onToggleSelect={bulkSelection.toggle}
                        unread={message.seen !== true}
                        dense
                        leading={
                          <button
                            type="button"
                            className="mt-0.5 shrink-0 rounded p-1 text-admin-muted hover:text-amber-400"
                            aria-label={t('platform.mail.star')}
                            aria-pressed={starred}
                            onClick={(event) => {
                              event.stopPropagation();
                              void toggleFlag(detail ?? message, 'flagged');
                            }}
                            data-testid={`mail-star-${message.uid}`}
                          >
                            <Star className={`h-4 w-4 ${starred ? 'fill-current text-amber-400' : ''}`} />
                          </button>
                        }
                        summary={
                          <div className="mail-row-grid" data-testid={`mail-row-${message.uid}`}>
                            <span className={`min-w-0 truncate ${message.seen ? 'text-admin-text' : 'font-semibold text-admin-text'}`}>
                              {displayFrom(message.from) || t('platform.mail.noSubject')}
                            </span>
                            <span className="min-w-0 truncate text-sm">
                              {tags.map((tag) => (
                                <span key={tag} className={`mail-tag mr-1.5 ${tagToneClass(labelKeyword(tag) || tag)}`}>
                                  {displayLabel(customLabels.find((item) => labelKeyword(item) === labelKeyword(tag)) ?? tag)}
                                </span>
                              ))}
                              <span className={message.seen ? 'text-admin-muted' : 'text-admin-text'}>
                                {message.subject || t('platform.mail.noSubject')}
                              </span>
                              {expandedId !== id && message.snippet !== '' ? (
                                <span className="text-admin-muted"> — {message.snippet}</span>
                              ) : null}
                            </span>
                            <span className="shrink-0 whitespace-nowrap text-xs text-admin-muted">{message.date}</span>
                          </div>
                        }
                      detail={
                        <div className="space-y-3 text-sm">
                          <p className="text-xs text-admin-muted">
                            {t('platform.mail.from')}: {detail?.from ?? message.from}
                          </p>
                          <div className="flex flex-wrap gap-2">
                            {canSend && !inLocalTrash ? (
                              <button
                                type="button"
                                className="btn btn-primary px-2 py-1 text-xs"
                                onClick={() => openReply(detail ?? message)}
                                data-testid="mail-reply"
                              >
                                <Reply className="mr-1 inline h-3 w-3" />
                                {t('platform.mail.reply')}
                              </button>
                            ) : null}
                            <button type="button" className="btn btn-secondary px-2 py-1 text-xs" onClick={() => void toggleFlag(detail ?? message, 'flagged')}>
                              <Star className={`mr-1 inline h-3 w-3 ${detail?.flagged || message.flagged ? 'fill-current text-amber-400' : ''}`} />
                              {t('platform.mail.star')}
                            </button>
                            <button type="button" className="btn btn-secondary px-2 py-1 text-xs" onClick={() => void toggleFlag(detail ?? message, 'seen')}>
                              {(detail?.seen ?? message.seen) ? (
                                <MailOpen className="mr-1 inline h-3 w-3" />
                              ) : (
                                <Mail className="mr-1 inline h-3 w-3" />
                              )}
                              {(detail?.seen ?? message.seen) ? t('platform.mail.markUnread') : t('platform.mail.markRead')}
                            </button>
                            {inLocalTrash ? (
                              <button type="button" className="btn btn-secondary px-2 py-1 text-xs" onClick={() => void restoreLocal(message)}>
                                <RotateCcw className="mr-1 inline h-3 w-3" />
                                {t('platform.mail.restore')}
                              </button>
                            ) : (
                              <>
                                <button
                                  type="button"
                                  className="btn btn-secondary px-2 py-1 text-xs"
                                  onClick={() => void hideLocal(message)}
                                  data-testid="mail-hide"
                                >
                                  <Trash2 className="mr-1 inline h-3 w-3" />
                                  {t('platform.mail.hideLocal')}
                                </button>
                                <button
                                  type="button"
                                  className="btn btn-danger px-2 py-1 text-xs"
                                  onClick={() => void moveSpam(message)}
                                  data-testid="mail-move-spam"
                                >
                                  {t('platform.mail.moveSpam')}
                                </button>
                              </>
                            )}
                          </div>
                          {detail?.html ? (
                            <iframe
                              title={detail.subject || t('platform.mail.selectMessage')}
                              sandbox="allow-popups allow-popups-to-escape-sandbox"
                              referrerPolicy="no-referrer"
                              srcDoc={detail.html}
                              className="h-[min(72vh,48rem)] w-full rounded-lg border border-admin-border bg-white"
                              data-testid="mail-html-frame"
                            />
                          ) : (
                            <p className="whitespace-pre-wrap text-admin-text">{detail?.body ?? message.snippet}</p>
                          )}
                          <div className="flex flex-wrap gap-2">
                            {(detail?.tags ?? message.tags ?? []).map((tag) => (
                              <button
                                key={tag}
                                type="button"
                                className={`mail-tag ${tagToneClass(labelKeyword(tag) || tag)}`}
                                onClick={() => void removeTag(detail ?? message, tag)}
                                title={t('platform.mail.removeTag')}
                              >
                                {displayLabel(tag)} ×
                              </button>
                            ))}
                          </div>
                          <div className="flex flex-col gap-2 sm:flex-row">
                            <input
                              className={MAIL_FIELD}
                              value={tagDraft}
                              onChange={(event) => setTagDraft(event.target.value)}
                              placeholder={t('platform.mail.addTag')}
                              aria-label={t('platform.mail.tags')}
                              data-testid="mail-tag-input"
                            />
                            <button type="button" className="btn btn-secondary shrink-0" onClick={() => void addTag(detail ?? message)}>
                              {t('platform.mail.addTag')}
                            </button>
                          </div>
                        </div>
                      }
                    />
                  );
                })}
              </AdminInboxList>

              <AdminListPagination
                page={listView.page}
                totalPages={listView.totalPages}
                total={listView.total}
                pageSize={pageSize}
                loading={loading}
                onPageChange={setPage}
                itemLabel={t('platform.mail.bulk.itemLabel')}
              />
            </>
          )}
          </div>
        </div>
      ) : null}

      {canSend && compose === null && !composeAnchorVisible ? (
        <button
          type="button"
          className="btn btn-primary mail-compose-fab shadow-lg shadow-black/20"
          onClick={openCompose}
          data-testid="mail-compose-fab"
        >
          <MailPlus className="mr-1 inline h-4 w-4" />
          {t('platform.mail.compose')}
        </button>
      ) : null}
    </div>
  );
};
