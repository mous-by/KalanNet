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
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Eleve } from '@/types/api';

const GENRE_OPTIONS = [
  { value: 'Masculin', label: 'Masculin' },
  { value: 'Féminin', label: 'Féminin' },
];

const STATUT_PAIEMENT_OPTIONS = [
  { value: 'normal', label: 'Normal - parent/tuteur' },
  { value: 'subventionne', label: 'Subventionné par l’État' },
  { value: 'boursier', label: 'Boursier / organisme' },
  { value: 'gratuit', label: 'Gratuité totale' },
];

const CAS_SOCIAL_OPTIONS = [
  { value: 'normal', label: 'Normal' },
  { value: 'Dipenser', label: 'Dispensé' },
  { value: 'Malade', label: 'Malade' },
];

const MODE_PAIEMENT_OPTIONS = [
  { value: '', label: 'Non défini' },
  { value: 'Mensuel', label: 'Mensuel' },
  { value: 'Trimestriel', label: 'Trimestriel' },
  { value: 'Annuel', label: 'Annuel' },
];

export default function EditEleveScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { isOnline, enqueueAction } = useOffline();
  const { data, isLoading: isLoadingEleve, error: eleveError } = useApiGet<{ eleve: Eleve }>(`/eleves/${id}`, [id], {
    cacheKey: `eleve-${id}`,
  });
  const { data: options } = useApiGet<{ classes: Classe[]; annees: AnneeScolaire[] }>('/eleves/cartes-scolaires', [], {
    cacheKey: 'eleves-cartes-scolaires',
  });

  const [form, setForm] = useState<Partial<Eleve>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('Élève modifié avec succès.');

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
        setSuccessMessage('Modification mise en attente, sera synchronisée au retour du réseau.');
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.put(`/eleves/${id}`, payload);
      setSuccessMessage('Élève modifié avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de sauvegarder cet élève.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoadingEleve) {
    return <ActivityIndicator style={styles.spinner} size="large" />;
  }
  if (!form.id_eleve) {
    return <Text style={styles.error}>{eleveError ?? 'Impossible de charger cet élève.'}</Text>;
  }

  const classeOptions = (options?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (options?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      <TextInput mode="outlined" label={requiredLabel('Prénom')} value={form.prenom_eleve ?? ''} onChangeText={(v) => set('prenom_eleve', v)} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Nom')} value={form.nom_eleve ?? ''} onChangeText={(v) => set('nom_eleve', v)} style={styles.input} />
      <TextInput mode="outlined" label="Matricule" value={form.matricule ?? ''} onChangeText={(v) => set('matricule', v)} style={styles.input} />
      <SelectField label={requiredLabel('Genre')} value={form.genre_eleve ?? null} options={GENRE_OPTIONS} onChange={(v) => set('genre_eleve', v as string)} />
      <DateField label="Date de naissance" value={form.date_naissance ?? null} onChange={(v) => set('date_naissance', v)} />
      <TextInput mode="outlined" label="Lieu de naissance" value={form.lieu_naiss ?? ''} onChangeText={(v) => set('lieu_naiss', v)} style={styles.input} />
      <TextInput mode="outlined" label="Adresse" value={form.adresse_eleve ?? ''} onChangeText={(v) => set('adresse_eleve', v)} style={styles.input} />
      <SelectField label={requiredLabel('Classe')} value={form.id_classe ?? null} options={classeOptions} onChange={(v) => set('id_classe', v as number)} />
      <SelectField label={requiredLabel('Année scolaire')} value={form.id_annee ?? null} options={anneeOptions} onChange={(v) => set('id_annee', v as number)} />
      <SelectField label="Cas social" value={form.cas_social ?? 'normal'} options={CAS_SOCIAL_OPTIONS} onChange={(v) => set('cas_social', v as string)} />
      <SelectField label="Mode de paiement" value={form.mode_paiement ?? ''} options={MODE_PAIEMENT_OPTIONS} onChange={(v) => set('mode_paiement', v as string)} />
      <SelectField
        label="Statut financier"
        value={form.statut_paiement ?? 'normal'}
        options={STATUT_PAIEMENT_OPTIONS}
        onChange={(v) => set('statut_paiement', v as string)}
      />
      <DateField label="Date d'inscription" value={form.date_inscription ?? null} onChange={(v) => set('date_inscription', v)} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer" onPress={handleSubmit} loading={isSubmitting} />

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
