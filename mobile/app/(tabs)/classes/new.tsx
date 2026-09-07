import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';

import ClasseForm from '@/components/ClasseForm';

export default function NewClasseScreen() {
  return (
    <ScrollView contentContainerStyle={styles.content}>
      <ClasseForm onSaved={() => router.back()} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
});
