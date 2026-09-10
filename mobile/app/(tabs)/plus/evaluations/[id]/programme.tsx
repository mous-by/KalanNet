import { useEffect, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Trimestre } from '@/types/api';

interface Note {
  id_note: number;
  typeNote: string;
  codeNote: string;
}

interface EvaluationContext {
  classes: Classe[];
  annees: AnneeScolaire[];
  notes: Note[];
  trimestres: Trimestre[];
}

interface EvaluationLineRaw {
  id_classe: number;
  id_matiere: number;
  id_annee_scolaire: number;
  id_trimestre: number | null;
  mois: number | null;
  id_note: number;
}

interface EvaluationDetail {
  evaluation: { libeller: string; date_evaluation: string; heure_debut: string; heure_fin: string };
  details: EvaluationLineRaw[];
}

// Mirrors evaluations/new.tsx (same fields/pickers) but pre-filled from the
// existing evaluation and submitted via PUT — the mobile equivalent of the
// web "Modifier la fiche" action (evaluations.programme.edit/update), which
// changes the evaluation's date/classe/matière/période rather than its notes.
export default function EditProgrammeScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: context } = useApiGet<EvaluationContext>('/evaluations');
  const { data: current, isLoading: isLoadingCurrent } = useApiGet<EvaluationDetail>(`/evaluations/${id}`, [id]);

  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idMatiere, setIdMatiere] = useState<number | null>(null);
  const [matieres, setMatieres] = useState<{ id_matiere: number; nom_matiere: string }[]>([]);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [mois, setMois] = useState<number | null>(null);
  const [idNote, setIdNote] = useState<number | null>(null);
  const [libeller, setLibeller] = useState('');
  const [dateEvaluation, setDateEvaluation] = useState<string | null>(null);
  const [heureDebut, setHeureDebut] = useState('');
  const [heureFin, setHeureFin] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  useEffect(() => {
    if (!current) return;
    const firstLine = current.details[0];
    setLibeller(current.evaluation.libeller);
    setDateEvaluation(current.evaluation.date_evaluation);
    setHeureDebut((current.evaluation.heure_debut ?? '').slice(0, 5));
    setHeureFin((current.evaluation.heure_fin ?? '').slice(0, 5));
    if (firstLine) {
      setIdClasse(firstLine.id_classe);
      setIdMatiere(firstLine.id_matiere);
      setIdAnnee(firstLine.id_annee_scolaire);
      setIdTrimestre(firstLine.id_trimestre);
      setMois(firstLine.mois);
      setIdNote(firstLine.id_note);
    }
  }, [current]);

  useEffect(() => {
    if (!idClasse) {
      setMatieres([]);
      return;
    }
    api
      .get<{ matiere: { id_matiere: number; nom_matiere: string }[] }>(`/evaluations/classes/${idClasse}/matieres`)
      .then(({ data }) => setMatieres(data.matiere))
      .catch(() => setMatieres([]));
  }, [idClasse]);

  const isValid = idClasse && idMatiere && idAnnee && idNote && libeller.trim() && dateEvaluation && heureDebut && heureFin;

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await api.put(`/evaluations/${id}/programme`, {
        id_classe: idClasse,
        id_matiere: idMatiere,
        id_annee_scolaire: idAnnee,
        id_trimestre: idTrimestre,
        mois,
        id_note: idNote,
        libeller: libeller.trim(),
        date_evaluation: dateEvaluation,
        heure_debut: heureDebut,
        heure_fin: heureFin,
      });
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de modifier cette fiche.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!context || isLoadingCurrent) return <ActivityIndicator style={styles.spinner} size="large" />;

  const classeOptions = context.classes.map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const matiereOptions = matieres.map((m) => ({ value: m.id_matiere, label: m.nom_matiere }));
  const anneeOptions = context.annees.map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const trimestreOptions = context.trimestres.map((t) => ({ value: t.id_trimestre, label: `Trimestre ${t.id_trimestre}` }));
  const noteOptions = context.notes.map((n) => ({ value: n.id_note, label: `${n.typeNote} (${n.codeNote})` }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <SelectField label={requiredLabel('Classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label={requiredLabel('Matière')} value={idMatiere} options={matiereOptions} onChange={(v) => setIdMatiere(v as number)} disabled={!idClasse} />
      <SelectField label={requiredLabel('Année scolaire')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <SelectField label="Trimestre (ou mois ci-dessous)" value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <TextInput
        mode="outlined"
        label="Mois (1-12, pour le fondamental I)"
        keyboardType="numeric"
        value={mois != null ? String(mois) : ''}
        onChangeText={(v) => setMois(v ? Number(v) : null)}
        style={styles.input}
      />
      <SelectField label={requiredLabel('Type de note')} value={idNote} options={noteOptions} onChange={(v) => setIdNote(v as number)} />
      <TextInput mode="outlined" label={requiredLabel('Libellé')} value={libeller} onChangeText={setLibeller} style={styles.input} />
      <DateField label={requiredLabel("Date de l'évaluation")} value={dateEvaluation} onChange={setDateEvaluation} />
      <TextInput mode="outlined" label={requiredLabel('Heure de début (HH:MM)')} value={heureDebut} onChangeText={setHeureDebut} placeholder="08:00" style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Heure de fin (HH:MM)')} value={heureFin} onChangeText={setHeureFin} placeholder="09:00" style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer la fiche" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message="Fiche modifiée avec succès." onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', marginBottom: 12 },
});
