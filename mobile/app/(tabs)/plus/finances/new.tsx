import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { formatMontant } from '@/lib/currency';
import { useApiGet } from '@/lib/useApi';

interface Echeance {
  id: number;
  libelle: string;
  montant_prevu: number;
  reste: number;
  date_limite: string;
}

interface ParentPayeur {
  id_parent: number;
  nom_prenom_parent: string;
}

interface StudentContext {
  eleve: { id: number; nom: string };
  plan: { echeances: Echeance[] } | null;
  parents: ParentPayeur[];
}

export default function NewPaiementScreen() {
  const { id_eleve } = useLocalSearchParams<{ id_eleve: string }>();
  const { user } = useAuth();
  const { t } = useLocale();
  const { isOnline, enqueueAction } = useOffline();

  const MODE_REGLEMENT_OPTIONS = [
    { value: 'especes', label: t('finances.mode_especes') },
    { value: 'cheque', label: t('finances.mode_cheque') },
    { value: 'virement', label: t('finances.mode_virement') },
    { value: 'mobile_money', label: t('finances.mode_mobile_money') },
  ];
  const { data: context, isLoading, error: contextError } = useApiGet<StudentContext>(id_eleve ? `/finances/eleves/${id_eleve}/contexte` : null, [id_eleve], {
    cacheKey: id_eleve ? `finances-eleve-${id_eleve}` : undefined,
  });

  const [echeanceId, setEcheanceId] = useState<number | null>(null);
  const [montant, setMontant] = useState('');
  const [modeReglement, setModeReglement] = useState<string | null>(null);
  const [date, setDate] = useState<string | null>(null);
  const [motif, setMotif] = useState('');
  const [parentId, setParentId] = useState<number | null>(null);
  const [nomPayeur, setNomPayeur] = useState('');
  const [telephone, setTelephone] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState(t('finances.paiement_enregistre'));

  const isValid = echeanceId && montant && modeReglement && date;

  async function handleSubmit() {
    if (!isValid) {
      setError(t('finances.validation_required'));
      return;
    }
    setError(null);
    setIsSubmitting(true);
    const payload = {
      echeance_id: echeanceId,
      date_paiement: date,
      motif: motif || null,
      montant_paye: Number(montant),
      mode_reglement: modeReglement,
      parent_id: parentId,
      nom_payeur: nomPayeur || null,
      telephone: telephone || null,
    };
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'paiement_eleve',
          label: `${context?.eleve.nom ?? ''} · ${formatMontant(Number(montant), user)}`,
          endpoint: '/finances/paiements',
          method: 'post',
          payload,
        });
        setSuccessMessage(t('finances.paiement_queued'));
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.post('/finances/paiements', payload);
      setSuccessMessage(t('finances.paiement_enregistre'));
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, t('finances.save_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!id_eleve) return <Text style={styles.error}>{t('finances.eleve_non_specifie')}</Text>;
  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (!context) return <Text style={styles.error}>{contextError ?? t('finances.load_error')}</Text>;

  const echeanceOptions = (context.plan?.echeances ?? [])
    .filter((e) => e.reste > 0)
    .map((e) => ({ value: e.id, label: `${e.libelle} — ${t('finances.reste_prefix').replace(':montant', formatMontant(e.reste, user))}` }));
  const parentOptions = [
    { value: 0, label: t('finances.autre_personne') },
    ...(context.parents ?? []).map((p) => ({ value: p.id_parent, label: p.nom_prenom_parent })),
  ];

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      <Text style={styles.studentName}>{context.eleve.nom}</Text>

      <SelectField label={requiredLabel(t('finances.label_echeance'))} value={echeanceId} options={echeanceOptions} onChange={(v) => setEcheanceId(v as number)} />
      <TextInput mode="outlined" label={requiredLabel(t('finances.label_montant_paye'))} keyboardType="numeric" value={montant} onChangeText={setMontant} style={styles.input} />
      <SelectField label={requiredLabel(t('finances.label_mode_reglement'))} value={modeReglement} options={MODE_REGLEMENT_OPTIONS} onChange={(v) => setModeReglement(v as string)} />
      <DateField label={requiredLabel(t('finances.label_date_paiement'))} value={date} onChange={setDate} />
      <TextInput mode="outlined" label={t('finances.label_motif_optional')} value={motif} onChangeText={setMotif} style={styles.input} />
      <SelectField label={t('finances.label_parent_payeur')} value={parentId ?? 0} options={parentOptions} onChange={(v) => setParentId((v as number) || null)} />
      <TextInput mode="outlined" label={t('finances.label_nom_payeur_optional')} value={nomPayeur} onChangeText={setNomPayeur} style={styles.input} />
      <TextInput mode="outlined" label={t('finances.label_telephone_optional')} value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={t('finances.enregistrer_paiement')} onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', marginBottom: 12, textAlign: 'center', marginTop: 12 },
  studentName: { fontSize: 18, fontWeight: '600', marginBottom: 16 },
});
