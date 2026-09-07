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

      <Stack.Screen name="emargements/index" options={{ title: 'Émargements' }} />
      <Stack.Screen name="emargements/new" options={{ title: 'Nouvel émargement' }} />

      <Stack.Screen name="presences/index" options={{ title: 'Présences' }} />
      <Stack.Screen name="presences/new" options={{ title: 'Nouvelle présence' }} />

      <Stack.Screen name="bulletins/index" options={{ title: 'Bulletins' }} />
      <Stack.Screen name="bulletins/[idClasse]" options={{ title: 'Élèves' }} />

      <Stack.Screen name="finances/index" options={{ title: 'Paiements' }} />
      <Stack.Screen name="finances/new" options={{ title: 'Nouveau paiement' }} />
      <Stack.Screen name="finances/caisse" options={{ title: 'Caisse' }} />
    </Stack>
  );
}
