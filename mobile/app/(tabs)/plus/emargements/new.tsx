import { useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet } from 'react-native';
import { ActivityIndicator, Text, TextInput } from 'react-native-paper';

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
import { AnneeScolaire, Classe, Enseignant, Matiere, Trimestre } from '@/types/api';

interface Lecon {
  id_lecon: number;
  numero: number;
  titre: string;
}

interface EmargementContext {
  enseignants: Enseignant[];
  classes: Classe[];
  matieres: Matiere[];
  trimestres: Trimestre[];
  annees: AnneeScolaire[];
  lecons: Lecon[];
}

export default function NewEmargementScreen() {
  const { user } = useAuth();
  const { isOnline, enqueueAction } = useOffline();
  const { data: context, isLoading: isLoadingContext, error: contextError } = useApiGet<EmargementContext>('/emargements', [], {
    cacheKey: 'emargements-context',
  });

  const [idEnseignant, setIdEnseignant] = useState<number | null>(null);
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idMatiere, setIdMatiere] = useState<number | null>(null);
  const [idLecon, setIdLecon] = useState<number | null>(null);
  const [newLeconTitre, setNewLeconTitre] = useState('');
  const [chapitre, setChapitre] = useState('');
  const [nombreHeure, setNombreHeure] = useState('1');
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [date, setDate] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const isTeacher = user?.droit === 'enseignant';
  const isValid = (isTeacher || idEnseignant) && idClasse && idMatiere && idTrimestre && idAnnee && date && nombreHeure;

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    const payload = {
      id_enseignant: idEnseignant,
      id_classe: idClasse,
      id_matiere: idMatiere,
      chapitre: chapitre || null,
      id_lecon: idLecon,
      new_lecon_titre: newLeconTitre || null,
      nombre_heure: Number(nombreHeure),
      id_trimestre: idTrimestre,
      id_anneeScolaire: idAnnee,
      date_emargement: date,
    };
    try {
      if (!isOnline) {
        const classeLabel = context?.classes.find((c) => c.id_classe === idClasse)?.nom_classe ?? '';
        const matiereLabel = context?.matieres.find((m) => m.id_matiere === idMatiere)?.nom_matiere ?? '';
        await enqueueAction({
          kind: 'emargement',
          label: `${classeLabel} · ${matiereLabel} · ${date}`,
          endpoint: '/emargements',
          method: 'post',
          payload,
        });
        setSuccessMessage('Émargement enregistré hors ligne, sera synchronisé.');
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }
      await api.post('/emargements', payload);
      setSuccessMessage('Émargement enregistré avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cet émargement.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoadingContext) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (!context) return <Text style={styles.error}>{contextError ?? 'Impossible de charger le formulaire.'}</Text>;

  const enseignantOptions = context.enseignants.map((e) => ({ value: e.id_enseignant, label: e.nom_prenom_enseignant }));
  const classeOptions = context.classes.map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const matiereOptions = context.matieres.map((m) => ({ value: m.id_matiere, label: m.nom_matiere }));
  const leconOptions = context.lecons.map((l) => ({ value: l.id_lecon, label: `${l.numero}. ${l.titre}` }));
  const trimestreOptions = context.trimestres.map((t) => ({ value: t.id_trimestre, label: `Trimestre ${t.id_trimestre}` }));
  const anneeOptions = context.annees.map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      {!isTeacher ? (
        <SelectField label={requiredLabel('Enseignant')} value={idEnseignant} options={enseignantOptions} onChange={(v) => setIdEnseignant(v as number)} />
      ) : null}
      <SelectField label={requiredLabel('Classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label={requiredLabel('Matière')} value={idMatiere} options={matiereOptions} onChange={(v) => setIdMatiere(v as number)} />
      <SelectField label="Leçon existante (optionnel)" value={idLecon} options={leconOptions} onChange={(v) => setIdLecon(v as number)} />
      <TextInput mode="outlined" label="Ou nouvelle leçon (titre)" value={newLeconTitre} onChangeText={setNewLeconTitre} style={styles.input} />
      <TextInput mode="outlined" label="Chapitre (optionnel)" value={chapitre} onChangeText={setChapitre} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel("Nombre d'heures")} keyboardType="numeric" value={nombreHeure} onChangeText={setNombreHeure} style={styles.input} />
      <SelectField label={requiredLabel('Trimestre')} value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <SelectField label={requiredLabel('Année scolaire')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <DateField label={requiredLabel('Date')} value={date} onChange={setDate} />

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
});
