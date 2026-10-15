import * as DocumentPicker from 'expo-document-picker';
import { router } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Checkbox, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface Options {
  niveaux: { id_classe_officielle: number; nom_classe_officielle: string; ordre_enseignement: string }[];
  matieres: { id_matiere: number; nom_matiere: string }[];
  caps: { id_cap: number; nom_cap: string }[];
}

type Fichier = { uri: string; name: string; mimeType?: string } | null;

const TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
const FONDAMENTAL = ['fondamentale1', 'fondamentale2'];

/** Création d'une évaluation commune : sujet confidentiel libéré aux écoles à l'heure fixée. */
export default function NouvelleEvaluationCommuneScreen() {
  const { user } = useAuth();
  const { data: options } = useApiGet<Options>('/evaluations-communes/territoire', []);
  const [titre, setTitre] = useState('');
  const [niveau, setNiveau] = useState<number | null>(null);
  const [matiere, setMatiere] = useState<number | null>(null);
  const [date, setDate] = useState<string | null>(null);
  const [heure, setHeure] = useState('08:00');
  const [duree, setDuree] = useState('120');
  const [liberation, setLiberation] = useState('60');
  const [consignes, setConsignes] = useState('');
  const [caps, setCaps] = useState<number[]>([]);
  const [sujet, setSujet] = useState<Fichier>(null);
  const [corrige, setCorrige] = useState<Fichier>(null);
  const [erreur, setErreur] = useState<string | null>(null);
  const [envoi, setEnvoi] = useState(false);

  const ordre = options?.niveaux.find((n) => n.id_classe_officielle === niveau)?.ordre_enseignement;
  const fondamental = ordre ? FONDAMENTAL.includes(ordre) : false;

  async function choisir(setter: (f: Fichier) => void) {
    const resultat = await DocumentPicker.getDocumentAsync({ type: TYPES, copyToCacheDirectory: true });
    if (!resultat.canceled && resultat.assets?.[0]) {
      const a = resultat.assets[0];
      setter({ uri: a.uri, name: a.name, mimeType: a.mimeType });
    }
  }

  async function envoyer() {
    if (!titre.trim() || !niveau || !date || !/^\d{2}:\d{2}$/.test(heure) || !sujet) {
      setErreur("Le titre, le niveau, la date, l'heure (HH:MM) et le sujet sont obligatoires.");
      return;
    }
    setErreur(null);
    setEnvoi(true);
    try {
      const form = new FormData();
      form.append('titre', titre.trim());
      form.append('id_classe_officielle', String(niveau));
      if (matiere) form.append('id_matiere', String(matiere));
      form.append('debut_at', `${date} ${heure}:00`);
      form.append('duree_minutes', duree || '0');
      form.append('liberation_minutes', liberation || '0');
      if (consignes.trim()) form.append('consignes', consignes.trim());
      if (user?.droit === 'DAE' && fondamental) caps.forEach((c) => form.append('caps[]', String(c)));
      form.append('sujet', { uri: sujet.uri, name: sujet.name, type: sujet.mimeType ?? 'application/pdf' } as unknown as Blob);
      if (corrige) form.append('corrige', { uri: corrige.uri, name: corrige.name, type: corrige.mimeType ?? 'application/pdf' } as unknown as Blob);

      const { data } = await api.post<{ id: number; message: string }>('/evaluations-communes/territoire', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      router.replace(`/plus/evaluations-communes/territoire/${data.id}`);
    } catch (err) {
      setErreur(apiErrorMessage(err, "Impossible de créer l'évaluation."));
    } finally {
      setEnvoi(false);
    }
  }

  if (!options) return <ActivityIndicator style={styles.spinner} />;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <TextInput mode="outlined" label={requiredLabel('Titre')} value={titre} onChangeText={setTitre} style={styles.champ} />
      <SelectField
        label={requiredLabel('Niveau')}
        value={niveau}
        options={options.niveaux.map((n) => ({ value: n.id_classe_officielle, label: n.nom_classe_officielle }))}
        onChange={(v) => setNiveau(v as number)}
      />
      <SelectField
        label="Matière"
        value={matiere}
        options={[{ value: 0, label: '— Aucune —' }, ...options.matieres.map((m) => ({ value: m.id_matiere, label: m.nom_matiere }))]}
        onChange={(v) => setMatiere((v as number) || null)}
      />
      <DateField label={requiredLabel("Date de l'épreuve")} value={date} onChange={setDate} />
      <TextInput mode="outlined" label={requiredLabel('Heure de début (HH:MM)')} value={heure} onChangeText={setHeure} style={styles.champ} />
      <TextInput mode="outlined" label="Durée (minutes)" keyboardType="numeric" value={duree} onChangeText={setDuree} style={styles.champ} />
      <TextInput
        mode="outlined"
        label="Sujet libéré combien de minutes avant ?"
        keyboardType="numeric"
        value={liberation}
        onChangeText={setLiberation}
        style={styles.champ}
      />
      <TextInput mode="outlined" label="Consignes" value={consignes} onChangeText={setConsignes} multiline style={styles.champ} />

      {user?.droit === 'DAE' && fondamental && options.caps.length ? (
        <View style={styles.bloc}>
          <Text style={styles.blocTitre}>CAP destinataires (aucun coché : tous les CAP)</Text>
          {options.caps.map((c) => (
            <Checkbox.Item
              key={c.id_cap}
              label={c.nom_cap}
              position="leading"
              labelStyle={styles.cap}
              status={caps.includes(c.id_cap) ? 'checked' : 'unchecked'}
              onPress={() => setCaps((prev) => (prev.includes(c.id_cap) ? prev.filter((x) => x !== c.id_cap) : [...prev, c.id_cap]))}
            />
          ))}
        </View>
      ) : null}
      {ordre && !fondamental ? <Text style={styles.info}>Secondaire : envoyée directement aux établissements de l'académie.</Text> : null}

      <Button mode="outlined" icon="file-upload-outline" onPress={() => choisir(setSujet)} style={styles.champ}>
        {sujet ? `Sujet : ${sujet.name}` : 'Choisir le sujet (obligatoire)'}
      </Button>
      <Button mode="outlined" icon="file-check-outline" onPress={() => choisir(setCorrige)} style={styles.champ}>
        {corrige ? `Corrigé : ${corrige.name}` : 'Choisir le corrigé (facultatif)'}
      </Button>

      {erreur ? <Text style={styles.erreur}>{erreur}</Text> : null}
      <Button mode="contained" loading={envoi} disabled={envoi} onPress={envoyer}>
        Publier l'évaluation
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  spinner: { marginTop: 40 },
  content: { padding: 16, paddingBottom: 40 },
  champ: { marginBottom: 12 },
  bloc: { marginBottom: 12 },
  blocTitre: { fontWeight: '600', marginBottom: 4 },
  cap: { textAlign: 'left' },
  info: { opacity: 0.7, marginBottom: 12 },
  erreur: { color: '#d33', marginBottom: 12 },
});
