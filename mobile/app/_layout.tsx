import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useFonts } from 'expo-font';
import { DefaultTheme, Stack, ThemeProvider as NavigationThemeProvider } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { PaperProvider } from 'react-native-paper';
import 'react-native-reanimated';

import { AuthProvider, useAuth } from '@/context/AuthContext';
import { LocaleProvider, useLocale } from '@/context/LocaleContext';
import { OfflineProvider } from '@/context/OfflineContext';
import { OnboardingProvider, useOnboarding } from '@/context/OnboardingContext';
import { ThemeProvider as AppThemeProvider, useAppTheme } from '@/context/ThemeContext';
import { buildPaperTheme } from '@/lib/paperTheme';
import { SURFACE } from '@/lib/themes';

export {
  // Catch any errors thrown by the Layout component.
  ErrorBoundary,
} from 'expo-router';

// Prevent the splash screen from auto-hiding before asset + auth bootstrap
// (token restore + /auth/me check) is complete.
SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  const [loaded, error] = useFonts({
    SpaceMono: require('../assets/fonts/SpaceMono-Regular.ttf'),
  });

  useEffect(() => {
    if (error) throw error;
  }, [error]);

  if (!loaded) {
    return null;
  }

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <AuthProvider>
        <LocaleProvider>
          <OnboardingProvider>
            <AppThemeProvider>
              <OfflineProvider>
                <SplashScreenController />
                <RootLayoutNav />
              </OfflineProvider>
            </AppThemeProvider>
          </OnboardingProvider>
        </LocaleProvider>
      </AuthProvider>
    </GestureHandlerRootView>
  );
}

function SplashScreenController() {
  const { isLoading: authLoading } = useAuth();
  const { isLoading: onboardingLoading } = useOnboarding();

  useEffect(() => {
    if (!authLoading && !onboardingLoading) {
      SplashScreen.hideAsync();
    }
  }, [authLoading, onboardingLoading]);

  return null;
}

function RootLayoutNav() {
  const { user, isLoading: authLoading } = useAuth();
  const { hasOnboarded, isLoading: onboardingLoading } = useOnboarding();
  const { theme } = useAppTheme();
  const { t } = useLocale();

  if (authLoading || onboardingLoading) {
    return null;
  }

  const paperTheme = buildPaperTheme(theme);
  const navTheme = {
    ...DefaultTheme,
    colors: {
      ...DefaultTheme.colors,
      primary: theme.accent,
      background: SURFACE.background,
      card: theme.chrome,
      text: SURFACE.text,
      border: SURFACE.border,
    },
  };

  return (
    <PaperProvider theme={paperTheme} settings={{ icon: (props) => <MaterialCommunityIcons {...props} /> }}>
      <NavigationThemeProvider value={navTheme}>
        <Stack
          screenOptions={{
            headerStyle: { backgroundColor: theme.chrome },
            headerTintColor: theme.onChrome,
          }}>
          <Stack.Protected guard={!!user}>
            <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
            <Stack.Screen name="notifications" options={{ title: t('notifications.title') }} />
          </Stack.Protected>

          <Stack.Protected guard={!user && !hasOnboarded}>
            <Stack.Screen name="onboarding" options={{ headerShown: false }} />
          </Stack.Protected>

          <Stack.Protected guard={!user && hasOnboarded}>
            <Stack.Screen name="login" options={{ headerShown: false }} />
          </Stack.Protected>
        </Stack>
      </NavigationThemeProvider>
    </PaperProvider>
  );
}
