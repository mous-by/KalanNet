import { Stack } from 'expo-router';

import { useAppTheme } from '@/context/ThemeContext';

export default function ClassesLayout() {
  const { theme } = useAppTheme();

  return (
    <Stack screenOptions={{ headerStyle: { backgroundColor: theme.chrome }, headerTintColor: theme.onChrome }}>
      <Stack.Screen name="index" options={{ title: 'Classes' }} />
      <Stack.Screen name="new" options={{ title: 'Nouvelle classe' }} />
      <Stack.Screen name="[id]/index" options={{ title: 'Classe' }} />
      <Stack.Screen name="[id]/edit" options={{ title: 'Modifier' }} />
    </Stack>
  );
}
