import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';

import EnseignantForm from '@/components/EnseignantForm';

export default function NewEnseignantScreen() {
  return (
    <ScrollView contentContainerStyle={styles.content}>
      <EnseignantForm onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
});
