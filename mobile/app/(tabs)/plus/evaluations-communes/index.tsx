import { useState } from 'react';
import { Alert, FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text } from 'react-native-paper';

import { apiErrorMessage } from '@/lib/api';
import { downloadAndShare } from '@/lib/downloadFile';
import { dateHeure, EvaluationCommune, nomFichier } from '@/lib/evaluationsCommunes';
import { useApiGet } from '@/lib/useApi';

/**
 * École : évaluations communes reçues (académie ou CAP). Le sujet ne se
 * télécharge qu'à partir de l'heure de libération, le corrigé après
 * l'épreuve ; chaque téléchargement est enregistré (accusé de réception).
 */
export default function EvaluationsCommunesEcoleScreen() {
  const { data, isLoading, error, reload } = useApiGet<{ evaluations: EvaluationCommune[] }>('/evaluations-communes', []);
  const [enCours, setEnCours] = useState<string | null>(null);

  async function telecharger(e: EvaluationCommune, piece: 'sujet' | 'corrige') {
    setEnCours(`${e.id}-${piece}`);
    try {
      await downloadAndShare(`/evaluations-communes/${e.id}/${piece}`, nomFichier(e.titre, piece, piece === 'sujet' ? e.sujet_nom : e.corrige_nom));
      reload();
    } catch (err) {
      Alert.alert('Téléchargement', apiErrorMessage(err, "Ce document n'est pas encore disponible."));
    } finally {
      setEnCours(null);
    }
  }

  if (isLoading && !data) return <ActivityIndicator style={styles.spinner} />;

  return (
    <FlatList
      data={data?.evaluations ?? []}
      keyExtractor={(e) => String(e.id)}
      contentContainerStyle={styles.liste}
      ListHeaderComponent={error ? <Text style={styles.erreur}>{error}</Text> : null}
      ListEmptyComponent={<Text style={styles.vide}>Aucune évaluation commune pour votre école.</Text>}
      renderItem={({ item: e }) => (
        <View style={styles.carte}>
          <Text style={styles.titre}>{e.titre}</Text>
          <Text style={styles.meta}>
            {[e.niveau_libelle, e.matiere_libelle].filter(Boolean).join(' · ')}
          </Text>
          <Text style={styles.etat}>{e.etat}</Text>
          <Text style={styles.ligne}>Épreuve : {dateHeure(e.debut_at)} ({e.duree_minutes} min)</Text>
          <Text style={styles.ligne}>Sujet disponible : {dateHeure(e.liberation_sujet_at)}</Text>
          {e.consignes ? <Text style={styles.consignes}>{e.consignes}</Text> : null}
          {e.consignes_cap ? <Text style={styles.consignes}>Consignes du CAP : {e.consignes_cap}</Text> : null}
          {e.telecharge_at ? <Text style={styles.accuse}>Sujet téléchargé le {dateHeure(e.telecharge_at)}</Text> : null}
          <View style={styles.actions}>
            <Button mode="contained" compact disabled={!e.sujet_disponible} loading={enCours === `${e.id}-sujet`} onPress={() => telecharger(e, 'sujet')} style={styles.bouton}>
              Sujet
            </Button>
            <Button mode="outlined" compact disabled={!e.corrige_disponible} loading={enCours === `${e.id}-corrige`} onPress={() => telecharger(e, 'corrige')} style={styles.bouton}>
              Corrigé
            </Button>
          </View>
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  spinner: { marginTop: 40 },
  liste: { padding: 16 },
  erreur: { color: '#d33', marginBottom: 10 },
  vide: { textAlign: 'center', opacity: 0.6, marginTop: 30 },
  carte: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14, marginBottom: 12 },
  titre: { fontWeight: '700', fontSize: 15 },
  meta: { opacity: 0.7, marginTop: 2 },
  etat: { marginTop: 6, fontWeight: '600' },
  ligne: { marginTop: 4 },
  consignes: { marginTop: 6, fontStyle: 'italic' },
  accuse: { marginTop: 6, opacity: 0.7, fontSize: 12 },
  actions: { flexDirection: 'row', gap: 10, marginTop: 12 },
  bouton: { flex: 1 },
});
