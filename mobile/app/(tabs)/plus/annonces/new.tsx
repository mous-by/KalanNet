import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';

const PUBLIC_OPTIONS = [
  { value: 'tous', label: 'Tous' },
  { value: 'parents', label: 'Parents' },
  { value: 'enseignants', label: 'Enseignants' },
  { value: 'gestionnaires', label: 'Gestionnaires' },
];

const STATUT_OPTIONS = [
  { value: 'publie', label: 'Publier immédiatement' },
  { value: 'brouillon', label: 'Enregistrer en brouillon' },
];

export default function NewAnnonceScreen() {
  const [titre, setTitre] = useState('');
  const [contenu, setContenu] = useState('');
  const [publicCible, setPublicCible] = useState('tous');
  const [statut, setStatut] = useState('publie');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const isValid = titre.trim() && contenu.trim();

  async function handleSubmit() {
    if (!isValid) {
      setError('Le titre et le contenu sont obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post('/annonces', {
        titre: titre.trim(),
        contenu: contenu.trim(),
        public_cible: publicCible,
        statut_annonce: statut,
      });
      router.back();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de créer cette annonce.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <TextInput mode="outlined" label="Titre" value={titre} onChangeText={setTitre} style={styles.input} />
      <TextInput mode="outlined" label="Contenu" value={contenu} onChangeText={setContenu} multiline numberOfLines={6} style={styles.input} />
      <SelectField label="Public cible" value={publicCible} options={PUBLIC_OPTIONS} onChange={(v) => setPublicCible(v as string)} />
      <SelectField label="Statut" value={statut} options={STATUT_OPTIONS} onChange={(v) => setStatut(v as string)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer" onPress={handleSubmit} loading={isSubmitting} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  error: { color: '#d33', marginBottom: 12 },
});
