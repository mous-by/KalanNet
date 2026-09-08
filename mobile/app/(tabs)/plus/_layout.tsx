import { Stack } from 'expo-router';

import { useAppTheme } from '@/context/ThemeContext';

export default function PlusLayout() {
  const { theme } = useAppTheme();

  return (
    <Stack screenOptions={{ headerStyle: { backgroundColor: theme.chrome }, headerTintColor: theme.onChrome }}>
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
      <Stack.Screen name="finances/classe" options={{ title: 'Paiement par classe' }} />
      <Stack.Screen name="finances/caisse" options={{ title: 'Caisse' }} />

      <Stack.Screen name="salaires/index" options={{ title: 'Salaires' }} />

      <Stack.Screen name="annonces/index" options={{ title: 'Annonces' }} />
      <Stack.Screen name="annonces/new" options={{ title: 'Nouvelle annonce' }} />

      <Stack.Screen name="resultats-nationaux/index" options={{ title: 'Résultats nationaux' }} />

      <Stack.Screen name="appels-epreuves/new" options={{ title: "Nouvel appel d'épreuve" }} />

      <Stack.Screen name="abonnement/index" options={{ title: 'Abonnement' }} />

      <Stack.Screen name="configuration/utilisateurs/index" options={{ title: 'Utilisateurs' }} />
      <Stack.Screen name="configuration/utilisateurs/new" options={{ title: 'Nouvel utilisateur' }} />
      <Stack.Screen name="configuration/utilisateurs/[id]/permissions" options={{ title: 'Permissions' }} />
      <Stack.Screen name="configuration/ecoles" options={{ title: 'Écoles' }} />
      <Stack.Screen name="configuration/annees" options={{ title: 'Années scolaires' }} />
      <Stack.Screen name="configuration/permissions" options={{ title: 'Permissions' }} />
      <Stack.Screen name="configuration/academies" options={{ title: 'Académies' }} />
      <Stack.Screen name="configuration/caps" options={{ title: 'CAP' }} />
      <Stack.Screen name="configuration/types-notes" options={{ title: 'Types de notes' }} />
      <Stack.Screen name="configuration/classes-officielles" options={{ title: 'Classes officielles' }} />
      <Stack.Screen name="configuration/status-controles" options={{ title: 'Statuts de contrôle' }} />
    </Stack>
  );
}
