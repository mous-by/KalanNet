import { router, useFocusEffect } from 'expo-router';
import { useCallback } from 'react';
import { FlatList, Pressable, StyleSheet } from 'react-native';
import { ActivityIndicator, FAB, Text } from 'react-native-paper';

import { dateHeure, EvaluationCommune } from '@/lib/evaluationsCommunes';
import { useApiGet } from '@/lib/useApi';

/** DAE / DCAP : évaluations communes créées ou reçues (DCAP : celles de l'académie adressées à son CAP). */
export default function EvaluationsCommunesTerritoireScreen() {
  const { data, isLoading, error, reload } = useApiGet<{ evaluations: (EvaluationCommune & { adressee_au_cap?: number | null; relayee_at?: string | null })[] }>(
    '/evaluations-communes/territoire',
    []
  );

  useFocusEffect(
    useCallback(() => {
      reload();
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])
  );

  if (isLoading && !data) return <ActivityIndicator style={styles.spinner} />;

  return (
    <>
      <FlatList
        data={data?.evaluations ?? []}
        keyExtractor={(e) => String(e.id)}
        contentContainerStyle={styles.liste}
        ListHeaderComponent={error ? <Text style={styles.erreur}>{error}</Text> : null}
        ListEmptyComponent={<Text style={styles.vide}>Aucune évaluation commune.</Text>}
        renderItem={({ item: e }) => (
          <Pressable style={styles.carte} onPress={() => router.push(`/plus/evaluations-communes/territoire/${e.id}`)}>
            <Text style={styles.titre}>{e.titre}</Text>
            <Text style={styles.meta}>{[e.niveau_libelle, e.matiere_libelle].filter(Boolean).join(' · ')}</Text>
            <Text style={styles.ligne}>Épreuve : {dateHeure(e.debut_at)}</Text>
            <Text style={styles.etat}>
              {e.etat}
              {e.portee ? ` · ${e.portee}` : ''}
            </Text>
            {e.origine === 'academie' && e.adressee_au_cap && !e.relayee_at ? <Text style={styles.aPublier}>À publier à vos écoles</Text> : null}
          </Pressable>
        )}
      />
      <FAB icon="plus" label="Nouvelle" style={styles.fab} onPress={() => router.push('/plus/evaluations-communes/territoire/new')} />
    </>
  );
}

const styles = StyleSheet.create({
  spinner: { marginTop: 40 },
  liste: { padding: 16, paddingBottom: 90 },
  erreur: { color: '#d33', marginBottom: 10 },
  vide: { textAlign: 'center', opacity: 0.6, marginTop: 30 },
  carte: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14, marginBottom: 12 },
  titre: { fontWeight: '700', fontSize: 15 },
  meta: { opacity: 0.7, marginTop: 2 },
  ligne: { marginTop: 4 },
  etat: { marginTop: 6, fontWeight: '600' },
  aPublier: { marginTop: 6, color: '#b26a00', fontWeight: '700' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
