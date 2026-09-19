import { describe, expect, it } from 'vitest';
import { filterPeople, groupPeopleByTeam } from './contactRoutingPeople';
import type { Team, TeamMember } from '../api/teams';

const ada: TeamMember = { id: 'user_1', name: 'Ada', username: 'ada', email: 'ada@example.com', active: true };
const bob: TeamMember = { id: 'user_2', name: 'Bob', username: 'bob', email: 'bob@example.com', active: true };
const cyd: TeamMember = { id: 'user_3', name: 'Cyd', username: 'cyd', email: 'cyd@example.com', active: true };

const teams: Team[] = [
  {
    id: 'team_devs',
    name: 'Web development',
    type: 'external',
    memberUserIds: ['user_1'],
    members: [],
    createdAt: 1,
    updatedAt: 1,
  },
  {
    id: 'team_help',
    name: 'Helpdesk',
    type: 'support',
    memberUserIds: ['user_2'],
    members: [],
    createdAt: 1,
    updatedAt: 1,
  },
];

describe('contactRoutingPeople', () => {
  it('groups members under their team and leftover under other', () => {
    const groups = groupPeopleByTeam([ada, bob, cyd], teams);
    expect(groups).toHaveLength(3);
    expect(groups[0]).toMatchObject({ id: 'team_devs', external: true, users: [ada] });
    expect(groups[1]).toMatchObject({ id: 'team_help', external: false, users: [bob] });
    expect(groups[2]?.id).toBe('other');
    expect(groups[2]?.users).toEqual([cyd]);
  });

  it('filters people by name or email', () => {
    expect(filterPeople([ada, bob], 'ada')).toEqual([ada]);
    expect(filterPeople([ada, bob], 'BOB@')).toEqual([bob]);
  });
});
