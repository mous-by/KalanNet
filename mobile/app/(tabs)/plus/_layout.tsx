import { Stack } from 'expo-router';

export default function PlusLayout() {
  return (
    <Stack>
      <Stack.Screen name="index" options={{ title: 'Plus' }} />

      <Stack.Screen name="enseignants/index" options={{ title: 'Enseignants' }} />
      <Stack.Screen name="enseignants/new" options={{ title: 'Nouvel enseignant' }} />
      <Stack.Screen name="enseignants/[id]/index" options={{ title: 'Enseignant' }} />
      <Stack.Screen name="enseignants/[id]/edit" options={{ title: 'Modifier' }} />
    </Stack>
  );
}
