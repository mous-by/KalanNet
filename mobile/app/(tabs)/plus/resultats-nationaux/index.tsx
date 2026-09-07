import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Eleve } from '@/types/api';

interface ResultatRow {
  decision: string;
  moyenne: number | null;
  observation: string | null;
}

interface ResultatsData {
  classes: Classe[];
  annees: AnneeScolaire[];
  examens_disponibles: string[];
  niveau_examen: string | null;
  eleves: Eleve[];
  resultats: Record<number, ResultatRow>;
}

interface EntryState {
  decision: string | null;
  moyenne: string;
  observation: string;
}

const DECISION_OPTIONS = [
  { value: 'admis', label: 'Admis' },
  { value: 'echec', label: 'Échec' },
];

export default function ResultatsNationauxScreen() {
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [niveauExamen, setNiveauExamen] = useState<string | null>(null);
  const [entries, setEntries] = useState<Record<number, EntryState>>({});
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const endpoint = useMemo(() => {
    const params = new URLSearchParams();
    if (idClasse) params.set('id_classe', String(idClasse));
    if (idAnnee) params.set('id_annee', String(idAnnee));
    if (niveauExamen) params.set('niveau_examen', niveauExamen);
    const query = params.toString();
    return query ? `/resultats-nationaux?${query}` : '/resultats-nationaux';
  }, [idClasse, idAnnee, niveauExamen]);

  const { data, isLoading, error: loadError } = useApiGet<ResultatsData>(endpoint, [endpoint]);

  const classeOptions = (data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (data?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const examenOptions = (data?.examens_disponibles ?? []).map((e) => ({ value: e, label: e }));

  function entryFor(idEleve: number): EntryState {
    if (entries[idEleve]) return entries[idEleve];
    const existing = data?.resultats?.[idEleve];
    return {
      decision: existing?.decision ?? null,
      moyenne: existing?.moyenne != null ? String(existing.moyenne) : '',
      observation: existing?.observation ?? '',
    };
  }

  function updateEntry(idEleve: number, patch: Partial<EntryState>) {
    setEntries((prev) => ({ ...prev, [idEleve]: { ...entryFor(idEleve), ...patch } }));
  }

  async function handleSubmit() {
    if (!idClasse || !idAnnee || !(niveauExamen || data?.niveau_examen)) {
      setError('Choisissez une classe, une année et un type d’examen.');
      return;
    }
    setError(null);
    setSuccess(null);
    setIsSubmitting(true);
    try {
      const resultats: Record<number, ResultatRow> = {};
      (data?.eleves ?? []).forEach((eleve) => {
        const entry = entryFor(eleve.id_eleve);
        if (entry.decision) {
          resultats[eleve.id_eleve] = {
            decision: entry.decision,
            moyenne: entry.moyenne ? Number(entry.moyenne) : null,
            observation: entry.observation || null,
          };
        }
      });

      const { data: response } = await api.post('/resultats-nationaux', {
        id_classe: idClasse,
        id_annee: idAnnee,
        niveau_examen: niveauExamen ?? data?.niveau_examen,
        resultats,
      });
      setSuccess(`${response.saved} résultat(s) enregistré(s).`);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer les résultats.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <SelectField label="Classe" value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label="Année scolaire" value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <SelectField
        label="Type d'examen"
        value={niveauExamen ?? data?.niveau_examen ?? null}
        options={examenOptions}
        onChange={(v) => setNiveauExamen(v as string)}
      />

      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : loadError ? (
        <Text style={styles.error}>{loadError}</Text>
      ) : (data?.eleves ?? []).length === 0 ? (
        <Text style={styles.empty}>Aucun élève pour cette sélection.</Text>
      ) : (
        <>
          {data!.eleves.map((eleve) => {
            const entry = entryFor(eleve.id_eleve);
            return (
              <View key={eleve.id_eleve} style={styles.studentRow}>
                <Text style={styles.studentName}>
                  {eleve.prenom_eleve} {eleve.nom_eleve}
                </Text>
                <SelectField
                  label="Décision"
                  value={entry.decision}
                  options={DECISION_OPTIONS}
                  onChange={(v) => updateEntry(eleve.id_eleve, { decision: v as string })}
                />
                <TextInput
                  mode="outlined"
                  label="Moyenne (optionnel)"
                  keyboardType="numeric"
                  value={entry.moyenne}
                  onChangeText={(v) => updateEntry(eleve.id_eleve, { moyenne: v })}
                  style={styles.input}
                />
                <TextInput
                  mode="outlined"
                  label="Observation (optionnel)"
                  value={entry.observation}
                  onChangeText={(v) => updateEntry(eleve.id_eleve, { observation: v })}
                  style={styles.input}
                />
              </View>
            );
          })}

          {error ? <Text style={styles.error}>{error}</Text> : null}
          {success ? <Text style={styles.success}>{success}</Text> : null}

          <SubmitButton label="Enregistrer les résultats" onPress={handleSubmit} loading={isSubmitting} />
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  error: { color: '#d33', marginBottom: 12 },
  success: { color: '#1f8a4c', marginBottom: 12 },
  studentRow: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 10,
    padding: 12,
    marginBottom: 12,
  },
  studentName: { fontWeight: '600', marginBottom: 8 },
});
