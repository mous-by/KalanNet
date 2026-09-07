import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { hasSeenOnboarding, setOnboardingSeen } from '@/lib/storage';

interface OnboardingContextValue {
  isLoading: boolean;
  hasOnboarded: boolean;
  markOnboarded: () => void;
}

const OnboardingContext = createContext<OnboardingContextValue | undefined>(undefined);

export function OnboardingProvider({ children }: { children: React.ReactNode }) {
  const [isLoading, setIsLoading] = useState(true);
  const [hasOnboarded, setHasOnboarded] = useState(false);

  useEffect(() => {
    hasSeenOnboarding()
      .then(setHasOnboarded)
      .finally(() => setIsLoading(false));
  }, []);

  const markOnboarded = useCallback(() => {
    setHasOnboarded(true);
    setOnboardingSeen().catch(() => {});
  }, []);

  return (
    <OnboardingContext.Provider value={{ isLoading, hasOnboarded, markOnboarded }}>{children}</OnboardingContext.Provider>
  );
}

export function useOnboarding(): OnboardingContextValue {
  const context = useContext(OnboardingContext);
  if (!context) throw new Error('useOnboarding must be used within an OnboardingProvider');
  return context;
}
