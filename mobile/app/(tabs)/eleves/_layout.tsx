import { Stack } from 'expo-router';

import { useLocale } from '@/context/LocaleContext';
import { useAppTheme } from '@/context/ThemeContext';

export default function ElevesLayout() {
  const { theme } = useAppTheme();
  const { t } = useLocale();

  return (
    <Stack screenOptions={{ headerStyle: { backgroundColor: theme.chrome }, headerTintColor: theme.onChrome }}>
      <Stack.Screen name="index" options={{ title: t('eleves.title') }} />
      <Stack.Screen name="new" options={{ title: t('eleves.new_title') }} />
      <Stack.Screen name="[id]/index" options={{ title: t('eleves.detail_title') }} />
      <Stack.Screen name="[id]/edit" options={{ title: t('eleves.edit_title') }} />
      <Stack.Screen name="[id]/transfer" options={{ title: t('eleves.transfer_title') }} />
      <Stack.Screen name="[id]/reintegrate" options={{ title: t('eleves.reintegrate_title') }} />
    </Stack>
  );
}
