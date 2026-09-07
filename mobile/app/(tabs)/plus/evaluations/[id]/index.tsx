import { useEffect, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text, TextInput } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { Eleve, Matiere } from '@/types/api';

interface EvaluationLine {
  id_ligneEvaluation: number;
  id_eleve: number;
  note: number | null;
  eleve?: Eleve;
}

interface EvaluationDetail {
  evaluation: { libeller: string; date_evaluation: string };
  details: EvaluationLine[];
  matiere?: Matiere;
  classe?: { nom_classe: string };
}

export default function EvaluationDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<EvaluationDetail>(`/evaluations/${id}`, [id]);
  const [notes, setNotes] = useState<Record<number, string>>({});
  const [saveError, setSaveError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (data?.details) {
      const initial: Record<number, string> = {};
      data.details.forEach((line) => {
        initial[line.id_ligneEvaluation] = line.note != null ? String(line.note) : '';
      });
      setNotes(initial);
    }
  }, [data]);

  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire', 'enseignant'].includes(user.droit);
  const canValidate = user?.droit && ['SupAdmin', 'Admin'].includes(user.droit);

  async function handleSaveNotes() {
    if (!data) return;
    setSaveError(null);
    setIsSaving(true);
    try {
      const lineIds = data.details.map((l) => l.id_ligneEvaluation);
      await api.put(`/evaluations/${id}/notes`, {
        id_ligneEvaluation: lineIds,
        note: lineIds.map((lineId) => (notes[lineId] ? Number(notes[lineId]) : null)),
      });
      reload();
    } catch (err) {
      setSaveError(apiErrorMessage(err, 'Impossible d’enregistrer les notes.'));
    } finally {
      setIsSaving(false);
    }
  }

  async function handleValidate() {
    try {
      await api.post(`/evaluations/${id}/validate`);
      reload();
    } catch {
      // no-op: the screen simply won't reflect a validated state if it fails
    }
  }

  async function handleDelete() {
    try {
      await api.delete(`/evaluations/${id}`);
      router.back();
    } catch (err) {
      setSaveError(apiErrorMessage(err, 'Impossible de supprimer cette évaluation.'));
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Évaluation introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.title}>{data.evaluation.libeller}</Text>
      <Text style={styles.meta}>
        {data.classe?.nom_classe ?? '—'} · {data.matiere?.nom_matiere ?? '—'} · {data.evaluation.date_evaluation}
      </Text>

      {canManage ? (
        <View style={styles.actions}>
          {canValidate ? (
            <Button mode="outlined" onPress={handleValidate} style={styles.actionButton}>
              Valider
            </Button>
          ) : null}
          <Button mode="outlined" textColor="#d33" onPress={handleDelete} style={styles.actionButton}>
            Supprimer
          </Button>
        </View>
      ) : null}

      {data.details.map((line) => (
        <View key={line.id_ligneEvaluation} style={styles.studentRow}>
          <Text style={styles.studentName}>
            {line.eleve?.prenom_eleve} {line.eleve?.nom_eleve}
          </Text>
          <TextInput
            mode="outlined"
            dense
            keyboardType="numeric"
            value={notes[line.id_ligneEvaluation] ?? ''}
            onChangeText={(v) => setNotes((prev) => ({ ...prev, [line.id_ligneEvaluation]: v }))}
            editable={canManage}
            style={styles.noteInput}
          />
        </View>
      ))}

      {saveError ? <Text style={styles.error}>{saveError}</Text> : null}

      {canManage ? (
        <Button mode="contained" onPress={handleSaveNotes} loading={isSaving} style={styles.saveButton}>
          Enregistrer les notes
        </Button>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  title: { fontSize: 20, fontWeight: 'bold' },
  meta: { opacity: 0.6, marginTop: 4, marginBottom: 12 },
  actions: { flexDirection: 'row', gap: 10, marginBottom: 16 },
  actionButton: { flex: 1 },
  studentRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(128,128,128,0.3)',
  },
  studentName: { flex: 1 },
  noteInput: { width: 80 },
  saveButton: { marginTop: 16 },
});
