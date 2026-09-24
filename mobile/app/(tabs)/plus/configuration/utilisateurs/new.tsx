import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';

export default function NewUtilisateurScreen() {
  const { t } = useLocale();
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

  const GENRE_OPTIONS = [
    { value: 'Masculin', label: t('configuration.uf_genre_masculin') },
    { value: 'Féminin', label: t('configuration.uf_genre_feminin') },
  ];

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
      setError(t('configuration.uf_champs_obligatoires_mobile'));
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      const { data: created } = await api.post<{ idUtilisateur: number; droit: string }>('/configuration/utilisateurs', {
        type_utilisateur: 1,
        nomPrenom: nomPrenom.trim(),
        email: email.trim(),
        telephone: telephone.trim(),
        genre,
        fonction: fonction || null,
        droit,
      });
      setSuccessVisible(true);
      const canAssign = created.droit !== 'SupAdmin' && (user?.droit === 'SupAdmin' || created.droit !== 'Admin');
      setTimeout(() => {
        if (canAssign) {
          router.replace(`/plus/configuration/utilisateurs/${created.idUtilisateur}/permissions`);
        } else {
          router.back();
        }
      }, 900);
    } catch (err) {
      setError(apiErrorMessage(err, t('configuration.uf_create_error_mobile')));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.note}>{t('configuration.uf_note_mobile')}</Text>
      <TextInput mode="outlined" label={requiredLabel(t('configuration.uf_nom_prenom_label'))} value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel(t('configuration.uf_email_label'))} value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel(t('configuration.ut_th_telephone'))} value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      <SelectField label={requiredLabel(t('configuration.ut_th_genre'))} value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />
      <TextInput mode="outlined" label={t('configuration.uf_fonction_optionnelle')} value={fonction} onChangeText={setFonction} style={styles.input} />
      <SelectField label={requiredLabel(t('configuration.uf_droit_label'))} value={droit} options={droitOptions} onChange={(v) => setDroit(v as string)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={t('configuration.uf_creer_utilisateur')} onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={t('configuration.uf_create_success_mobile')} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  note: { opacity: 0.6, marginBottom: 16, fontSize: 13 },
  error: { color: '#d33', marginBottom: 12 },
});
