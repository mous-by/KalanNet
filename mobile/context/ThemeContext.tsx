import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '@/lib/api';
import { DEFAULT_THEME_KEY, ThemeDefinition, ThemeKey, getTheme } from '@/lib/themes';
import { useAuth } from './AuthContext';

interface ThemeContextValue {
  themeKey: ThemeKey;
  theme: ThemeDefinition;
  setThemeKey: (key: ThemeKey) => void;
}

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const [themeKey, setThemeKeyState] = useState<ThemeKey>(DEFAULT_THEME_KEY);

  useEffect(() => {
    if (user?.theme_preference) {
      setThemeKeyState(getTheme(user.theme_preference).key);
    } else {
      setThemeKeyState(DEFAULT_THEME_KEY);
    }
  }, [user?.theme_preference]);

  const setThemeKey = useCallback((key: ThemeKey) => {
    setThemeKeyState(key);
    api.put('/auth/theme', { theme: key }).catch(() => {
      // Applied locally regardless; the next successful save will catch up.
    });
  }, []);

  const value = useMemo(() => ({ themeKey, theme: getTheme(themeKey), setThemeKey }), [themeKey, setThemeKey]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useAppTheme(): ThemeContextValue {
  const context = useContext(ThemeContext);
  if (!context) throw new Error('useAppTheme must be used within a ThemeProvider');
  return context;
}
