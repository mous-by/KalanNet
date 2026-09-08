import { useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, Divider, Portal, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

export default function ClasseDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const { data: classe, isLoading, error, reload } = useApiGet<Classe>(`/classes/${id}`, [id]);
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const [confirmVisible, setConfirmVisible] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  const canEdit = hasPermission(user, 'classes_modification');
  const canDelete = hasPermission(user, 'classes_supprimer');

  async function handleDelete() {
    setIsDeleting(true);
    setDeleteError(null);
    try {
      await api.delete(`/classes/${id}`);
      router.back();
    } catch (err) {
      setDeleteError(apiErrorMessage(err, 'Impossible de supprimer cette classe.'));
      setConfirmVisible(false);
    } finally {
      setIsDeleting(false);
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !classe) return <Text style={styles.error}>{error ?? 'Classe introuvable.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={false} onRefresh={reload} />}>
      <Text style={styles.name}>{classe.nom_classe}</Text>
      <Text style={styles.meta}>{classe.ordreEnseignement}</Text>

      {canEdit || canDelete ? (
        <View style={styles.actions}>
          {canEdit ? (
            <Button mode="outlined" onPress={() => router.push(`/classes/${id}/edit`)} style={styles.actionButton}>
              Modifier
            </Button>
          ) : null}
          {canDelete ? (
            <Button mode="outlined" textColor="#d33" onPress={() => setConfirmVisible(true)} style={styles.actionButton}>
              Supprimer
            </Button>
          ) : null}
        </View>
      ) : null}

      {deleteError ? <Text style={styles.error}>{deleteError}</Text> : null}

      <Text style={styles.sectionTitle}>Matières</Text>
      {(classe.ligneClasses ?? []).map((ligne, index) => (
        <View key={ligne.id_ligneclasse ?? index}>
          {index > 0 ? <Divider style={styles.divider} /> : null}
          <View style={styles.matiereRow}>
            <Text style={styles.matiereName}>{ligne.matiere?.nom_matiere ?? '—'}</Text>
            <Text style={styles.meta}>{ligne.enseignant?.nom_prenom_enseignant ?? 'Sans enseignant'} · coef. {ligne.coefficient}</Text>
          </View>
        </View>
      ))}

      <Portal>
        <Dialog visible={confirmVisible} onDismiss={() => setConfirmVisible(false)}>
          <Dialog.Title>Supprimer cette classe ?</Dialog.Title>
          <Dialog.Content>
            <Text>Cette action est irréversible.</Text>
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setConfirmVisible(false)}>Annuler</Button>
            <Button textColor="#d33" loading={isDeleting} onPress={handleDelete}>
              Supprimer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
  spinner: {
    marginTop: 40,
  },
  name: {
    fontSize: 22,
    fontWeight: 'bold',
  },
  meta: {
    opacity: 0.6,
    marginTop: 4,
  },
  actions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 16,
    marginBottom: 8,
  },
  actionButton: {
    flex: 1,
  },
  sectionTitle: {
    fontWeight: '600',
    marginTop: 20,
    marginBottom: 8,
  },
  matiereRow: {
    paddingVertical: 8,
  },
  matiereName: {
    fontWeight: '500',
  },
  divider: {
    marginVertical: 4,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginTop: 12,
  },
});
