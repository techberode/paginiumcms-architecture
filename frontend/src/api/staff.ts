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
  chatEnabled?: boolean;
  online?: boolean;
}

export interface StaffChatStatus {
  chatEnabled: boolean;
  online: boolean;
  lastSeen: number;
  inSupportTeam: boolean;
  canPublicChat: boolean;
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

export async function fetchStaffCards(query: {
  user?: string;
  type?: string;
  team?: string;
}): Promise<PublicStaffCard[]> {
  const params = new URLSearchParams();
  if (query.user) params.set('user', query.user);
  if (query.type) params.set('type', query.type);
  if (query.team) params.set('team', query.team);
  const res = await apiClient.get<{ cards?: PublicStaffCard[] }>(`/api/public/staff?${params.toString()}`);
  return res.success && Array.isArray(res.data?.cards) ? res.data.cards : [];
}

export async function sendStaffMessage(
  staffId: string,
  payload: { name: string; email: string; message: string }
): Promise<{ success: boolean; error?: string }> {
  const res = await apiClient.post<{ id?: string }>(`/api/public/staff/${encodeURIComponent(staffId)}/message`, payload);
  return { success: Boolean(res.success), error: res.error };
}
