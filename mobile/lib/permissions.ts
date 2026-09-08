import { User } from '@/types/api';

// Mirrors User::userHasPermission() server-side: SupAdmin bypasses every
// check, everyone else needs the exact canonical permission name — already
// returned pre-canonicalized in `user.permissions` by UserResource, so no
// alias/typo normalization is needed again on this side.
export function hasPermission(user: User | null | undefined, permission: string): boolean {
  if (!user) return false;
  if (user.droit === 'SupAdmin') return true;
  return user.permissions?.includes(permission) ?? false;
}
