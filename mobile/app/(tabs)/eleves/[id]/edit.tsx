import { useEffect, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Eleve, Matiere } from '@/types/api';

export default function EditEleveScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { t } = useLocale();
  const { isOnline, enqueueAction } = useOffline();

  const GENRE_OPTIONS = [
    { value: 'Masculin', label: t('eleves.genre_masculin') },
    { value: 'Féminin', label: t('eleves.genre_feminin') },
  ];

  const STATUT_PAIEMENT_OPTIONS = [
    { value: 'normal', label: t('eleves.statut_paiement_normal') },
    { value: 'subventionne', label: t('eleves.statut_paiement_subventionne') },
    { value: 'boursier', label: t('eleves.statut_paiement_boursier') },
    { value: 'gratuit', label: t('eleves.statut_paiement_gratuit') },
  ];

  const CAS_SOCIAL_OPTIONS = [
    { value: 'normal', label: t('eleves.cas_social_normal') },
    { value: 'Dipenser', label: t('eleves.cas_social_dispense') },
    { value: 'Malade', label: t('eleves.cas_social_malade') },
  ];

  const MODE_PAIEMENT_OPTIONS = [
    { value: '', label: t('eleves.mode_paiement_non_defini') },
    { value: 'Mensuel', label: t('eleves.mode_paiement_mensuel') },
    { value: 'Trimestriel', label: t('eleves.mode_paiement_trimestriel') },
    { value: 'Annuel', label: t('eleves.mode_paiement_annuel') },
  ];
  const { data, isLoading: isLoadingEleve, error: eleveError } = useApiGet<{ eleve: Eleve }>(`/eleves/${id}`, [id], {
    cacheKey: `eleve-${id}`,
  });
  const { data: options } = useApiGet<{ classes: Classe[]; annees: AnneeScolaire[]; matieres_lv2: Matiere[] }>('/eleves/inscription-options', [], {
    cacheKey: 'eleves-inscription-options',
  });

  const [form, setForm] = useState<Partial<Eleve>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState(t('eleves.updated_success'));

  useEffect(() => {
    if (data?.eleve) setForm(data.eleve);
  }, [data]);

  function set<K extends keyof Eleve>(key: K, value: Eleve[K]) {
    setForm((prev) => ({ ...prev, [key]: value }));
  }

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    const payload = {
      prenom_eleve: form.prenom_eleve,
      nom_eleve: form.nom_eleve,
      matricule: form.matricule,
      genre_eleve: form.genre_eleve,
      date_naissance: form.date_naissance,
      lieu_naiss: form.lieu_naiss,
      adresse_eleve: form.adresse_eleve,
      cas_social: form.cas_social,
      mode_paiement: form.mode_paiement,
      statut_paiement: form.statut_paiement,
      id_classe: form.id_classe,
      id_annee: form.id_annee,
      id_matiere_lv2: form.id_matiere_lv2 || null,
      date_inscription: form.date_inscription,
    };
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'eleve',
          label: `${form.prenom_eleve ?? ''} ${form.nom_eleve ?? ''}`.trim(),
          endpoint: `/eleves/${id}`,
          method: 'put',
          payload,
        });
        setSuccessMessage(t('eleves.queued_update_success'));
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.put(`/eleves/${id}`, payload);
      setSuccessMessage(t('eleves.updated_success'));
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, t('eleves.update_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoadingEleve) {
    return <ActivityIndicator style={styles.spinner} size="large" />;
  }
  if (!form.id_eleve) {
    return <Text style={styles.error}>{eleveError ?? t('eleves.cannot_load')}</Text>;
  }

  const classeOptions = (options?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (options?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const matiereLv2Options = [
    { value: 0, label: t('eleves.lv2_none') },
    ...(options?.matieres_lv2 ?? []).map((m) => ({ value: m.id_matiere, label: m.nom_matiere })),
  ];

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      <TextInput mode="outlined" label={requiredLabel(t('eleves.label_prenom'))} value={form.prenom_eleve ?? ''} onChangeText={(v) => set('prenom_eleve', v)} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel(t('eleves.label_nom'))} value={form.nom_eleve ?? ''} onChangeText={(v) => set('nom_eleve', v)} style={styles.input} />
      <TextInput mode="outlined" label={t('eleves.label_matricule_short')} value={form.matricule ?? ''} onChangeText={(v) => set('matricule', v)} style={styles.input} />
      <SelectField label={requiredLabel(t('eleves.label_genre'))} value={form.genre_eleve ?? null} options={GENRE_OPTIONS} onChange={(v) => set('genre_eleve', v as string)} />
      <DateField label={t('eleves.label_date_naissance')} value={form.date_naissance ?? null} onChange={(v) => set('date_naissance', v)} />
      <TextInput mode="outlined" label={t('eleves.label_lieu_naissance')} value={form.lieu_naiss ?? ''} onChangeText={(v) => set('lieu_naiss', v)} style={styles.input} />
      <TextInput mode="outlined" label={t('eleves.label_adresse_short')} value={form.adresse_eleve ?? ''} onChangeText={(v) => set('adresse_eleve', v)} style={styles.input} />
      <SelectField label={requiredLabel(t('eleves.label_classe'))} value={form.id_classe ?? null} options={classeOptions} onChange={(v) => set('id_classe', v as number)} />
      <SelectField
        label={t('eleves.lv2_label')}
        value={form.id_matiere_lv2 ?? 0}
        options={matiereLv2Options}
        onChange={(v) => set('id_matiere_lv2', (v as number) || null)}
      />
      <SelectField label={requiredLabel(t('eleves.label_annee'))} value={form.id_annee ?? null} options={anneeOptions} onChange={(v) => set('id_annee', v as number)} />
      <SelectField label={t('eleves.label_cas_social')} value={form.cas_social ?? 'normal'} options={CAS_SOCIAL_OPTIONS} onChange={(v) => set('cas_social', v as string)} />
      <SelectField label={t('eleves.label_mode_paiement')} value={form.mode_paiement ?? ''} options={MODE_PAIEMENT_OPTIONS} onChange={(v) => set('mode_paiement', v as string)} />
      <SelectField
        label={t('eleves.statut_financier_label')}
        value={form.statut_paiement ?? 'normal'}
        options={STATUT_PAIEMENT_OPTIONS}
        onChange={(v) => set('statut_paiement', v as string)}
      />
      <DateField label={t('eleves.label_date_inscription')} value={form.date_inscription ?? null} onChange={(v) => set('date_inscription', v)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={t('classes.save')} onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
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
  spinner: {
    marginTop: 40,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
});
