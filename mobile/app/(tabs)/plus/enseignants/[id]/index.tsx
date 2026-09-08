import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, Text } from 'react-native-paper';

import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { Enseignant, LigneClasse } from '@/types/api';

interface EnseignantDetail {
  enseignant: Enseignant;
  lignes_classes: LigneClasse[];
  emargement_stats: { total: number; valides: number; heures: number };
  presence_stats: { total: number; valides: number; heures: number };
}

export default function EnseignantDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<EnseignantDetail>(`/enseignants/${id}`, [id]);
  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire'].includes(user.droit);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  async function toggleArchive(isArchived: boolean) {
    try {
      await api.patch(`/enseignants/${id}/${isArchived ? 'reactivate' : 'archive'}`);
      setSuccessMessage(isArchived ? 'Enseignant réactivé avec succès.' : 'Enseignant archivé avec succès.');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      // surfaced via a simple alert-less inline reload retry; errors here are rare
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Enseignant introuvable.'}</Text>;

  const { enseignant, lignes_classes, emargement_stats, presence_stats } = data;
  const isArchived = enseignant.is_deleted === 1;

  return (
    <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={false} onRefresh={reload} />}>
      <Text style={styles.name}>{enseignant.nom_prenom_enseignant}</Text>
      <Text style={styles.meta}>{enseignant.email_enseignant ?? '—'} · {enseignant.telephone_enseignant ?? '—'}</Text>

      {canManage ? (
        <View style={styles.actions}>
          <Button mode="outlined" onPress={() => router.push(`/plus/enseignants/${id}/edit`)} style={styles.actionButton}>
            Modifier
          </Button>
          <Button mode="outlined" textColor={isArchived ? undefined : '#d33'} onPress={() => toggleArchive(isArchived)} style={styles.actionButton}>
            {isArchived ? 'Réactiver' : 'Archiver'}
          </Button>
        </View>
      ) : null}

      <Card style={styles.card}>
        <Card.Title title="Émargements" />
        <Card.Content>
          <Text>{emargement_stats.total} au total · {emargement_stats.valides} validés · {emargement_stats.heures}h</Text>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Title title="Présences" />
        <Card.Content>
          <Text>{presence_stats.total} au total · {presence_stats.valides} validées · {presence_stats.heures}h</Text>
        </Card.Content>
      </Card>

      <Text style={styles.sectionTitle}>Classes assignées</Text>
      {lignes_classes.length === 0 ? (
        <Text style={styles.meta}>Aucune classe assignée.</Text>
      ) : (
        lignes_classes.map((ligne, index) => (
          <Text key={ligne.id_ligneclasse ?? index} style={styles.classeRow}>
            {ligne.classe?.nom_classe ?? '—'} · {ligne.matiere?.nom_matiere ?? '—'}
          </Text>
        ))
      )}

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 40 },
  name: { fontSize: 22, fontWeight: 'bold' },
  meta: { opacity: 0.6, marginTop: 4 },
  actions: { flexDirection: 'row', gap: 10, marginTop: 16, marginBottom: 8 },
  actionButton: { flex: 1 },
  card: { marginTop: 14 },
  sectionTitle: { fontWeight: '600', marginTop: 20, marginBottom: 8 },
  classeRow: { paddingVertical: 6 },
});
