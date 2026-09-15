import apiClient from './client';

export interface PublicStaffCard {
  id: string;
  name: string;
  jobTitle?: string;
  bio?: string;
  avatarUrl?: string | null;
  phone?: string;
  email?: string;
  address?: { street?: string; city?: string; postal?: string; country?: string };
  experience?: Array<{ org: string; role: string; years?: string }>;
  education?: Array<{ school: string; field: string; years?: string }>;
  socials?: Array<{ platform: string; label: string; url: string; directChat: boolean }>;
}

export interface PublicStaffLists {
  contacts: PublicStaffCard[];
  support: PublicStaffCard[];
}

export async function fetchPublicStaff(): Promise<PublicStaffLists> {
  const res = await apiClient.get<PublicStaffLists>('/api/public/staff');
  if (!res.success || !res.data) {
    return { contacts: [], support: [] };
  }
  return {
    contacts: Array.isArray(res.data.contacts) ? res.data.contacts : [],
    support: Array.isArray(res.data.support) ? res.data.support : [],
  };
}
