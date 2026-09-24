import { Stack } from 'expo-router';

import { useLocale } from '@/context/LocaleContext';
import { useAppTheme } from '@/context/ThemeContext';

export default function ClassesLayout() {
  const { theme } = useAppTheme();
  const { t } = useLocale();

  return (
    <Stack screenOptions={{ headerStyle: { backgroundColor: theme.chrome }, headerTintColor: theme.onChrome }}>
      <Stack.Screen name="index" options={{ title: t('classes.title') }} />
      <Stack.Screen name="new" options={{ title: t('classes.new_title') }} />
      <Stack.Screen name="[id]/index" options={{ title: t('classes.detail_title') }} />
      <Stack.Screen name="[id]/edit" options={{ title: t('classes.edit_title') }} />
    </Stack>
  );
}
