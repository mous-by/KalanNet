import * as DocumentPicker from 'expo-document-picker';
import { useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text, TextInput } from 'react-native-paper';

import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { downloadAndShare } from '@/lib/downloadFile';
import { dateHeure, EvaluationCommune, nomFichier } from '@/lib/evaluationsCommunes';
import { useApiGet } from '@/lib/useApi';

interface Detail {
  evaluation: EvaluationCommune;
  gerable: boolean;
  a_publier: boolean;
  passe_par_cap: boolean;
  caps: { id_cap: number; nom_cap: string; relayee_at: string | null; relayee_par: string | null }[];
  ecoles: { id: number; nom: string; cap: string | null; a_le_niveau: boolean | null; telecharge_at: string | null }[];
}

/** Suivi d'une évaluation commune : écoles qui l'ont reçue et téléchargée ; actions du DAE / DCAP. */
export default function EvaluationCommuneDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, error, reload } = useApiGet<Detail>(`/evaluations-communes/territoire/${id}`, [id]);
  const [consignesCap, setConsignesCap] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [enCours, setEnCours] = useState<string | null>(null);

  async function action(cle: string, appel: () => Promise<{ data: { message: string } }>) {
    setEnCours(cle);
    try {
      const { data: reponse } = await appel();
      setMessage(reponse.message);
      reload();
    } catch (err) {
      Alert.alert('Évaluation commune', apiErrorMessage(err, 'Action impossible.'));
    } finally {
      setEnCours(null);
    }
  }

  async function envoyerCorrige() {
    const resultat = await DocumentPicker.getDocumentAsync({ type: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'], copyToCacheDirectory: true });
    if (resultat.canceled || !resultat.assets?.[0]) return;
    const a = resultat.assets[0];
    const form = new FormData();
    form.append('corrige', { uri: a.uri, name: a.name, type: a.mimeType ?? 'application/pdf' } as unknown as Blob);
    action('corrige', () => api.post(`/evaluations-communes/territoire/${id}/corrige`, form, { headers: { 'Content-Type': 'multipart/form-data' } }));
  }

  function annuler() {
    Alert.alert('Annuler cette évaluation ?', "Les écoles n'y auront plus accès.", [
      { text: 'Non', style: 'cancel' },
      { text: 'Annuler l\'évaluation', style: 'destructive', onPress: () => action('annuler', () => api.post(`/evaluations-communes/territoire/${id}/annuler`)) },
    ]);
  }

  async function telecharger(piece: 'sujet' | 'corrige') {
    if (!data) return;
    setEnCours(piece);
    try {
      const e = data.evaluation;
      await downloadAndShare(`/evaluations-communes/territoire/${id}/${piece}`, nomFichier(e.titre, piece, piece === 'sujet' ? e.sujet_nom : e.corrige_nom));
    } catch (err) {
      Alert.alert('Téléchargement', apiErrorMessage(err, 'Téléchargement impossible.'));
    } finally {
      setEnCours(null);
    }
  }

  if (isLoading && !data) return <ActivityIndicator style={styles.spinner} />;
  if (!data) return <Text style={styles.erreur}>{error ?? 'Évaluation introuvable.'}</Text>;

  const e = data.evaluation;
  const recues = data.ecoles.filter((x) => x.telecharge_at).length;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.titre}>{e.titre}</Text>
      <Text style={styles.meta}>{[e.niveau_libelle, e.matiere_libelle].filter(Boolean).join(' · ')}</Text>
      <Text style={styles.etat}>
        {e.etat}
        {e.portee ? ` · ${e.portee}` : ''}
      </Text>
      <Text style={styles.ligne}>Épreuve : {dateHeure(e.debut_at)} ({e.duree_minutes} min)</Text>
      <Text style={styles.ligne}>Sujet libéré le {dateHeure(e.liberation_sujet_at)}</Text>
      {e.consignes ? <Text style={styles.consignes}>{e.consignes}</Text> : null}

      <View style={styles.actions}>
        <Button mode="outlined" compact loading={enCours === 'sujet'} onPress={() => telecharger('sujet')} style={styles.bouton}>
          Sujet
        </Button>
        <Button mode="outlined" compact disabled={!e.corrige_nom} loading={enCours === 'corrige'} onPress={() => telecharger('corrige')} style={styles.bouton}>
          Corrigé
        </Button>
      </View>

      {data.a_publier ? (
        <View style={styles.bloc}>
          <Text style={styles.blocTitre}>Publier à vos écoles</Text>
          <TextInput mode="outlined" label="Consignes du CAP (facultatif)" value={consignesCap} onChangeText={setConsignesCap} multiline style={styles.champ} />
          <Button
            mode="contained"
            loading={enCours === 'publier'}
            onPress={() => action('publier', () => api.post(`/evaluations-communes/territoire/${id}/publier`, { consignes_cap: consignesCap }))}
          >
            Publier à mes écoles
          </Button>
        </View>
      ) : null}

      {data.gerable && e.statut !== 'annulee' ? (
        <View style={styles.actions}>
          <Button mode="outlined" compact icon="file-check-outline" loading={enCours === 'corrige'} onPress={envoyerCorrige} style={styles.bouton}>
            {e.corrige_nom ? 'Remplacer le corrigé' : 'Ajouter le corrigé'}
          </Button>
          <Button mode="outlined" compact textColor="#d33" loading={enCours === 'annuler'} onPress={annuler} style={styles.bouton}>
            Annuler
          </Button>
        </View>
      ) : null}

      {data.caps.length ? (
        <>
          <Text style={styles.section}>CAP destinataires</Text>
          {data.caps.map((c) => (
            <View key={c.id_cap} style={styles.rang}>
              <Text style={styles.nom}>{c.nom_cap}</Text>
              <Text style={c.relayee_at ? styles.ok : styles.attente}>
                {c.relayee_at ? `Publiée le ${dateHeure(c.relayee_at)}` : 'Pas encore publiée'}
              </Text>
            </View>
          ))}
        </>
      ) : null}

      <Text style={styles.section}>
        Écoles ({recues} / {data.ecoles.length} ont téléchargé le sujet)
      </Text>
      {data.ecoles.map((ecole) => (
        <View key={ecole.id} style={styles.rang}>
          <Text style={styles.nom}>{ecole.nom}</Text>
          <Text style={styles.meta}>
            {ecole.cap ?? ''}
            {ecole.a_le_niveau === false ? ' · pas de classe de ce niveau' : ''}
          </Text>
          <Text style={ecole.telecharge_at ? styles.ok : styles.attente}>
            {ecole.telecharge_at ? `Téléchargé le ${dateHeure(ecole.telecharge_at)}` : 'Pas encore téléchargé'}
          </Text>
        </View>
      ))}

      <SuccessSnackbar visible={!!message} message={message ?? ''} onDismiss={() => setMessage(null)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  spinner: { marginTop: 40 },
  erreur: { color: '#d33', padding: 16 },
  content: { padding: 16, paddingBottom: 40 },
  titre: { fontWeight: '700', fontSize: 17 },
  meta: { opacity: 0.7, marginTop: 2 },
  etat: { marginTop: 8, fontWeight: '600' },
  ligne: { marginTop: 4 },
  consignes: { marginTop: 8, fontStyle: 'italic' },
  actions: { flexDirection: 'row', gap: 10, marginTop: 14 },
  bouton: { flex: 1 },
  bloc: { marginTop: 18 },
  blocTitre: { fontWeight: '700', marginBottom: 8 },
  champ: { marginBottom: 10 },
  section: { fontWeight: '700', fontSize: 15, marginTop: 22, marginBottom: 8 },
  rang: { paddingVertical: 8, borderBottomWidth: StyleSheet.hairlineWidth, borderColor: 'rgba(128,128,128,0.3)' },
  nom: { fontWeight: '600' },
  ok: { color: '#2e7d32', marginTop: 2, fontSize: 13 },
  attente: { opacity: 0.6, marginTop: 2, fontSize: 13 },
});
