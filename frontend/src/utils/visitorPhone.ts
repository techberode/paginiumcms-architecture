export const DEFAULT_PHONE_PREFIX = '+421';

export function composeVisitorPhone(prefix: string, nationalNumber: string): string {
  const normalizedPrefix = prefix.replace(/[\s\-().]+/g, '');
  const digits = nationalNumber.replace(/\D+/g, '');
  return `${normalizedPrefix}${digits}`;
}

export function isValidVisitorPhone(prefix: string, nationalNumber: string): boolean {
  return /^\+[1-9]\d{7,14}$/.test(composeVisitorPhone(prefix, nationalNumber));
}
