import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';

import ParentForm from '@/components/ParentForm';

export default function NewParentScreen() {
  return (
    <ScrollView contentContainerStyle={styles.content}>
      <ParentForm onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
});
