import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';

export default function TransferEleveScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [motif, setMotif] = useState('');
  const [destination, setDestination] = useState('');
  const [travail, setTravail] = useState('');
  const [conduite, setConduite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post(`/eleves/${id}/transfert`, { motif, destination, travail, conduite });
      router.back();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de transférer cet élève.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const isValid = motif.trim() && destination.trim() && conduite.trim();

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <TextInput mode="outlined" label="Motif" value={motif} onChangeText={setMotif} style={styles.input} />
      <TextInput mode="outlined" label="Établissement de destination" value={destination} onChangeText={setDestination} style={styles.input} />
      <TextInput mode="outlined" label="Travail effectué (optionnel)" value={travail} onChangeText={setTravail} style={styles.input} />
      <TextInput mode="outlined" label="Conduite" value={conduite} onChangeText={setConduite} style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Confirmer le transfert" onPress={handleSubmit} loading={isSubmitting} disabled={!isValid} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
  input: {
    marginBottom: 12,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
});
