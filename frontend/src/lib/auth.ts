export type AuthUser = {
  id: number;
  name: string;
  email: string;
  status: string;
  company?: { id: number; name: string } | null;
  employee_id?: number | null;
  roles: string[];
  permissions: string[];
};

export const AUTH_TOKEN_KEY = "hrms_token";
export const AUTH_USER_KEY = "hrms_user";
export const AUTH_EXPIRED_EVENT = "hrms:auth-expired";

export function getAuthToken() {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(AUTH_TOKEN_KEY);
}

export function getStoredUser(): AuthUser | null {
  if (typeof window === "undefined") return null;

  const rawUser = window.localStorage.getItem(AUTH_USER_KEY);
  if (!rawUser) return null;

  try {
    return JSON.parse(rawUser) as AuthUser;
  } catch {
    clearAuthSession();
    return null;
  }
}

export function storeAuthSession(token: string, user: AuthUser) {
  window.localStorage.setItem(AUTH_TOKEN_KEY, token);
  window.localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
}

export function updateStoredUser(user: AuthUser) {
  window.localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user));
}

export function clearAuthSession() {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(AUTH_TOKEN_KEY);
  window.localStorage.removeItem(AUTH_USER_KEY);
}

export function hasAnyPermission(user: AuthUser | null, permissions?: string[]) {
  if (!permissions?.length) return true;
  if (!user) return false;
  return permissions.some((permission) => user.permissions.includes(permission));
}
