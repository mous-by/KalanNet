import { Stack } from 'expo-router';

export default function ClassesLayout() {
  return (
    <Stack>
      <Stack.Screen name="index" options={{ title: 'Classes' }} />
      <Stack.Screen name="new" options={{ title: 'Nouvelle classe' }} />
      <Stack.Screen name="[id]/index" options={{ title: 'Classe' }} />
      <Stack.Screen name="[id]/edit" options={{ title: 'Modifier' }} />
    </Stack>
  );
}
