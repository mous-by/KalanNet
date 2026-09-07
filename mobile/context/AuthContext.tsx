import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api, registerUnauthorizedHandler } from '../lib/api';
import { clearToken, getToken, setToken } from '../lib/storage';
import { AccountChoice, LoginResponse, User, isLoginSuccess } from '../types/api';

interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  subscriptionBlocked: boolean;
  pendingAccounts: AccountChoice[] | null;
  login: (identifier: string, pwd: string) => Promise<void>;
  selectSchool: (idUtilisateur: number, idEcole: number) => Promise<void>;
  logout: () => Promise<void>;
  cancelSchoolSelection: () => void;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

async function applyLoginResult(
  response: Awaited<ReturnType<typeof api.post<LoginResponse>>>['data'],
  setUser: (user: User) => void,
  setSubscriptionBlocked: (blocked: boolean) => void
) {
  if (isLoginSuccess(response)) {
    await setToken(response.token);
    setUser(response.user);
    setSubscriptionBlocked(response.subscription_blocked);
    return null;
  }
  return response.accounts;
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [subscriptionBlocked, setSubscriptionBlocked] = useState(false);
  const [pendingAccounts, setPendingAccounts] = useState<AccountChoice[] | null>(null);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch {
      // best-effort: even if the network call fails, clear the local session
    }
    await clearToken();
    setUser(null);
    setSubscriptionBlocked(false);
  }, []);

  useEffect(() => {
    registerUnauthorizedHandler(() => {
      setUser(null);
      setSubscriptionBlocked(false);
    });
  }, []);

  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (!token) {
        setIsLoading(false);
        return;
      }
      try {
        const { data } = await api.get<{ user: User; subscription_blocked: boolean }>('/auth/me');
        setUser(data.user);
        setSubscriptionBlocked(data.subscription_blocked);
      } catch {
        await clearToken();
      } finally {
        setIsLoading(false);
      }
    })();
  }, []);

  const login = useCallback(async (identifier: string, pwd: string) => {
    const { data } = await api.post<LoginResponse>('/auth/login', {
      identifier,
      pwd,
      device_name: 'mobile-app',
    });
    const accounts = await applyLoginResult(data, setUser, setSubscriptionBlocked);
    setPendingAccounts(accounts);
  }, []);

  const selectSchool = useCallback(async (idUtilisateur: number, idEcole: number) => {
    const { data } = await api.post<LoginResponse>('/auth/select-school', {
      id_utilisateur: idUtilisateur,
      id_ecole: idEcole,
      device_name: 'mobile-app',
    });
    await applyLoginResult(data, setUser, setSubscriptionBlocked);
    setPendingAccounts(null);
  }, []);

  const cancelSchoolSelection = useCallback(() => setPendingAccounts(null), []);

  const value = useMemo(
    () => ({ user, isLoading, subscriptionBlocked, pendingAccounts, login, selectSchool, logout, cancelSchoolSelection }),
    [user, isLoading, subscriptionBlocked, pendingAccounts, login, selectSchool, logout, cancelSchoolSelection]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
}
