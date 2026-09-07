import { Stack } from 'expo-router';

export default function ElevesLayout() {
  return (
    <Stack>
      <Stack.Screen name="index" options={{ title: 'Élèves' }} />
      <Stack.Screen name="[id]/index" options={{ title: 'Élève' }} />
      <Stack.Screen name="[id]/edit" options={{ title: 'Modifier' }} />
      <Stack.Screen name="[id]/transfer" options={{ title: 'Transférer' }} />
      <Stack.Screen name="[id]/reintegrate" options={{ title: 'Réintégrer' }} />
    </Stack>
  );
}
