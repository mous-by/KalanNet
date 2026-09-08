import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';

const GENRE_OPTIONS = [
  { value: 'Masculin', label: 'Masculin' },
  { value: 'Féminin', label: 'Féminin' },
];

export default function NewUtilisateurScreen() {
  const { user } = useAuth();
  const [nomPrenom, setNomPrenom] = useState('');
  const [email, setEmail] = useState('');
  const [telephone, setTelephone] = useState('');
  const [genre, setGenre] = useState<string | null>(null);
  const [fonction, setFonction] = useState('');
  const [droit, setDroit] = useState<string | null>(user?.droit === 'SupAdmin' ? null : 'Gestionnaire');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  const droitOptions =
    user?.droit === 'SupAdmin'
      ? [
          { value: 'SupAdmin', label: 'SupAdmin' },
          { value: 'Admin', label: 'Admin' },
          { value: 'Gestionnaire', label: 'Gestionnaire' },
        ]
      : [{ value: 'Gestionnaire', label: 'Gestionnaire' }];

  const isValid = nomPrenom.trim() && email.trim() && telephone.trim() && genre && droit;

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post('/configuration/utilisateurs', {
        type_utilisateur: 1,
        nomPrenom: nomPrenom.trim(),
        email: email.trim(),
        telephone: telephone.trim(),
        genre,
        fonction: fonction || null,
        droit,
      });
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de créer cet utilisateur.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.note}>
        Ce formulaire crée un compte classique (Admin/Gestionnaire). Pour lier un compte à un enseignant, un parent, un DAE ou un
        DCAP, utilisez la version web.
      </Text>
      <TextInput mode="outlined" label={requiredLabel('Nom et prénom')} value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Email')} value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Téléphone')} value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      <SelectField label={requiredLabel('Genre')} value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />
      <TextInput mode="outlined" label="Fonction (optionnel)" value={fonction} onChangeText={setFonction} style={styles.input} />
      <SelectField label={requiredLabel('Droit')} value={droit} options={droitOptions} onChange={(v) => setDroit(v as string)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Créer l'utilisateur" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message="Utilisateur créé avec succès." onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  note: { opacity: 0.6, marginBottom: 16, fontSize: 13 },
  error: { color: '#d33', marginBottom: 12 },
});
