import { Stack } from 'expo-router';

import { useAppTheme } from '@/context/ThemeContext';

export default function ElevesLayout() {
  const { theme } = useAppTheme();

  return (
    <Stack screenOptions={{ headerStyle: { backgroundColor: theme.chrome }, headerTintColor: theme.onChrome }}>
      <Stack.Screen name="index" options={{ title: 'Élèves' }} />
      <Stack.Screen name="new" options={{ title: 'Inscrire un élève' }} />
      <Stack.Screen name="[id]/index" options={{ title: 'Élève' }} />
      <Stack.Screen name="[id]/edit" options={{ title: 'Modifier' }} />
      <Stack.Screen name="[id]/transfer" options={{ title: 'Transférer' }} />
      <Stack.Screen name="[id]/reintegrate" options={{ title: 'Réintégrer' }} />
    </Stack>
  );
}
