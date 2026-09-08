import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, IconButton, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Enseignant, Trimestre } from '@/types/api';

interface PresenceContext {
  enseignants: Enseignant[];
  classes: Classe[];
  trimestres: Trimestre[];
  annees: AnneeScolaire[];
}

interface LeconRow {
  titre: string;
  nombre_heure: string;
  progression: string;
}

export default function NewPresenceScreen() {
  const { user } = useAuth();
  const { isOnline, enqueueAction } = useOffline();
  const { data: context, isLoading: isLoadingContext, error: contextError } = useApiGet<PresenceContext>('/presences', [], {
    cacheKey: 'presences-context',
  });

  const [idEnseignant, setIdEnseignant] = useState<number | null>(null);
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [nombreHeure, setNombreHeure] = useState('1');
  const [date, setDate] = useState<string | null>(null);
  const [lecons, setLecons] = useState<LeconRow[]>([{ titre: '', nombre_heure: '1', progression: '' }]);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const isTeacher = user?.droit === 'enseignant';
  const validLecons = lecons.filter((l) => l.titre.trim());
  const isValid = (isTeacher || idEnseignant) && idClasse && idTrimestre && idAnnee && date && validLecons.length > 0;

  function updateLecon(index: number, patch: Partial<LeconRow>) {
    setLecons((prev) => prev.map((l, i) => (i === index ? { ...l, ...patch } : l)));
  }

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir les champs obligatoires et au moins une leçon.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    const payload = {
      id_enseignant: idEnseignant,
      id_classe: idClasse,
      date_presence: date,
      nombre_heure: Number(nombreHeure),
      id_trimestre: idTrimestre,
      id_anneeScolaire: idAnnee,
      lecons: validLecons.map((l) => ({
        titre: l.titre.trim(),
        nombre_heure: Number(l.nombre_heure) || 0,
        progression: l.progression ? Number(l.progression) : null,
      })),
    };
    try {
      if (!isOnline) {
        const classeLabel = context?.classes.find((c) => c.id_classe === idClasse)?.nom_classe ?? '';
        await enqueueAction({
          kind: 'presence',
          label: `${classeLabel} · ${date}`,
          endpoint: '/presences',
          method: 'post',
          payload,
        });
        setSuccessMessage('Présence enregistrée hors ligne, sera synchronisée.');
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.post('/presences', payload);
      setSuccessMessage('Présence enregistrée avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette présence.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoadingContext) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (!context) return <Text style={styles.error}>{contextError ?? 'Impossible de charger le formulaire.'}</Text>;

  const enseignantOptions = context.enseignants.map((e) => ({ value: e.id_enseignant, label: e.nom_prenom_enseignant }));
  const classeOptions = context.classes.map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const trimestreOptions = context.trimestres.map((t) => ({ value: t.id_trimestre, label: `Trimestre ${t.id_trimestre}` }));
  const anneeOptions = context.annees.map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      {!isTeacher ? (
        <SelectField label={requiredLabel('Enseignant')} value={idEnseignant} options={enseignantOptions} onChange={(v) => setIdEnseignant(v as number)} />
      ) : null}
      <SelectField label={requiredLabel('Classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <TextInput mode="outlined" label="Nombre d'heures total" keyboardType="numeric" value={nombreHeure} onChangeText={setNombreHeure} style={styles.input} />
      <SelectField label={requiredLabel('Trimestre')} value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <SelectField label={requiredLabel('Année scolaire')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <DateField label={requiredLabel('Date')} value={date} onChange={setDate} />

      <Text style={styles.sectionTitle}>
        Leçons couvertes <Text style={styles.required}>*</Text>
      </Text>
      {lecons.map((lecon, index) => (
        <View key={index} style={styles.leconRow}>
          <View style={styles.leconFields}>
            <TextInput mode="outlined" label={requiredLabel('Titre')} value={lecon.titre} onChangeText={(v) => updateLecon(index, { titre: v })} style={styles.input} />
            <TextInput
              mode="outlined"
              label="Heures"
              keyboardType="numeric"
              value={lecon.nombre_heure}
              onChangeText={(v) => updateLecon(index, { nombre_heure: v })}
              style={styles.input}
            />
            <TextInput
              mode="outlined"
              label="Progression % (optionnel)"
              keyboardType="numeric"
              value={lecon.progression}
              onChangeText={(v) => updateLecon(index, { progression: v })}
              style={styles.input}
            />
          </View>
          {lecons.length > 1 ? (
            <IconButton icon="delete-outline" onPress={() => setLecons((prev) => prev.filter((_, i) => i !== index))} />
          ) : null}
        </View>
      ))}
      <IconButton
        icon="plus"
        mode="outlined"
        style={styles.addButton}
        onPress={() => setLecons((prev) => [...prev, { titre: '', nombre_heure: '1', progression: '' }])}
      />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', marginBottom: 12 },
  required: { color: '#d33' },
  sectionTitle: { fontWeight: '600', marginTop: 8, marginBottom: 8 },
  leconRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 10,
    padding: 10,
    marginBottom: 10,
  },
  leconFields: { flex: 1 },
  addButton: { alignSelf: 'flex-start', marginBottom: 12 },
});
