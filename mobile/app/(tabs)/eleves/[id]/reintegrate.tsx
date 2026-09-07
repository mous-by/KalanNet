import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe } from '@/types/api';

export default function ReintegrateEleveScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: options } = useApiGet<{ classes: Classe[]; annees: AnneeScolaire[] }>('/eleves/cartes-scolaires');

  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [motif, setMotif] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post(`/eleves/${id}/reintegrer`, { id_classe: idClasse, id_annee: idAnnee, motif_retour: motif });
      router.back();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de réintégrer cet élève.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const classeOptions = (options?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (options?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <SelectField label="Classe" value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label="Année scolaire" value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <TextInput mode="outlined" label="Motif du retour (optionnel)" value={motif} onChangeText={setMotif} style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Réintégrer" onPress={handleSubmit} loading={isSubmitting} disabled={!idClasse || !idAnnee} />
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
