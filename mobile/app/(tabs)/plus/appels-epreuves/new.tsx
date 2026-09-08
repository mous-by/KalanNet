import { useEffect, useState } from 'react';
import { router } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Switch, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Eleve, Matiere, Trimestre } from '@/types/api';

interface Controle {
  id_controle: number;
  type_controle: string;
}

interface FormData {
  classes: Classe[];
  matieres: Matiere[];
  annees: AnneeScolaire[];
  trimestres: Trimestre[];
  statuts: Controle[];
}

export default function NewAppelEpreuveScreen() {
  const { data: context } = useApiGet<FormData>('/appels-epreuves/create');

  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idMatiere, setIdMatiere] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [date, setDate] = useState<string | null>(null);
  const [libelle, setLibelle] = useState('');
  const [heureDebut, setHeureDebut] = useState('');
  const [heureFin, setHeureFin] = useState('');
  const [notifierParent, setNotifierParent] = useState(false);
  const [eleves, setEleves] = useState<Eleve[]>([]);
  const [statuts, setStatuts] = useState<Record<number, number>>({});
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  useEffect(() => {
    if (!idClasse || !idAnnee) {
      setEleves([]);
      return;
    }
    api
      .get<{ eleves: Eleve[] }>('/appels-epreuves/create', { params: { id_classe: idClasse, id_annee_scolaire: idAnnee } })
      .then(({ data }) => setEleves(data.eleves))
      .catch(() => setEleves([]));
  }, [idClasse, idAnnee]);

  const isValid =
    idClasse && idMatiere && idAnnee && idTrimestre && date && libelle.trim() && heureDebut && heureFin && Object.keys(statuts).length > 0;

  async function handleSubmit() {
    if (!isValid) {
      setError('Veuillez remplir tous les champs et le statut d’au moins un élève.');
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await api.post('/appels-epreuves', {
        id_classe: idClasse,
        id_matiere: idMatiere,
        id_annee_scolaire: idAnnee,
        id_trimestre: idTrimestre,
        date,
        libelle: libelle.trim(),
        heure_debut: heureDebut,
        heure_fin: heureFin,
        notifier_parent: notifierParent,
        statuts,
      });
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cet appel.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!context) return <ActivityIndicator style={styles.spinner} size="large" />;

  const classeOptions = context.classes.map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const matiereOptions = context.matieres.map((m) => ({ value: m.id_matiere, label: m.nom_matiere }));
  const anneeOptions = context.annees.map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const trimestreOptions = context.trimestres.map((t) => ({ value: t.id_trimestre, label: `Trimestre ${t.id_trimestre}` }));
  const statutOptions = context.statuts.map((s) => ({ value: s.id_controle, label: s.type_controle }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <SelectField label={requiredLabel('Classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label={requiredLabel('Matière')} value={idMatiere} options={matiereOptions} onChange={(v) => setIdMatiere(v as number)} />
      <SelectField label={requiredLabel('Année scolaire')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <SelectField label={requiredLabel('Trimestre')} value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <DateField label={requiredLabel('Date')} value={date} onChange={setDate} />
      <TextInput mode="outlined" label={requiredLabel('Libellé')} value={libelle} onChangeText={setLibelle} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Heure de début (HH:MM)')} value={heureDebut} onChangeText={setHeureDebut} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Heure de fin (HH:MM)')} value={heureFin} onChangeText={setHeureFin} style={styles.input} />

      <View style={styles.switchRow}>
        <Text>Notifier les parents</Text>
        <Switch value={notifierParent} onValueChange={setNotifierParent} />
      </View>

      {eleves.length > 0 ? (
        <>
          <Text style={styles.sectionTitle}>
            Statut des élèves <Text style={styles.required}>*</Text>
          </Text>
          {eleves.map((eleve) => (
            <View key={eleve.id_eleve} style={styles.studentRow}>
              <Text style={styles.studentName}>
                {eleve.prenom_eleve} {eleve.nom_eleve}
              </Text>
              <SelectField
                label={requiredLabel('Statut')}
                value={statuts[eleve.id_eleve] ?? null}
                options={statutOptions}
                onChange={(v) => setStatuts((prev) => ({ ...prev, [eleve.id_eleve]: v as number }))}
              />
            </View>
          ))}
        </>
      ) : null}

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Enregistrer" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message="Appel d'épreuve enregistré avec succès." onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  required: { color: '#d33' },
  error: { color: '#d33', marginBottom: 12 },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  sectionTitle: { fontWeight: '600', marginTop: 8, marginBottom: 8 },
  studentRow: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 10,
    padding: 10,
    marginBottom: 10,
  },
  studentName: { fontWeight: '500', marginBottom: 6 },
});
