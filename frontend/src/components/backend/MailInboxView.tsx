import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowLeft,
  Ban,
  ChevronDown,
  Menu,
  FileText,
  Folder,
  Contact,
  Inbox,
  Mail,
  MailOpen,
  MailPlus,
  Pencil,
  Plus,
  Reply,
  RotateCcw,
  Send,
  Star,
  Trash2,
} from 'lucide-react';
import {
  MAIL_LOCAL_TRASH,
  mailApi,
  type MailFolder,
  type MailMessage,
  type MailSignatureFields,
  type MailSignaturePrefs,
  type MailSignatureState,
  type MailStatus,
} from '../../api/mail';
import type { ApiResponse } from '../../api/client';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { applyClientListView } from '../../utils/clientListView';
import { isTrustedImageSender, parseSenderEmail, rememberSenderRemoteImages } from '../../utils/mailTrustedImageSenders';
import {
  collectMailboxAddressHints,
  formatRecipientList,
  readRecentRecipients,
  rememberRecipients,
  validateRecipientList,
} from '../../utils/mailRecipients';
import {
  createLabelDefinition,
  findDefinitionByTag,
  messageTagMatchesDefinition,
  messageTagsForLabel,
  resolveNavLabelDefinition,
  labelColorToPickerValue,
  labelDisplayName,
  labelKeyword,
  MAIL_LABEL_COLORS,
  mailTagAttrs,
  mailTagDotAttrs,
  normalizeHexColor,
  normalizeLabelName,
  readLabelDefinitions,
  type MailLabelColor,
  type MailLabelDefinition,
  writeLabelDefinitions,
} from '../../utils/mailLabels';
import { ADMIN_PAGE_SUBTITLE, ADMIN_PAGE_TITLE } from '../../theme/adminUiClasses';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { AdminHintCard } from './AdminHintCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListSortBar } from './SortableTableHeader';
import { AdminListPagination } from './AdminListPagination';
import { MailSignaturePanel } from './MailSignaturePanel';
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

type MailCompose = { to: string; subject: string; body: string; draftUid?: number };

const DRAFT_AUTOSAVE_DEBOUNCE_MS = 2000;
const DRAFT_AUTOSAVE_TICK_MS = 5000;

function isComposeEmpty(compose: MailCompose): boolean {
  return compose.to.trim() === '' && compose.subject.trim() === '' && compose.body.trim() === '';
}

function composeFingerprint(compose: MailCompose): string {
  return `${compose.to}\n${compose.subject}\n${compose.body}`;
}

