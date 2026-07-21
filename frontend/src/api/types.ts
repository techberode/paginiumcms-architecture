// frontend/src/api/types.ts
export interface User {
  id: string;
  email: string;
  username?: string;
  name: string;
  roles: string[];
  active?: boolean;
  twoFactorEnabled: boolean;
  twoFactorSecret?: string;
  twoFactorVerifiedAt?: number | null;
  avatarUrl?: string | null;
  createdAt: number;
  updatedAt: number;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  success: boolean;
  user: User;
  requires_two_factor?: boolean;
  token?: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  name: string;
}

export interface RegisterResponse {
  success: boolean;
  user: User;
}

export interface Page {
  id: string;
  title: string;
  slug: string;
  content: string;
  frontMatter: Record<string, any>;
  html: string;
  status: 'draft' | 'published' | 'archived';
  author: string;
  createdAt: string;
  updatedAt: string;
  template?: string;
}

export interface Article extends Page {
  featuredImage: string;
  ogImage?: string;
  tags: string[];
  excerpt: string;
  readingTime: number;
  commentsEnabled?: boolean;
  commentsRequireApproval?: boolean | null;
  commentsAllowGuests?: boolean | null;
}

export interface Backup {
  id: string;
  name: string;
  createdAt: string;
  size: number;
  sizeFormatted: string;
  filePath: string;
  status: 'in_progress' | 'completed' | 'failed';
  includes: string[];
  sha256?: string;
}

export interface BackupVerifyResult {
  valid: boolean;
  expected: string;
  actual: string | null;
  reason?: string;
}

export interface Version {
  id: string;
  contentId: string;
  contentType: string;
  version: number;
  content: string;
  frontMatter: string;
  createdAt: string;
  createdBy: string;
  message: string;
  diff: {
    additions: number;
    deletions: number;
    modifications: number;
    content: string;
    summary: string;
    lines: any[];
  } | null;
}

export interface AuditEvent {
  type: 'version' | 'access' | 'admin' | 'security';
  version?: Version;
  log: {
    category: string;
    action: string;
    target: string;
    severity: string;
    message: string;
    timestamp: string;
    context: Record<string, any>;
  };
  timestamp: string;
  user: {
    id: string;
    email: string;
    name: string;
  } | null;
}

export interface HealthCheck {
  name: string;
  description: string;
  group: string;
  status: 'pass' | 'fail' | 'warn' | 'skip';
  message: string;
  data: Record<string, any>;
  duration: number;
  timestamp: string;
}

export interface HealthReport {
  id: string;
  timestamp: string;
  status: 'pass' | 'fail' | 'warn';
  summary: {
    total: number;
    pass: number;
    fail: number;
    warn: number;
    skip: number;
  };
  checks: HealthCheck[];
}

export interface FileInfo {
  path: string;
  name: string;
  size: number;
  modified: number;
  extension: string;
  language: string;
  editable: boolean;
  backups?: string[];
  permissions?: string;
}

export interface CodeEditorFile {
  path: string;
  content: string;
  language: string;
  info: FileInfo;
}

export interface AuditStats {
  total_events: number;
  by_category: Record<string, number>;
  by_action: Record<string, number>;
  by_user: Record<string, number>;
  by_severity: Record<string, number>;
  recent_events: AuditEvent[];
  timeline: Record<string, number>;
}

export interface ApiErrorResponse {
  success: false;
  error: string;
  message?: string;
}

export interface MediaFile {
  id: string;
  path: string;
  fileName: string;
  url: string;
  sizeBytes: number;
  mimeType: string;
  uploadedAt: number;
  altText: string;
  folder?: string;
  title?: string;
}

export interface NavigationItem {
  id: string;
  label: string;
  path: string;
  target: '_self' | '_blank' | '_parent' | '_top';
  order: number;
  parentId: string | null;
  icon: string | null;
}

export interface Navigation {
  items: NavigationItem[];
}

export interface ScheduleInfo {
  enabled: boolean;
  interval?: 'daily' | 'weekly' | 'monthly';
  keep?: number;
  next_run?: string;
  last_run?: string;
}
