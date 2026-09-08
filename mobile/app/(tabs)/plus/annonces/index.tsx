import { useState } from 'react';
import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, FAB, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { hasPermission } from '@/lib/permissions';
import { usePaginatedApi } from '@/lib/useApi';

interface Annonce {
  id_annonce: number;
  titre: string;
  contenu: string;
  public_cible: string;
  statut_annonce: string;
  date_publication: string | null;
  auteur?: string;
}

export default function AnnoncesScreen() {
  const { user } = useAuth();
  const list = usePaginatedApi<Annonce>('/annonces', {}, 'annonces');
  const [actionError, setActionError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const canManage = hasPermission(user, 'annonces_creation');
  const canDelete = hasPermission(user, 'annonces_supprimer');

  async function handlePublish(id: number) {
    setActionError(null);
    try {
      await api.post(`/annonces/${id}/publier`);
      setSuccessMessage('Annonce publiée avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err));
    }
  }

  async function handleArchive(id: number) {
    setActionError(null);
    try {
      await api.post(`/annonces/${id}/archiver`);
      setSuccessMessage('Annonce archivée avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err));
    }
  }

  async function handleDelete(id: number) {
    setActionError(null);
    try {
      await api.delete(`/annonces/${id}`);
      setSuccessMessage('Annonce supprimée avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err));
    }
  }

  return (
    <>
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_annonce)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error ?? actionError}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucune annonce."
        renderItem={(item) => (
          <View style={styles.row}>
            <Text style={styles.title}>{item.titre}</Text>
            <Text style={styles.content} numberOfLines={3}>
              {item.contenu}
            </Text>
            <Text style={styles.meta}>
              {item.public_cible} · {item.statut_annonce}
            </Text>
            {canManage || canDelete ? (
              <View style={styles.actions}>
                {canManage && item.statut_annonce !== 'publie' ? (
                  <Button compact onPress={() => handlePublish(item.id_annonce)}>
                    Publier
                  </Button>
                ) : null}
                {canManage && item.statut_annonce !== 'archive' ? (
                  <Button compact onPress={() => handleArchive(item.id_annonce)}>
                    Archiver
                  </Button>
                ) : null}
                {canDelete ? (
                  <Button compact textColor="#d33" onPress={() => handleDelete(item.id_annonce)}>
                    Supprimer
                  </Button>
                ) : null}
              </View>
            ) : null}
          </View>
        )}
      />
      {canManage ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/annonces/new')} /> : null}

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </>
  );
}

const styles = StyleSheet.create({
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  title: { fontSize: 16, fontWeight: '600' },
  content: { marginTop: 6, opacity: 0.8 },
  meta: { opacity: 0.6, marginTop: 6, fontSize: 12 },
  actions: { flexDirection: 'row', marginTop: 4 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
