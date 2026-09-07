import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import ClasseForm from '@/components/ClasseForm';
import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

export default function EditClasseScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: classe, isLoading, error } = useApiGet<Classe>(`/classes/${id}`, [id]);

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !classe) return <Text style={styles.error}>{error ?? 'Classe introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <ClasseForm classe={classe} onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
  spinner: {
    marginTop: 40,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginTop: 40,
  },
});
