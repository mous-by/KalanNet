import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from '@/lib/api';
import { DEFAULT_LOCALE, Locale, TranslationKey, translate } from '@/lib/i18n';
import { useAuth } from './AuthContext';

interface LocaleContextValue {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  t: (key: TranslationKey) => string;
}

const LocaleContext = createContext<LocaleContextValue | undefined>(undefined);

export function LocaleProvider({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const [locale, setLocaleState] = useState<Locale>(DEFAULT_LOCALE);

  useEffect(() => {
    const preference = user?.locale_preference;
    if (preference === 'fr' || preference === 'en' || preference === 'ar') {
      setLocaleState(preference);
    }
  }, [user?.locale_preference]);

  const setLocale = useCallback((next: Locale) => {
    setLocaleState(next);
    api.put('/auth/locale', { locale: next }).catch(() => {
      // Applied locally regardless; retried implicitly next time it's changed.
    });
  }, []);

  const t = useCallback((key: TranslationKey) => translate(locale, key), [locale]);

  const value = useMemo(() => ({ locale, setLocale, t }), [locale, setLocale, t]);

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

export function useLocale(): LocaleContextValue {
  const context = useContext(LocaleContext);
  if (!context) throw new Error('useLocale must be used within a LocaleProvider');
  return context;
}
