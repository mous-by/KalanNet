import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';

import ParentForm from '@/components/ParentForm';

export default function EditParentScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <ParentForm parentId={Number(id)} onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
});
