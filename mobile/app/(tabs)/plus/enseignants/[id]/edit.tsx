import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import EnseignantForm from '@/components/EnseignantForm';
import { useApiGet } from '@/lib/useApi';
import { Enseignant } from '@/types/api';

export default function EditEnseignantScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, error } = useApiGet<{ enseignant: Enseignant }>(`/enseignants/${id}`, [id]);

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Enseignant introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <EnseignantForm enseignant={data.enseignant} onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 40 },
});
