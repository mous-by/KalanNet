import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
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

const MODE_REGLEMENT_OPTIONS = [
  { value: 'especes', label: 'Espèces' },
  { value: 'cheque', label: 'Chèque' },
  { value: 'virement', label: 'Virement' },
  { value: 'mobile_money', label: 'Mobile money manuel' },
];

export default function NewPaiementScreen() {
  const { id_eleve } = useLocalSearchParams<{ id_eleve: string }>();
  const { data: context, isLoading } = useApiGet<StudentContext>(id_eleve ? `/finances/eleves/${id_eleve}/contexte` : null, [id_eleve]);

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

  const isValid = echeanceId && montant && modeReglement && date;

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post('/finances/paiements', {
        echeance_id: echeanceId,
        date_paiement: date,
        motif: motif || null,
        montant_paye: Number(montant),
        mode_reglement: modeReglement,
        parent_id: parentId,
        nom_payeur: nomPayeur || null,
        telephone: telephone || null,
      });
      router.back();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer ce paiement.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!id_eleve) return <Text style={styles.error}>Élève non spécifié.</Text>;
  if (isLoading || !context) return <ActivityIndicator style={styles.spinner} size="large" />;

  const echeanceOptions = (context.plan?.echeances ?? [])
    .filter((e) => e.reste > 0)
    .map((e) => ({ value: e.id, label: `${e.libelle} — reste ${e.reste.toLocaleString('fr-FR')} FCFA` }));
  const parentOptions = [
    { value: 0, label: 'Autre personne' },
    ...(context.parents ?? []).map((p) => ({ value: p.id_parent, label: p.nom_prenom_parent })),
  ];

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.studentName}>{context.eleve.nom}</Text>

      <SelectField label="Échéance" value={echeanceId} options={echeanceOptions} onChange={(v) => setEcheanceId(v as number)} />
      <TextInput mode="outlined" label="Montant payé" keyboardType="numeric" value={montant} onChangeText={setMontant} style={styles.input} />
      <SelectField label="Mode de règlement" value={modeReglement} options={MODE_REGLEMENT_OPTIONS} onChange={(v) => setModeReglement(v as string)} />
      <DateField label="Date de paiement" value={date} onChange={setDate} />
      <TextInput mode="outlined" label="Motif (optionnel)" value={motif} onChangeText={setMotif} style={styles.input} />
      <SelectField label="Parent payeur" value={parentId ?? 0} options={parentOptions} onChange={(v) => setParentId((v as number) || null)} />
      <TextInput mode="outlined" label="Nom du payeur (optionnel)" value={nomPayeur} onChangeText={setNomPayeur} style={styles.input} />
      <TextInput mode="outlined" label="Téléphone (optionnel)" value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer le paiement" onPress={handleSubmit} loading={isSubmitting} />
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
