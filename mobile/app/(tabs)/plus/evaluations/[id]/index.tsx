import { useEffect, useMemo, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text, TextInput } from 'react-native-paper';

import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { downloadAndShare } from '@/lib/downloadFile';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';
import { Eleve, Matiere, Trimestre } from '@/types/api';

const MOIS_OPTIONS: Record<number, string> = {
  1: 'Janvier',
  2: 'Février',
  3: 'Mars',
  4: 'Avril',
  5: 'Mai',
  6: 'Juin',
  9: 'Septembre',
  10: 'Octobre',
  11: 'Novembre',
  12: 'Décembre',
};

interface EvaluationLine {
  id_ligneEvaluation: number;
  id_eleve: number;
  note: number | null;
  mois: number | null;
  eleve?: Eleve;
  note_type?: { typeNote: string; valeur: number } | null;
  trimestre?: Trimestre | null;
}

interface EvaluationDetail {
  evaluation: { libeller: string; date_evaluation: string; heure_debut: string; heure_fin: string };
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
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [isDownloading, setIsDownloading] = useState(false);

  useEffect(() => {
    if (data?.details) {
      const initial: Record<number, string> = {};
      data.details.forEach((line) => {
        initial[line.id_ligneEvaluation] = line.note != null ? String(line.note) : '';
      });
      setNotes(initial);
    }
  }, [data]);

  const canManage = hasPermission(user, 'evaluation_modification');
  const canDelete = hasPermission(user, 'evaluation_supprimer');
  const canValidate = hasPermission(user, 'evaluation_validation_notes');

  const firstLine = data?.details[0];
  const maxNote = firstLine?.note_type?.valeur && firstLine.note_type.valeur > 0 ? firstLine.note_type.valeur : 20;
  const periode = firstLine?.mois ? MOIS_OPTIONS[firstLine.mois] ?? String(firstLine.mois) : firstLine?.trimestre?.nom_trimestre ?? '—';

  const invalidLines = useMemo(() => {
    const invalid = new Set<number>();
    Object.entries(notes).forEach(([lineId, raw]) => {
      if (raw.trim() === '') return;
      const value = Number(raw.replace(',', '.'));
      if (Number.isNaN(value) || value < 0 || value > maxNote) invalid.add(Number(lineId));
    });
    return invalid;
  }, [notes, maxNote]);

  async function handleSaveNotes() {
    if (!data) return;
    if (invalidLines.size > 0) {
      setSaveError(`Certaines notes sont invalides (doivent être entre 0 et ${maxNote}).`);
      return;
    }
    setSaveError(null);
    setIsSaving(true);
    try {
      const lineIds = data.details.map((l) => l.id_ligneEvaluation);
      await api.put(`/evaluations/${id}/notes`, {
        id_ligneEvaluation: lineIds,
        note: lineIds.map((lineId) => (notes[lineId] ? Number(notes[lineId].replace(',', '.')) : null)),
      });
      setSuccessMessage('Notes enregistrées avec succès.');
      setSuccessVisible(true);
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
      setSuccessMessage('Évaluation validée avec succès.');
      setSuccessVisible(true);
      reload();
    } catch {
      // no-op: the screen simply won't reflect a validated state if it fails
    }
  }

