import type { Team, TeamMember } from '../api/teams';

export interface RoutingPeopleGroup {
  id: string;
  label: string;
  external: boolean;
  users: TeamMember[];
}

export function groupPeopleByTeam(users: TeamMember[], teams: Team[]): RoutingPeopleGroup[] {
  const claimed = new Set<string>();
  const groups: RoutingPeopleGroup[] = [];

  for (const team of teams) {
    const members = users.filter((user) => team.memberUserIds.includes(user.id));
    if (members.length === 0) {
      continue;
    }
    members.forEach((user) => claimed.add(user.id));
    groups.push({
      id: team.id,
      label: team.name,
      external: team.type === 'external',
      users: members,
    });
  }

  const leftover = users.filter((user) => !claimed.has(user.id));
  if (leftover.length > 0) {
    groups.push({
      id: 'other',
      label: '',
      external: false,
      users: leftover,
    });
  }

  return groups;
}

export function filterPeople(users: TeamMember[], query: string): TeamMember[] {
  const needle = query.trim().toLocaleLowerCase();
  if (needle === '') {
    return users;
  }

  return users.filter((user) => {
    const hay = `${user.name} ${user.email} ${user.username}`.toLocaleLowerCase();
    return hay.includes(needle);
  });
}
