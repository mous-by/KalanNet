import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Switch, Text, TextInput } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
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
  const { user } = useAuth();
  const { isOnline, enqueueAction } = useOffline();
  const isSupAdmin = user?.droit === 'SupAdmin';
  const [titre, setTitre] = useState('');
  const [contenu, setContenu] = useState('');
  const [publicCible, setPublicCible] = useState('tous');
  const [statut, setStatut] = useState('publie');
  const [global, setGlobal] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('Annonce enregistrée avec succès.');

  const isValid = titre.trim() && contenu.trim();

  async function handleSubmit() {
    if (!isValid) {
      setError('Le titre et le contenu sont obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    const payload = {
      titre: titre.trim(),
      contenu: contenu.trim(),
      public_cible: publicCible,
      statut_annonce: statut,
      global: isSupAdmin && global,
    };
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'annonce',
          label: titre.trim(),
          endpoint: '/annonces',
          method: 'post',
          payload,
        });
        setSuccessMessage('Annonce mise en attente, sera synchronisée au retour du réseau.');
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.post('/annonces', payload);
      setSuccessMessage('Annonce enregistrée avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de créer cette annonce.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      <TextInput mode="outlined" label={requiredLabel('Titre')} value={titre} onChangeText={setTitre} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Contenu')} value={contenu} onChangeText={setContenu} multiline numberOfLines={6} style={styles.input} />
      {isSupAdmin ? (
        <View style={styles.switchRow}>
          <Text style={styles.switchLabel}>Diffuser à toutes les écoles (tous les Admin)</Text>
          <Switch value={global} onValueChange={setGlobal} />
        </View>
      ) : null}
      {!global ? (
        <SelectField label={requiredLabel('Public cible')} value={publicCible} options={PUBLIC_OPTIONS} onChange={(v) => setPublicCible(v as string)} />
      ) : null}
      <SelectField label={requiredLabel('Statut')} value={statut} options={STATUT_OPTIONS} onChange={(v) => setStatut(v as string)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  error: { color: '#d33', marginBottom: 12 },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
    paddingVertical: 4,
  },
  switchLabel: {
    flex: 1,
    fontWeight: '600',
    marginRight: 12,
  },
});
