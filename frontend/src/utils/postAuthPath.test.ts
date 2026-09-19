import { describe, expect, it } from 'vitest';
import { isStaffUser, postAuthPath } from './postAuthPath';

describe('postAuthPath', () => {
  it('sends staff to the dashboard', () => {
    expect(postAuthPath({ roles: ['EDITOR'] })).toBe('/dashboard');
    expect(isStaffUser({ roles: ['ADMIN'] })).toBe(true);
  });

  it('sends external members to team chat', () => {
    expect(postAuthPath({ roles: ['USER'], hasTeamChat: true })).toBe('/team-chat');
  });

  it('sends other users home', () => {
    expect(postAuthPath({ roles: ['USER'] })).toBe('/');
    expect(postAuthPath(null)).toBe('/');
  });
});
