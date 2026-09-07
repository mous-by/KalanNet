import { useEffect, useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
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

export default function NewEvaluationScreen() {
  const { data: context } = useApiGet<EvaluationContext>('/evaluations');

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
      await api.post('/evaluations', {
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
      router.back();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de créer cette évaluation.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!context) return <ActivityIndicator style={styles.spinner} size="large" />;

  const classeOptions = context.classes.map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const matiereOptions = matieres.map((m) => ({ value: m.id_matiere, label: m.nom_matiere }));
  const anneeOptions = context.annees.map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const trimestreOptions = context.trimestres.map((t) => ({ value: t.id_trimestre, label: `Trimestre ${t.id_trimestre}` }));
  const noteOptions = context.notes.map((n) => ({ value: n.id_note, label: `${n.typeNote} (${n.codeNote})` }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <SelectField label="Classe" value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label="Matière" value={idMatiere} options={matiereOptions} onChange={(v) => setIdMatiere(v as number)} disabled={!idClasse} />
      <SelectField label="Année scolaire" value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <SelectField label="Trimestre (ou mois ci-dessous)" value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <TextInput
        mode="outlined"
        label="Mois (1-12, pour le fondamental I)"
        keyboardType="numeric"
        value={mois != null ? String(mois) : ''}
        onChangeText={(v) => setMois(v ? Number(v) : null)}
        style={styles.input}
      />
      <SelectField label="Type de note" value={idNote} options={noteOptions} onChange={(v) => setIdNote(v as number)} />
      <TextInput mode="outlined" label="Libellé" value={libeller} onChangeText={setLibeller} style={styles.input} />
      <DateField label="Date de l'évaluation" value={dateEvaluation} onChange={setDateEvaluation} />
      <TextInput mode="outlined" label="Heure de début (HH:MM)" value={heureDebut} onChangeText={setHeureDebut} placeholder="08:00" style={styles.input} />
      <TextInput mode="outlined" label="Heure de fin (HH:MM)" value={heureFin} onChangeText={setHeureFin} placeholder="09:00" style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Créer l'évaluation" onPress={handleSubmit} loading={isSubmitting} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', marginBottom: 12 },
});