  async function handleDelete() {
    try {
      await api.delete(`/evaluations/${id}`);
      setSuccessMessage('Évaluation supprimée avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setSaveError(apiErrorMessage(err, 'Impossible de supprimer cette évaluation.'));
    }
  }

  async function handleDownloadPdf() {
    if (!data) return;
    setIsDownloading(true);
    try {
      await downloadAndShare(`/evaluations/${id}/pdf`, `Notes_${data.evaluation.libeller}.pdf`);
    } catch (err) {
      setSaveError(apiErrorMessage(err, 'Impossible de télécharger le PDF.'));
    } finally {
      setIsDownloading(false);
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Évaluation introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.title}>{data.evaluation.libeller}</Text>

      <Text style={styles.sectionTitle}>Fiche de l'évaluation</Text>
      <View style={styles.infoCard}>
        <InfoRow label="Date" value={data.evaluation.date_evaluation} />
        <InfoRow label="Classe" value={data.classe?.nom_classe ?? '—'} />
        <InfoRow label="Matière" value={data.matiere?.nom_matiere ?? '—'} />
        <InfoRow label="Heure" value={`${(data.evaluation.heure_debut ?? '—').slice(0, 5)} - ${(data.evaluation.heure_fin ?? '—').slice(0, 5)}`} />
        <InfoRow label="Type note" value={firstLine?.note_type?.typeNote ?? '—'} />
        <InfoRow label="Période" value={periode} last />
      </View>

      <View style={styles.actions}>
        <Button mode="outlined" icon="printer" loading={isDownloading} onPress={handleDownloadPdf} style={styles.actionButton}>
          Fiche PDF
        </Button>
        {canValidate ? (
          <Button mode="outlined" onPress={handleValidate} style={styles.actionButton}>
            Valider
          </Button>
        ) : null}
        {canDelete ? (
          <Button mode="outlined" textColor="#d33" onPress={handleDelete} style={styles.actionButton}>
            Supprimer
          </Button>
        ) : null}
      </View>

      <Text style={styles.sectionTitle}>Saisie des notes (/ {maxNote})</Text>
      {data.details.map((line) => {
        const isInvalid = invalidLines.has(line.id_ligneEvaluation);
        return (
          <View key={line.id_ligneEvaluation} style={styles.studentRow}>
            <View style={styles.studentInfo}>
              <Text style={styles.studentName}>
                {line.eleve?.prenom_eleve} {line.eleve?.nom_eleve}
              </Text>
              <Text style={styles.studentMatricule}>{line.eleve?.matricule ?? '—'}</Text>
            </View>
            <View style={styles.noteInputWrap}>
              <TextInput
                mode="outlined"
                dense
                keyboardType="numeric"
                value={notes[line.id_ligneEvaluation] ?? ''}
                onChangeText={(v) => setNotes((prev) => ({ ...prev, [line.id_ligneEvaluation]: v }))}
                editable={canManage}
                error={isInvalid}
                style={styles.noteInput}
              />
              {isInvalid ? <Text style={styles.noteError}>0 à {maxNote}</Text> : null}
            </View>
          </View>
        );
      })}

      {saveError ? <Text style={styles.error}>{saveError}</Text> : null}

      {canManage ? (
        <Button mode="contained" onPress={handleSaveNotes} loading={isSaving} disabled={invalidLines.size > 0} style={styles.saveButton}>
          Enregistrer les notes
        </Button>
      ) : null}

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

function InfoRow({ label, value, last }: { label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.infoRow, last ? styles.infoRowLast : null]}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  title: { fontSize: 20, fontWeight: 'bold' },
  sectionTitle: { fontSize: 15, fontWeight: '700', marginTop: 20, marginBottom: 10 },
  infoCard: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    paddingHorizontal: 14,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(128,128,128,0.25)',
  },
  infoRowLast: { borderBottomWidth: 0 },
  infoLabel: { opacity: 0.6 },
  infoValue: { fontWeight: '600' },
  actions: { flexDirection: 'row', gap: 10, marginTop: 20 },
  actionButton: { flex: 1 },
  studentRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(128,128,128,0.3)',
    gap: 10,
  },
  studentInfo: { flex: 1 },
  studentName: { fontWeight: '600' },
  studentMatricule: { opacity: 0.6, fontSize: 12, marginTop: 2 },
  noteInputWrap: { alignItems: 'flex-end' },
  noteInput: { width: 90 },
  noteError: { color: '#d33', fontSize: 11, marginTop: 2 },
  saveButton: { marginTop: 16 },
});
