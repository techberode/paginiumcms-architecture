// src/api/types.ts
export interface User {
  id: string;
  email: string;
  name: string;
  roles: string[];
  twoFactorEnabled: boolean;
  createdAt: number;
  updatedAt: number;
}

export interface AuthResponse {
  success: boolean;
  user?: User;
  requires_two_factor?: boolean;
  message?: string;
  error?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  name: string;
}

export interface ChangePasswordRequest {
  old_password: string;
  new_password: string;
}

export interface ResetPasswordRequest {
  email: string;
}

export interface VerifyResetTokenRequest {
  token: string;
  new_password: string;
}

export interface TwoFactorEnableResponse {
  success: boolean;
  secret: string;
  qr_code: string;
  provisioning_uri: string;
  message: string;
  enabled: boolean;
}

export interface TwoFactorVerifyRequest {
  code: string;
}

export interface Content {
  path: string;
  frontMatter: Record<string, any>;
  content: string;
  html: string;
  size: number;
  modifiedAt: number;
}

export interface Page extends Content {
  template?: string;
}

export interface Article extends Content {
  featuredImage?: string;
}