export const MailInboxView: React.FC = () => {
  const { t } = useI18n();
  const confirmDestructive = useAdminConfirm();
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
  const [newLabel, setNewLabel] = useState('');
  const [newLabelColor, setNewLabelColor] = useState<MailLabelColor>('1');
  const [editingLabelId, setEditingLabelId] = useState<string | null>(null);
  const [bulkLabelPick, setBulkLabelPick] = useState('');
  const [tagDraft, setTagDraft] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('mail');
  const { sortField, sortDirection, handleSort } = useColumnSort('date', 'desc');
  const [compose, setCompose] = useState<MailCompose | null>(null);
  const [sending, setSending] = useState(false);
  const [mailboxRefreshing, setMailboxRefreshing] = useState(false);
  const [recipientHintsTick, setRecipientHintsTick] = useState(0);
  const [messageHeaderOpen, setMessageHeaderOpen] = useState(false);
  const [extraMailbox, setExtraMailbox] = useState('');
  const [extraPassword, setExtraPassword] = useState('');
  const [showAddAccount, setShowAddAccount] = useState(false);
  const [listFilter, setListFilter] = useState<'all' | 'starred'>('all');
  const [labelFilter, setLabelFilter] = useState<string | null>(null);
  const [labelDefinitions, setLabelDefinitions] = useState<MailLabelDefinition[]>([]);
  const [blockedSenders, setBlockedSenders] = useState<string[]>([]);
  const [blockedPanelOpen, setBlockedPanelOpen] = useState(false);
  const [signaturePanelOpen, setSignaturePanelOpen] = useState(false);
  const [signatureState, setSignatureState] = useState<MailSignatureState | null>(null);
  const [signatureDraftFields, setSignatureDraftFields] = useState<MailSignatureFields | null>(null);
  const [signatureDraftPrefs, setSignatureDraftPrefs] = useState<MailSignaturePrefs | null>(null);
  const [signatureSaving, setSignatureSaving] = useState(false);
  const [mailNavOpen, setMailNavOpen] = useState(false);
  const composeAnchorRef = useRef<HTMLButtonElement>(null);
  const spamAutocleanDoneRef = useRef(false);
  const [composeAnchorVisible, setComposeAnchorVisible] = useState(true);
  const [trustedSendersTick, setTrustedSendersTick] = useState(0);
  const composeRef = useRef<MailCompose | null>(null);
  const draftSaveInFlightRef = useRef(false);
  const lastDraftFingerprintRef = useRef('');

  const describeMailError = useCallback(
    (
      raw: string,
      fallbackKey:
        | 'platform.mail.toast.loadFailed'
        | 'platform.mail.toast.saveFailed'
        | 'platform.mail.toast.sendFailed'
        | 'platform.mail.toast.draftFailed'
        | 'platform.mail.toast.deleteFailed'
    ) => {
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
        case 'Recipient is invalid.':
          return t('platform.mail.toast.recipientInvalid');
        case 'Subject is invalid.':
          return t('platform.mail.toast.subjectInvalid');
        case 'Message body is invalid.':
          return t('platform.mail.toast.bodyInvalid');
        case 'Too many recipients.':
          return t('platform.mail.toast.tooManyRecipients');
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

  const loadBlockedSenders = useCallback(async () => {
    const next = await mailApi.blockedSenders();
    setBlockedSenders(next.blocked);
  }, []);

  const applySignatureState = useCallback((next: MailSignatureState | null) => {
    setSignatureState(next);
    if (next) {
      setSignatureDraftFields({ ...next.fields });
      setSignatureDraftPrefs({ ...next.prefs, overrides: { ...next.prefs.overrides } });
    } else {
      setSignatureDraftFields(null);
      setSignatureDraftPrefs(null);
    }
  }, []);

  const loadSignature = useCallback(async () => {
    applySignatureState(await mailApi.signature());
  }, [applySignatureState]);

  useEffect(() => {
    composeRef.current = compose;
  }, [compose]);

  const persistDraft = useCallback(
    async (silent: boolean): Promise<boolean> => {
      const current = composeRef.current;
      if (current === null || draftSaveInFlightRef.current || isComposeEmpty(current)) {
        return false;
      }
      const fingerprint = composeFingerprint(current);
      if (silent && fingerprint === lastDraftFingerprintRef.current) {
        return false;
      }
      draftSaveInFlightRef.current = true;
      try {
        const response = await mailApi.saveDraft({
          ...current,
          uid: current.draftUid,
        });
        if (!response.success || !response.data) {
          if (!silent) {
            toast.error(describeMailError(firstError(response), 'platform.mail.toast.draftFailed'));
          }
          return false;
        }
        lastDraftFingerprintRef.current = fingerprint;
        setCompose((prev) => (prev ? { ...prev, draftUid: response.data!.uid } : null));
        const nextFolders = await mailApi.folders();
        setFolders(nextFolders);
        if (mailNavKind(folder) === 'drafts') {
          setMessages(await mailApi.messages(folder));
        }
        if (!silent) {
          toast.success(t('platform.mail.toast.draftSaved'));
        }
        return true;
      } finally {
        draftSaveInFlightRef.current = false;
      }
    },
    [describeMailError, folder, t, toast]
  );

  useEffect(() => {
    if (compose === null) {
      return;
    }
    const timer = window.setTimeout(() => {
      void persistDraft(true);
    }, DRAFT_AUTOSAVE_DEBOUNCE_MS);

    return () => window.clearTimeout(timer);
  }, [compose, persistDraft]);

  useEffect(() => {
    if (compose === null) {
      return;
    }
    const timer = window.setInterval(() => {
      void persistDraft(true);
    }, DRAFT_AUTOSAVE_TICK_MS);

    return () => window.clearInterval(timer);
  }, [compose, persistDraft]);

  useEffect(() => {
    return () => {
      void persistDraft(true);
    };
  }, [persistDraft]);

  useEffect(() => {
    const flush = () => {
      void persistDraft(true);
    };
    window.addEventListener('pagehide', flush);
    return () => window.removeEventListener('pagehide', flush);
  }, [persistDraft]);

  const flushDraftBeforeLeave = useCallback(async () => {
    await persistDraft(true);
  }, [persistDraft]);

  const loadMailbox = useCallback(async (activeFolder: string) => {
    const nextFolders = await mailApi.folders();
    setFolders(nextFolders);
    const [nextMessages] = await Promise.all([
      mailApi.messages(activeFolder),
      loadBlockedSenders(),
      loadSignature(),
    ]);
    setMessages(nextMessages);
    setDetails({});
    setExpandedId(null);
  }, [loadBlockedSenders, loadSignature]);

  const saveSignature = async () => {
    if (signatureDraftFields === null || signatureDraftPrefs === null || signatureSaving) {
      return;
    }
    setSignatureSaving(true);
    const response = await mailApi.saveSignature({
      enabled: signatureDraftPrefs.enabled,
      templateId: signatureDraftPrefs.templateId,
      overrides: signatureDraftFields,
    });
    setSignatureSaving(false);
    if (response.success && response.data) {
      applySignatureState(response.data);
      toast.success(t('platform.mail.signatureSaved'));
      return;
    }
    toast.error(describeMailError(response.message ?? '', 'platform.mail.toast.saveFailed'));
  };

  const importSignatureProfile = async () => {
    if (signatureSaving) {
      return;
    }
    setSignatureSaving(true);
    const response = await mailApi.importSignatureProfile();
    setSignatureSaving(false);
    if (response.success && response.data) {
      applySignatureState(response.data);
      toast.success(t('platform.mail.signatureImported'));
      return;
    }
    toast.error(describeMailError(response.message ?? '', 'platform.mail.toast.saveFailed'));
  };

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    void loadStatus()
      .then(async (next) => {
        if (cancelled || next === null || !next.enabled || !next.hasPassword || !next.mailboxAllowed) {
          return;
        }
        if (!spamAutocleanDoneRef.current) {
          await mailApi.autocleanSpam();
          spamAutocleanDoneRef.current = true;
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
    setLabelDefinitions(readLabelDefinitions(status?.mailbox ?? ''));
  }, [status?.mailbox]);

  const persistLabel = (name: string, color: MailLabelColor) => {
    const created = createLabelDefinition(name, color);
    if (created === null) {
      return normalizeLabelName(name);
    }
    const mailbox = status?.mailbox ?? '';
    setLabelDefinitions((current) => {
      if (current.some((item) => item.id === created.id)) {
        return current;
      }
      const next = [...current, created];
      writeLabelDefinitions(mailbox, next);
      return next;
    });
    return created.name;
  };

  const resetLabelForm = () => {
    setEditingLabelId(null);
    setNewLabel('');
    setNewLabelColor('1');
  };

  const startEditLabelForItem = (item: { id: string; name: string }) => {
    const def = resolveNavLabelDefinition(item, labelDefinitions);
    setEditingLabelId(def.id);
    setNewLabel(def.name);
    setNewLabelColor(def.color);
  };

  const saveLabelDefinition = (): string | null => {
    const normalized = normalizeLabelName(newLabel);
    if (labelKeyword(normalized) === '') {
      return null;
    }
    const mailbox = status?.mailbox ?? '';
    if (editingLabelId === null) {
      persistLabel(newLabel, newLabelColor);
      resetLabelForm();
      return normalized;
    }
    const updated: MailLabelDefinition = {
      id: editingLabelId,
      name: normalized,
      color: newLabelColor,
    };
    setLabelDefinitions((current) => {
      const index = current.findIndex((item) => item.id === editingLabelId);
      const next = index >= 0 ? current.map((item, i) => (i === index ? updated : item)) : [...current, updated];
      writeLabelDefinitions(mailbox, next);
      return next;
    });
    if (labelFilter !== null && findDefinitionByTag(labelFilter, labelDefinitions)?.id === editingLabelId) {
      setLabelFilter(normalized);
    }
    resetLabelForm();
    toast.success(t('platform.mail.toast.labelSaved'));
    return normalized;
  };

  const removeLabelDefinition = async (def: MailLabelDefinition) => {
    if (!(await confirmDestructive(t('platform.mail.confirmDeleteLabel', { name: def.name })))) {
      return;
    }
    const mailbox = status?.mailbox ?? '';
    setLabelDefinitions((current) => {
      const next = current.filter((item) => item.id !== def.id);
      writeLabelDefinitions(mailbox, next);
      return next;
    });
    if (labelFilter !== null && findDefinitionByTag(labelFilter, labelDefinitions)?.id === def.id) {
      setLabelFilter(null);
    }
    for (const row of messages) {
      const tags = row.tags ?? [];
      const tagsToRemove = tags.filter((tag) => messageTagMatchesDefinition(tag, def));
      if (tagsToRemove.length === 0) {
        continue;
      }
      const sourceFolder = imapFolderOf(row, folder);
      const id = rowId(row, folder);
      for (const tag of tagsToRemove) {
        const response = await mailApi.changeFlags(sourceFolder, row.uid, [], [tag]);
        if (!response.success) {
          toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
          return;
        }
      }
      const current = details[id] ?? row;
      patchRow(id, {
        tags: (current.tags ?? []).filter((tag) => !messageTagMatchesDefinition(tag, def)),
      });
    }
    toast.success(t('platform.mail.toast.labelDeleted'));
    await loadMailbox(folder);
  };

  const labelNavItems = useMemo(() => {
    const unique = new Map<string, { id: string; name: string }>();
    for (const def of labelDefinitions) {
      unique.set(def.id, { id: def.id, name: def.name });
    }
    for (const item of messages) {
      for (const tag of item.tags ?? []) {
        const def = findDefinitionByTag(tag, labelDefinitions);
        const id = def?.id ?? tag;
        if (!unique.has(id)) {
          unique.set(id, { id, name: labelDisplayName(tag, labelDefinitions) });
        }
      }
    }
    return [...unique.values()].sort((a, b) => a.name.localeCompare(b.name));
  }, [labelDefinitions, messages]);

  const labelMessageCount = useCallback(
    (labelName: string) =>
      messages.filter((item) => labelMatches(item.tags ?? [], labelName)).length,
    [messages]
  );

  const navFolders = useMemo(() => inboxFirst(folders), [folders]);

  const mailPageSizeOptions = useMemo(() => {
    const base = [5, 10, 20, 50];
    const limit = status?.listLimit ?? 40;
    if (limit > 50 && !base.includes(limit)) {
      return [...base, limit].sort((a, b) => a - b);
    }

    return base;
  }, [status?.listLimit]);

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

  const openedMessageId = expandedId;
  const openedListRow = useMemo(() => {
    if (openedMessageId === null) {
      return null;
    }

    return messages.find((item) => rowId(item, folder) === openedMessageId) ?? null;
  }, [folder, messages, openedMessageId]);
  const openedDetail = openedMessageId !== null ? details[openedMessageId] : undefined;
  const messageViewOpen = openedMessageId !== null && openedListRow !== null;

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

  const resolveAllowRemoteImages = useCallback(
    (row: MailMessage, explicit?: boolean): boolean => {
      if (explicit === true) {
        return true;
      }
      const mailbox = status?.mailbox?.trim() ?? '';
      if (mailbox === '') {
        return false;
      }

      return isTrustedImageSender(mailbox, row.from ?? '');
    },
    [status?.mailbox, trustedSendersTick]
  );

  const showRemoteImagesForMessage = (row: MailMessage) => {
    const mailbox = status?.mailbox?.trim() ?? '';
    const from = row.from ?? '';
    if (mailbox !== '' && from !== '') {
      rememberSenderRemoteImages(mailbox, from);
      setTrustedSendersTick((n) => n + 1);
    }
    void openMessage(row, true);
  };

  const openMessage = async (row: MailMessage, allowRemoteImages?: boolean) => {
    const sourceFolder = imapFolderOf(row, folder);
    const id = rowId(row, folder);
    const allow = resolveAllowRemoteImages(row, allowRemoteImages);
    const message = await mailApi.message(sourceFolder, row.uid, allow);
    if (message === null) {
      toast.error(t('platform.mail.toast.loadFailed'));
      return;
    }
    const merged = { ...message, originFolder: sourceFolder, seen: true, flagged: message.flagged ?? row.flagged };
    setDetails((current) => ({ ...current, [id]: merged }));
    setMessages((current) => current.map((item) => (rowId(item, folder) === id ? { ...item, seen: true } : item)));
  };

  useEffect(() => {
    setMessageHeaderOpen(false);
  }, [expandedId]);

  const openMessageView = (id: string) => {
    if (expandedId === id) {
      return;
    }
    setExpandedId(id);
    const row = messages.find((item) => rowId(item, folder) === id);
    if (row) {
      void openMessage(row);
    }
  };

  const closeMessageView = () => {
    setExpandedId(null);
  };

  const closeMailNav = useCallback(() => {
    setMailNavOpen(false);
  }, []);

  const toggleMailNav = useCallback(() => {
    setMailNavOpen((open) => !open);
  }, []);

  useEffect(() => {
    if (!mailNavOpen) {
      return;
    }
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        closeMailNav();
      }
    };
    window.addEventListener('keydown', onKeyDown);

    return () => window.removeEventListener('keydown', onKeyDown);
  }, [closeMailNav, mailNavOpen]);

  const mobileNavTitle = useMemo(() => {
    if (listFilter === 'starred') {
      return t('platform.mail.starred');
    }
    if (labelFilter !== null) {
      return displayLabel(labelFilter);
    }
    const current = navFolders.find((item) => item.name === folder);

    return current ? folderLabel(current) : folder;
  }, [folder, labelFilter, listFilter, navFolders, t]);

  const mailNavToggleButton = (
    <button
      type="button"
      className="admin-topbar-ghost inline-flex shrink-0 rounded-lg p-2 lg:hidden"
      onClick={toggleMailNav}
      aria-expanded={mailNavOpen}
      aria-controls="mail-app-nav"
      data-testid="mail-nav-toggle"
      aria-label={t('platform.mail.openFoldersMenu')}
    >
      <Menu className="h-5 w-5" aria-hidden />
    </button>
  );

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
    if (!(await confirmDestructive(t('platform.mail.confirmDeleteFolder', { name })))) {
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
    const name = persistLabel(tagDraft, '1');
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

  const submitLabelForm = () => {
    const creating = editingLabelId === null;
    const name = saveLabelDefinition();
    if (creating && name !== null) {
      setListFilter('all');
      setLabelFilter(name);
    }
  };

  const removeLabelFromMessage = async (message: MailMessage, labelRef: string) => {
    const sourceFolder = imapFolderOf(message, folder);
    const id = rowId(message, folder);
    const current = details[id] ?? message;
    const tagsToRemove = messageTagsForLabel(current.tags ?? [], labelRef, labelDefinitions);
    if (tagsToRemove.length === 0) {
      return;
    }
    for (const tag of tagsToRemove) {
      const response = await mailApi.changeFlags(sourceFolder, message.uid, [], [tag]);
      if (!response.success) {
        toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
        return;
      }
    }
    const def =
      findDefinitionByTag(labelRef, labelDefinitions) ??
      resolveNavLabelDefinition({ id: labelRef, name: labelRef }, labelDefinitions);
    patchRow(id, {
      tags: (current.tags ?? []).filter((tag) => !messageTagMatchesDefinition(tag, def)),
    });
    toast.success(t('platform.mail.toast.labelRemovedFromMessage'));
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

  const emptyLocalTrash = async () => {
    if (!(await confirmDestructive(t('platform.mail.confirmEmptyLocalTrash')))) {
      return;
    }
    const response = await mailApi.emptyLocalTrash();
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    const removed = response.data?.removed ?? 0;
    toast.success(t('platform.mail.toast.localTrashEmptied', { count: removed }));
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

  const blockSender = async (message: MailMessage) => {
    const sourceFolder = imapFolderOf(message, folder);
    const from = message.from ?? '';
    if (from.trim() === '') {
      return;
    }
    const response = await mailApi.blockSender(sourceFolder, message.uid, from);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    toast.success(
      t('platform.mail.toast.senderBlocked', { sender: response.data?.blocked ?? parseSenderEmail(from) })
    );
    await loadMailbox(folder);
  };

  const unblockSenderAddress = async (email: string) => {
    const response = await mailApi.unblockSender(email);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
      return;
    }
    if (response.data?.removed === false) {
      toast.warning(t('platform.mail.toast.senderNotBlocked'));
      return;
    }
    toast.success(t('platform.mail.toast.senderUnblocked', { sender: response.data?.unblocked ?? email }));
    await loadMailbox(folder);
  };

  const selectedRows = listView.items.filter((item) => bulkSelection.isSelected(rowId(item, folder)));

  const handleBulkLabel = async (labelName: string) => {
    if (selectedRows.length === 0 || labelName === '') {
      return;
    }
    const tag = labelKeyword(labelName);
    if (tag === '') {
      return;
    }
    for (const row of selectedRows) {
      const sourceFolder = imapFolderOf(row, folder);
      const id = rowId(row, folder);
      const current = details[id] ?? row;
      if (labelMatches(current.tags ?? [], labelName)) {
        continue;
      }
      const response = await mailApi.changeFlags(sourceFolder, row.uid, [tag], []);
      if (!response.success) {
        toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
        return;
      }
      patchRow(id, { tags: Array.from(new Set([...(current.tags ?? []), tag])) });
    }
    bulkSelection.clear();
    setBulkLabelPick('');
    toast.success(t('platform.mail.toast.bulkLabelApplied'));
    await loadMailbox(folder);
  };

  const handleBulkRemoveLabel = async (labelName: string) => {
    if (selectedRows.length === 0 || labelName === '') {
      return;
    }
    const def =
      findDefinitionByTag(labelName, labelDefinitions) ??
      resolveNavLabelDefinition({ id: labelKeyword(labelName), name: labelName }, labelDefinitions);
    for (const row of selectedRows) {
      const sourceFolder = imapFolderOf(row, folder);
      const id = rowId(row, folder);
      const current = details[id] ?? row;
      const tagsToRemove = messageTagsForLabel(current.tags ?? [], labelName, labelDefinitions);
      if (tagsToRemove.length === 0) {
        continue;
      }
      for (const tag of tagsToRemove) {
        const response = await mailApi.changeFlags(sourceFolder, row.uid, [], [tag]);
        if (!response.success) {
          toast.error(describeMailError(firstError(response), 'platform.mail.toast.loadFailed'));
          return;
        }
      }
      patchRow(id, {
        tags: (current.tags ?? []).filter((tag) => !messageTagMatchesDefinition(tag, def)),
      });
    }
    bulkSelection.clear();
    setBulkLabelPick('');
    toast.success(t('platform.mail.toast.bulkLabelRemoved'));
    await loadMailbox(folder);
  };

  const bulkLabelOptions = useMemo(() => {
    const names = new Map<string, string>();
    for (const def of labelDefinitions) {
      names.set(def.name, def.name);
    }
    for (const row of selectedRows) {
      for (const tag of row.tags ?? []) {
        const name = labelDisplayName(tag, labelDefinitions);
        names.set(name, name);
      }
    }
    return Array.from(names.values()).sort((a, b) => a.localeCompare(b));
  }, [labelDefinitions, selectedRows]);

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
    lastDraftFingerprintRef.current = '';
    setCompose({ to: '', subject: '', body: '' });
  };

  const openReply = (message: MailMessage) => {
    lastDraftFingerprintRef.current = '';
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
    const validated = validateRecipientList(compose.to);
    if (!validated.ok) {
      toast.error(t('platform.mail.toast.recipientInvalid'));
      return;
    }
    const payload = {
      to: formatRecipientList(validated.recipients),
      subject: compose.subject.trim(),
      body: compose.body.trim(),
    };
    if (payload.body === '') {
      toast.error(t('platform.mail.toast.bodyInvalid'));
      return;
    }
    setSending(true);
    try {
      const response = await mailApi.send(payload);
      if (!response.success) {
        toast.error(describeMailError(firstError(response), 'platform.mail.toast.sendFailed'));
        return;
      }
      toast.success(t('platform.mail.toast.sent'));
      if (status?.mailbox) {
        rememberRecipients(status.mailbox, validated.recipients);
        setRecipientHintsTick((tick) => tick + 1);
      }
      setCompose(null);
      const sentFolder = folders.find((item) => mailNavKind(item.name) === 'sent')?.name;
      if (sentFolder) {
        setFolder(sentFolder);
        await loadMailbox(sentFolder);
      } else {
        await loadMailbox(folder);
      }
    } finally {
      setSending(false);
    }
  };

  const saveDraft = async () => {
    if (compose === null || sending) {
      return;
    }
    await persistDraft(false);
  };

  const closeCompose = async () => {
    if (compose !== null && !isComposeEmpty(compose)) {
      await persistDraft(true);
    }
    setCompose(null);
  };

  const selectFolder = async (name: string) => {
    await flushDraftBeforeLeave();
    setListFilter('all');
    setLabelFilter(null);
    setFolder(name);
    closeMailNav();
  };

  const deleteLocalCopy = async (message: MailMessage) => {
    const response = await mailApi.deleteLocalMessage(folder, message.uid);
    if (!response.success) {
      toast.error(describeMailError(firstError(response), 'platform.mail.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.mail.toast.deletedLocal'));
    setExpandedId(null);
    await loadMailbox(folder);
  };

  const switchAccount = async (mailbox: string) => {
    if (mailbox === '' || mailbox === status?.mailbox) {
      return;
    }
    await flushDraftBeforeLeave();
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
    if (!(await confirmDestructive(t('platform.mail.confirmRemoveAccount', { mailbox })))) {
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

  const recipientAddressHints = useMemo(() => {
    const mailbox = status?.mailbox ?? '';
    if (mailbox === '') {
      return [];
    }
    const rows = messages.map((item) => {
      const detail = details[rowId(item, folder)];
      return {
        from: detail?.from ?? item.from,
        to: detail?.to ?? item.to,
      };
    });
    return collectMailboxAddressHints(rows, readRecentRecipients(mailbox));
  }, [details, folder, messages, recipientHintsTick, status?.mailbox]);

  const refreshMailbox = useCallback(async () => {
    if (!mailboxReady || mailboxRefreshing) {
      return;
    }
    setMailboxRefreshing(true);
    try {
      await loadMailbox(folder);
      toast.success(t('platform.mail.toast.refreshed'));
    } catch {
      toast.error(t('platform.mail.toast.loadFailed'));
    } finally {
      setMailboxRefreshing(false);
    }
  }, [folder, loadMailbox, mailboxReady, mailboxRefreshing, t, toast]);

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

  const mobileListOnly = mailboxReady && compose === null;

  return (
    <div
      className={`relative w-full max-w-none pb-24 ${mobileListOnly ? 'max-lg:space-y-0 max-lg:pb-20' : 'space-y-6'}`}
      data-testid="mail-inbox"
    >
      <div className={`flex flex-wrap items-start justify-between gap-3 ${mobileListOnly ? 'hidden lg:flex' : ''}`}>
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
        <p className={`text-sm text-admin-muted ${mobileListOnly ? 'hidden lg:block' : ''}`}>
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
                list="mail-compose-recipient-hints"
                placeholder={t('platform.mail.recipientsPlaceholder')}
                data-testid="mail-compose-to"
              />
              <datalist id="mail-compose-recipient-hints">
                {recipientAddressHints.map((email) => (
                  <option key={email} value={email} />
                ))}
              </datalist>
              <span className="mt-1 block text-xs text-admin-muted">{t('platform.mail.recipientsHint')}</span>
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
              <button type="button" className="btn btn-secondary" onClick={() => void saveDraft()} disabled={sending} data-testid="mail-save-draft">
                {t('platform.mail.saveDraft')}
              </button>
              <button type="button" className="btn btn-secondary" onClick={() => void closeCompose()} disabled={sending}>
                {t('common.cancel')}
              </button>
            </div>
          </div>
        </AdminWidgetCard>
      ) : null}

      {mailboxReady ? (
        <div className={`mail-app ${mailNavOpen ? 'mail-app-nav-drawer-open' : ''}`}>
          {mailNavOpen ? (
            <button
              type="button"
              className="mail-app-nav-backdrop lg:hidden"
              aria-label={t('platform.mail.closeFoldersMenu')}
              onClick={closeMailNav}
              data-testid="mail-nav-backdrop"
            />
          ) : null}
          <aside id="mail-app-nav" className="mail-app-nav" data-testid="mail-app-nav">
            {canSend ? (
              <button
                type="button"
                ref={composeAnchorRef}
                className="btn btn-primary mb-2 w-full"
                onClick={() => {
                  closeMailNav();
                  openCompose();
                }}
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
                      onClick={() => void selectFolder(item.name)}
                      aria-current={active ? 'page' : undefined}
                      data-testid={`mail-folder-${item.name}`}
                    >
                      <Icon className="h-4 w-4 shrink-0" aria-hidden />
                      <span className="min-w-0 truncate">{folderLabel(item)}</span>
                      {(() => {
                        const badge =
                          kind === 'inbox'
                            ? (item.unseen ?? (folder === item.name ? unread : 0))
                            : (item.total ?? 0);
                        return badge > 0 ? <span className="mail-nav-count">{badge}</span> : null;
                      })()}
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
                  closeMailNav();
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
              {labelNavItems.map((item) => {
                const def = resolveNavLabelDefinition(item, labelDefinitions);
                return (
                  <div key={item.id} className="flex min-w-0 items-center gap-0.5">
                    <button
                      type="button"
                      className={`${mailNavClass(labelFilter !== null && labelMatches([labelFilter], item.name))} min-w-0 flex-1`}
                      onClick={() => {
                        setListFilter('all');
                        setLabelFilter(item.name);
                        closeMailNav();
                      }}
                      aria-current={labelFilter !== null && labelMatches([labelFilter], item.name) ? 'page' : undefined}
                      data-testid={`mail-label-${item.id}`}
                    >
                      <span {...mailTagDotAttrs(item.id, labelDefinitions)} aria-hidden />
                      <span className="min-w-0 truncate">{displayLabel(item.name)}</span>
                      {labelMessageCount(item.name) > 0 ? (
                        <span className="mail-nav-count">{labelMessageCount(item.name)}</span>
                      ) : null}
                    </button>
                    <div className="flex shrink-0 gap-0.5 pr-1">
                      <button
                        type="button"
                        className="rounded p-1 text-admin-muted hover:bg-admin-sidebar-hover hover:text-admin-text"
                        onClick={() => startEditLabelForItem(item)}
                        aria-label={t('platform.mail.editLabel', { name: def.name })}
                        data-testid={`mail-label-edit-${item.id}`}
                      >
                        <Pencil className="h-3.5 w-3.5" aria-hidden />
                      </button>
                      <button
                        type="button"
                        className="rounded p-1 text-admin-muted hover:bg-admin-sidebar-hover hover:text-red-600"
                        onClick={() => void removeLabelDefinition(def)}
                        aria-label={t('platform.mail.deleteLabel', { name: def.name })}
                        data-testid={`mail-label-delete-${item.id}`}
                      >
                        <Trash2 className="h-3.5 w-3.5" aria-hidden />
                      </button>
                    </div>
                  </div>
                );
              })}
            </nav>
            <form
              className="mt-2 flex flex-col gap-2"
              onSubmit={(event) => {
                event.preventDefault();
                submitLabelForm();
              }}
            >
              <label className="block min-w-0 text-sm font-medium text-[#132337] dark:text-admin-text">
                {editingLabelId ? t('platform.mail.editLabelTitle') : t('platform.mail.newLabel')}
                <input
                  className={`${MAIL_FIELD} mt-1`}
                  value={newLabel}
                  onChange={(event) => setNewLabel(event.target.value)}
                  placeholder={t('platform.mail.newLabelPlaceholder')}
                  autoComplete="off"
                  data-testid="mail-new-label"
                />
              </label>
              <fieldset className="space-y-1">
                <legend className="text-xs font-medium text-admin-muted">{t('platform.mail.labelColor')}</legend>
                <div className="flex flex-wrap items-center gap-2" data-testid="mail-label-colors">
                  {MAIL_LABEL_COLORS.map((color) => (
                    <button
                      key={color}
                      type="button"
                      className={`mail-tag-dot mail-tag-${color} h-7 w-7 rounded-full border-2 ${
                        newLabelColor === color ? 'border-admin-primary' : 'border-transparent'
                      }`}
                      aria-pressed={newLabelColor === color}
                      aria-label={t('platform.mail.labelColorOption', { color })}
                      onClick={() => setNewLabelColor(color)}
                    />
                  ))}
                  <label className="inline-flex items-center gap-2 text-xs text-admin-muted">
                    <span className="sr-only">{t('platform.mail.labelColorPicker')}</span>
                    <input
                      type="color"
                      className="h-9 w-12 cursor-pointer rounded border border-admin-border bg-admin-canvas p-0.5"
                      value={labelColorToPickerValue(newLabelColor)}
                      onChange={(event) => {
                        const hex = normalizeHexColor(event.target.value);
                        if (hex !== null) {
                          setNewLabelColor(hex);
                        }
                      }}
                      data-testid="mail-label-color-picker"
                    />
                    <span aria-hidden>{t('platform.mail.labelColorPicker')}</span>
                  </label>
                </div>
              </fieldset>
              <div className="flex flex-col gap-2 sm:flex-row">
                <button type="submit" className="btn btn-secondary w-full sm:flex-1" data-testid="mail-add-label">
                  {editingLabelId ? t('platform.mail.saveLabel') : t('platform.mail.addLabel')}
                </button>
                {editingLabelId ? (
                  <button type="button" className="btn btn-secondary w-full sm:w-auto" onClick={resetLabelForm}>
                    {t('common.cancel')}
                  </button>
                ) : null}
              </div>
            </form>
            <div className="mt-4 border-t border-admin-border pt-3">
              <button
                type="button"
                className="mail-nav-item w-full justify-between"
                onClick={() => setSignaturePanelOpen((open) => !open)}
                aria-expanded={signaturePanelOpen}
                data-testid="mail-signature-toggle"
              >
                <span className="flex min-w-0 items-center gap-2">
                  <Contact className="h-4 w-4 shrink-0" aria-hidden />
                  <span className="truncate">{t('platform.mail.signatureTitle')}</span>
                </span>
                {signatureDraftPrefs?.enabled ? (
                  <span className="mail-nav-count">ON</span>
                ) : null}
              </button>
              {signaturePanelOpen && signatureDraftFields && signatureDraftPrefs && signatureState ? (
                <MailSignaturePanel
                  mailbox={signatureState.mailbox}
                  templates={signatureState.templates}
                  prefs={signatureDraftPrefs}
                  fields={signatureDraftFields}
                  previewHtml={signatureState.previewHtml}
                  saving={signatureSaving}
                  onChangePrefs={setSignatureDraftPrefs}
                  onChangeFields={setSignatureDraftFields}
                  onSave={() => void saveSignature()}
                  onImportProfile={() => void importSignatureProfile()}
                />
              ) : null}
            </div>
            <div className="mt-4 border-t border-admin-border pt-3">
              <button
                type="button"
                className="mail-nav-item w-full justify-between"
                onClick={() => setBlockedPanelOpen((open) => !open)}
                aria-expanded={blockedPanelOpen}
                data-testid="mail-blocked-toggle"
              >
                <span className="flex min-w-0 items-center gap-2">
                  <Ban className="h-4 w-4 shrink-0" aria-hidden />
                  <span className="truncate">{t('platform.mail.blockedSenders')}</span>
                </span>
                {blockedSenders.length > 0 ? (
                  <span className="mail-nav-count">{blockedSenders.length}</span>
                ) : null}
              </button>
              {blockedPanelOpen ? (
                <div className="mt-2 space-y-2" data-testid="mail-blocked-panel">
                  <p className="text-xs text-admin-muted">{t('platform.mail.blockedSendersHint')}</p>
                  {blockedSenders.length === 0 ? (
                    <p className="text-sm text-admin-muted">{t('platform.mail.blockedSendersEmpty')}</p>
                  ) : (
                    <ul className="max-h-48 space-y-1 overflow-y-auto">
                      {blockedSenders.map((email) => (
                        <li
                          key={email}
                          className="flex items-center justify-between gap-2 rounded-md border border-admin-border px-2 py-1.5 text-xs"
                        >
                          <span className="min-w-0 truncate text-admin-text" title={email}>
                            {email}
                          </span>
                          <button
                            type="button"
                            className="btn btn-secondary shrink-0 px-2 py-0.5 text-[11px]"
                            data-testid={`mail-unblock-${email}`}
                            onClick={() => void unblockSenderAddress(email)}
                          >
                            {t('platform.mail.unblockSender')}
                          </button>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ) : null}
            </div>
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
            {messageViewOpen && openedListRow ? (
              <>
                <div className="mail-app-toolbar flex flex-col gap-2 border-b border-admin-border px-3 py-3 sm:px-4">
                  <div className="flex min-w-0 items-center gap-2 sm:gap-3">
                    {mailNavToggleButton}
                    <button
                      type="button"
                      className="btn btn-secondary flex shrink-0 items-center gap-1 px-2 py-1.5 text-sm"
                      onClick={closeMessageView}
                      data-testid="mail-back-to-list"
                    >
                      <ArrowLeft className="h-4 w-4" aria-hidden />
                      {t('platform.mail.backToList')}
                    </button>
                    <h2 className="min-w-0 flex-1 truncate text-base font-semibold text-admin-text">
                      {(openedDetail?.subject ?? openedListRow.subject) || t('platform.mail.noSubject')}
                    </h2>
                    <span className="hidden shrink-0 text-xs text-admin-muted sm:inline">{openedListRow.date}</span>
                  </div>
                  <div className="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 text-xs text-admin-muted">
                    <span className="min-w-0 truncate">
                      {t('platform.mail.from')}: {openedDetail?.from ?? openedListRow.from}
                    </span>
                    <button
                      type="button"
                      className="inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-0.5 text-admin-primary hover:bg-admin-sidebar-hover"
                      aria-expanded={messageHeaderOpen}
                      onClick={() => setMessageHeaderOpen((open) => !open)}
                      data-testid="mail-message-details-toggle"
                    >
                      <ChevronDown
                        className={`h-3.5 w-3.5 transition-transform ${messageHeaderOpen ? 'rotate-180' : ''}`}
                        aria-hidden
                      />
                      {messageHeaderOpen ? t('platform.mail.hideDetails') : t('platform.mail.showDetails')}
                    </button>
                  </div>
                  {messageHeaderOpen ? (
                    <dl
                      className="grid gap-2 rounded-lg border border-admin-border bg-admin-canvas/60 p-3 text-xs text-admin-text sm:grid-cols-2"
                      data-testid="mail-message-details"
                    >
                      <div className="min-w-0 sm:col-span-2">
                        <dt className="font-medium text-admin-muted">{t('platform.mail.from')}</dt>
                        <dd className="break-words">{openedDetail?.from ?? openedListRow.from}</dd>
                      </div>
                      <div className="min-w-0 sm:col-span-2">
                        <dt className="font-medium text-admin-muted">{t('platform.mail.to')}</dt>
                        <dd className="break-words">
                          {openedDetail?.to?.trim() ||
                            openedListRow.to?.trim() ||
                            (mailNavKind(folder) === 'sent' ? '—' : status?.mailbox || '—')}
                        </dd>
                      </div>
                      {openedDetail?.cc?.trim() ? (
                        <div className="min-w-0 sm:col-span-2">
                          <dt className="font-medium text-admin-muted">{t('platform.mail.cc')}</dt>
                          <dd className="break-words">{openedDetail.cc}</dd>
                        </div>
                      ) : null}
                      {openedDetail?.replyTo?.trim() ? (
                        <div className="min-w-0 sm:col-span-2">
                          <dt className="font-medium text-admin-muted">{t('platform.mail.replyTo')}</dt>
                          <dd className="break-words">{openedDetail.replyTo}</dd>
                        </div>
                      ) : null}
                      <div>
                        <dt className="font-medium text-admin-muted">{t('platform.mail.date')}</dt>
                        <dd>{openedDetail?.date ?? openedListRow.date}</dd>
                      </div>
                      <div>
                        <dt className="font-medium text-admin-muted">{t('platform.mail.detailFolder')}</dt>
                        <dd>{imapFolderOf(openedListRow, folder)}</dd>
                      </div>
                      <div>
                        <dt className="font-medium text-admin-muted">{t('platform.mail.detailAccount')}</dt>
                        <dd className="break-all">{status?.mailbox ?? '—'}</dd>
                      </div>
                      <div>
                        <dt className="font-medium text-admin-muted">{t('platform.mail.detailUid')}</dt>
                        <dd>{openedListRow.uid}</dd>
                      </div>
                      {(openedDetail?.tags ?? openedListRow.tags ?? []).length > 0 ? (
                        <div className="min-w-0 sm:col-span-2">
                          <dt className="mb-1 font-medium text-admin-muted">{t('platform.mail.tags')}</dt>
                          <dd className="flex flex-wrap gap-1">
                            {(openedDetail?.tags ?? openedListRow.tags ?? []).map((tag) => (
                              <button
                                key={tag}
                                type="button"
                                {...mailTagAttrs(tag, labelDefinitions)}
                                onClick={() => void removeLabelFromMessage(openedDetail ?? openedListRow, tag)}
                                title={t('platform.mail.removeTag')}
                                data-testid={`mail-tag-remove-${tag}`}
                              >
                                {labelDisplayName(tag, labelDefinitions)} ×
                              </button>
                            ))}
                          </dd>
                        </div>
                      ) : null}
                      {openedDetail?.localOnly || openedListRow.localOnly ? (
                        <div className="sm:col-span-2">
                          <dt className="font-medium text-admin-muted">{t('platform.mail.detailStorage')}</dt>
                          <dd>{t('platform.mail.detailLocalCopy')}</dd>
                        </div>
                      ) : null}
                    </dl>
                  ) : null}
                </div>
                <div className="space-y-3 p-4 text-sm" data-testid="mail-message-view">
                  <div className="flex flex-wrap gap-2">
                    {canSend && !inLocalTrash ? (
                      <button
                        type="button"
                        className="btn btn-primary px-2 py-1 text-xs"
                        onClick={() => openReply(openedDetail ?? openedListRow)}
                        data-testid="mail-reply"
                      >
                        <Reply className="mr-1 inline h-3 w-3" />
                        {t('platform.mail.reply')}
                      </button>
                    ) : null}
                    <button
                      type="button"
                      className="btn btn-secondary px-2 py-1 text-xs"
                      onClick={() => void toggleFlag(openedDetail ?? openedListRow, 'flagged')}
                    >
                      <Star
                        className={`mr-1 inline h-3 w-3 ${openedDetail?.flagged || openedListRow.flagged ? 'fill-current text-amber-400' : ''}`}
                      />
                      {t('platform.mail.star')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-secondary px-2 py-1 text-xs"
                      onClick={() => void toggleFlag(openedDetail ?? openedListRow, 'seen')}
                    >
                      {(openedDetail?.seen ?? openedListRow.seen) ? (
                        <MailOpen className="mr-1 inline h-3 w-3" />
                      ) : (
                        <Mail className="mr-1 inline h-3 w-3" />
                      )}
                      {(openedDetail?.seen ?? openedListRow.seen) ? t('platform.mail.markUnread') : t('platform.mail.markRead')}
                    </button>
                    {inLocalTrash ? (
                      <button type="button" className="btn btn-secondary px-2 py-1 text-xs" onClick={() => void restoreLocal(openedListRow)}>
                        <RotateCcw className="mr-1 inline h-3 w-3" />
                        {t('platform.mail.restore')}
                      </button>
                    ) : openedDetail?.localOnly || openedListRow.localOnly ? (
                      <button
                        type="button"
                        className="btn btn-danger px-2 py-1 text-xs"
                        onClick={() => void deleteLocalCopy(openedListRow)}
                        data-testid="mail-delete-local"
                      >
                        <Trash2 className="mr-1 inline h-3 w-3" />
                        {t('platform.mail.deleteLocalPermanent')}
                      </button>
                    ) : (
                      <>
                        <button
                          type="button"
                          className="btn btn-secondary px-2 py-1 text-xs"
                          onClick={() => void hideLocal(openedListRow)}
                          data-testid="mail-hide"
                        >
                          <Trash2 className="mr-1 inline h-3 w-3" />
                          {t('platform.mail.hideLocal')}
                        </button>
                        <button
                          type="button"
                          className="btn btn-danger px-2 py-1 text-xs"
                          onClick={() => void moveSpam(openedListRow)}
                          data-testid="mail-move-spam"
                        >
                          {t('platform.mail.moveSpam')}
                        </button>
                        <button
                          type="button"
                          className="btn btn-danger px-2 py-1 text-xs"
                          onClick={() => void blockSender(openedDetail ?? openedListRow)}
                          data-testid="mail-block-sender"
                        >
                          <Ban className="mr-1 inline h-3 w-3" />
                          {t('platform.mail.blockSender')}
                        </button>
                      </>
                    )}
                  </div>
                  {(openedDetail?.tags ?? openedListRow.tags ?? []).length > 0 ? (
                    <div className="flex flex-wrap items-center gap-2" data-testid="mail-message-labels">
                      <span className="text-xs font-medium text-admin-muted">{t('platform.mail.tags')}</span>
                      {(openedDetail?.tags ?? openedListRow.tags ?? []).map((tag) => (
                        <button
                          key={`chip-${tag}`}
                          type="button"
                          {...mailTagAttrs(tag, labelDefinitions)}
                          onClick={() => void removeLabelFromMessage(openedDetail ?? openedListRow, tag)}
                          title={t('platform.mail.removeTag')}
                          data-testid={`mail-tag-remove-${tag}`}
                        >
                          {labelDisplayName(tag, labelDefinitions)} ×
                        </button>
                      ))}
                    </div>
                  ) : null}
                  {openedDetail?.remoteImagesBlocked ? (
                    <div className="flex flex-col gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-admin-text">
                      <span>{t('platform.mail.remoteImagesBlockedHint')}</span>
                      <button
                        type="button"
                        className="btn btn-secondary px-2 py-1 text-xs"
                        data-testid="mail-load-remote-images"
                        onClick={() => showRemoteImagesForMessage(openedDetail ?? openedListRow)}
                      >
                        {t('platform.mail.loadRemoteImages', {
                          sender: parseSenderEmail(openedDetail?.from ?? openedListRow.from),
                        })}
                      </button>
                    </div>
                  ) : null}
                  {!openedDetail ? (
                    <AdminListSkeleton rows={4} />
                  ) : openedDetail.html ? (
                    <iframe
                      title={openedDetail.subject || t('platform.mail.selectMessage')}
                      sandbox="allow-popups allow-popups-to-escape-sandbox"
                      referrerPolicy="no-referrer"
                      srcDoc={openedDetail.html}
                      className="h-[min(72vh,48rem)] w-full rounded-lg border border-admin-border bg-white"
                      data-testid="mail-html-frame"
                    />
                  ) : (
                    <p className="whitespace-pre-wrap text-admin-text">{openedDetail.body ?? openedListRow.snippet}</p>
                  )}
                  <div className="flex flex-col gap-2 sm:flex-row">
                    <input
                      className={MAIL_FIELD}
                      value={tagDraft}
                      onChange={(event) => setTagDraft(event.target.value)}
                      placeholder={t('platform.mail.addTag')}
                      aria-label={t('platform.mail.tags')}
                      data-testid="mail-tag-input"
                    />
                    <button type="button" className="btn btn-secondary shrink-0" onClick={() => void addTag(openedDetail ?? openedListRow)}>
                      {t('platform.mail.addTag')}
                    </button>
                  </div>
                </div>
              </>
            ) : (
              <>
                <div className="mail-mobile-chrome flex items-center gap-2 border-b border-admin-border px-3 py-2 lg:hidden">
                  {mailNavToggleButton}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-admin-text">{mobileNavTitle}</p>
                    {accounts.length > 1 ? (
                      <label className="mt-0.5 block min-w-0">
                        <span className="sr-only">{t('platform.mail.accounts')}</span>
                        <select
                          className="w-full max-w-full truncate rounded border border-admin-border bg-admin-canvas px-1.5 py-0.5 text-xs text-admin-text"
                          value={status?.mailbox ?? ''}
                          onChange={(event) => void switchAccount(event.target.value)}
                          data-testid="mail-account-mobile"
                        >
                          {accounts.map((item) => (
                            <option key={item.mailbox} value={item.mailbox}>
                              {item.mailbox}
                            </option>
                          ))}
                        </select>
                      </label>
                    ) : status?.mailbox ? (
                      <p className="truncate text-xs text-admin-muted">{status.mailbox}</p>
                    ) : null}
                  </div>
                </div>
                <div className="mail-app-toolbar">
                  <AdminListToolbar
                    search={search}
                    onSearchChange={setSearch}
                    searchPlaceholder={t('platform.mail.search')}
                    pageSize={pageSize}
                    onPageSizeChange={setPageSize}
                    pageSizeOptions={mailPageSizeOptions}
                  >
                    {inLocalTrash && messages.length > 0 ? (
                      <button
                        type="button"
                        className="btn btn-danger shrink-0 text-sm"
                        onClick={() => void emptyLocalTrash()}
                        data-testid="mail-empty-local-trash"
                      >
                        <Trash2 className="mr-1 inline h-4 w-4" aria-hidden />
                        {t('platform.mail.emptyLocalTrash')}
                      </button>
                    ) : null}
                    <button
                      type="button"
                      className="btn btn-secondary shrink-0 text-sm"
                      onClick={() => void refreshMailbox()}
                      disabled={mailboxRefreshing}
                      data-testid="mail-refresh"
                      title={t('platform.mail.syncHint')}
                    >
                      <RotateCcw className={`mr-1 inline h-4 w-4 ${mailboxRefreshing ? 'animate-spin' : ''}`} aria-hidden />
                      {mailboxRefreshing ? t('platform.mail.refreshing') : t('platform.mail.refresh')}
                    </button>
                  </AdminListToolbar>
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
                {bulkSelection.count > 0 && bulkLabelOptions.length > 0 ? (
                  <div className="flex flex-wrap items-center gap-2 px-4 pb-2" data-testid="mail-bulk-label">
                    <label className="flex min-w-0 flex-1 items-center gap-2 text-sm text-admin-muted sm:flex-none">
                      <span className="shrink-0">{t('platform.mail.bulk.applyLabel')}</span>
                      <select
                        className={`${MAIL_FIELD} min-w-[10rem]`}
                        value={bulkLabelPick}
                        onChange={(event) => setBulkLabelPick(event.target.value)}
                        data-testid="mail-bulk-label-select"
                      >
                        <option value="">{t('platform.mail.bulk.chooseLabel')}</option>
                        {bulkLabelOptions.map((name) => (
                          <option key={labelKeyword(name)} value={name}>
                            {displayLabel(name)}
                          </option>
                        ))}
                      </select>
                    </label>
                    <button
                      type="button"
                      className="btn btn-secondary text-xs"
                      disabled={bulkLabelPick === ''}
                      onClick={() => void handleBulkLabel(bulkLabelPick)}
                      data-testid="mail-bulk-label-apply"
                    >
                      {t('platform.mail.bulk.applyLabelConfirm')}
                    </button>
                    <button
                      type="button"
                      className="btn btn-secondary text-xs"
                      disabled={bulkLabelPick === ''}
                      onClick={() => void handleBulkRemoveLabel(bulkLabelPick)}
                      data-testid="mail-bulk-label-remove"
                    >
                      {t('platform.mail.bulk.removeLabelConfirm')}
                    </button>
                  </div>
                ) : null}

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
                            expanded={false}
                            navigationMode
                            onToggleExpand={openMessageView}
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
                                    <span key={tag} {...mailTagAttrs(tag, labelDefinitions, 'mr-1.5')}>
                                      {labelDisplayName(tag, labelDefinitions)}
                                    </span>
                                  ))}
                                  <span className={message.seen ? 'text-admin-muted' : 'text-admin-text'}>
                                    {message.subject || t('platform.mail.noSubject')}
                                  </span>
                                  {message.snippet !== '' ? <span className="text-admin-muted"> — {message.snippet}</span> : null}
                                </span>
                                <span className="shrink-0 whitespace-nowrap text-xs text-admin-muted">{message.date}</span>
                              </div>
                            }
                            detail={<></>}
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
