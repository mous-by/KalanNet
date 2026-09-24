import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';

export default function TransferEleveScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { t } = useLocale();
  const [motif, setMotif] = useState('');
  const [destination, setDestination] = useState('');
  const [travail, setTravail] = useState('');
  const [conduite, setConduite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post(`/eleves/${id}/transfert`, { motif, destination, travail, conduite });
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, t('eleves.transfer_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  const isValid = motif.trim() && destination.trim() && conduite.trim();

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <TextInput mode="outlined" label={requiredLabel(t('eleves.motif_label'))} value={motif} onChangeText={setMotif} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel(t('eleves.destination_label'))} value={destination} onChangeText={setDestination} style={styles.input} />
      <TextInput mode="outlined" label={t('eleves.travail_label')} value={travail} onChangeText={setTravail} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel(t('eleves.conduite_label'))} value={conduite} onChangeText={setConduite} style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={t('eleves.confirm_transfer_button')} onPress={handleSubmit} loading={isSubmitting} disabled={!isValid} />

      <SuccessSnackbar visible={successVisible} message={t('eleves.transfer_success')} onDismiss={() => setSuccessVisible(false)} />
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
