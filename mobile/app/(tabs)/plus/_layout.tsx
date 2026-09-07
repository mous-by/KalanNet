import { Stack } from 'expo-router';

export default function PlusLayout() {
  return (
    <Stack>
      <Stack.Screen name="index" options={{ title: 'Plus' }} />

      <Stack.Screen name="enseignants/index" options={{ title: 'Enseignants' }} />
      <Stack.Screen name="enseignants/new" options={{ title: 'Nouvel enseignant' }} />
      <Stack.Screen name="enseignants/[id]/index" options={{ title: 'Enseignant' }} />
      <Stack.Screen name="enseignants/[id]/edit" options={{ title: 'Modifier' }} />

      <Stack.Screen name="timetable/index" options={{ title: 'Emploi du temps' }} />

      <Stack.Screen name="evaluations/index" options={{ title: 'Évaluations' }} />
      <Stack.Screen name="evaluations/new" options={{ title: 'Nouvelle évaluation' }} />
      <Stack.Screen name="evaluations/[id]/index" options={{ title: 'Notes' }} />
    </Stack>
  );
}
