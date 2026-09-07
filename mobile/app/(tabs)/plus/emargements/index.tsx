import { useState } from 'react';
import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, FAB, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';
import { Classe, Enseignant, Matiere } from '@/types/api';

interface Emargement {
  id_emargement: number;
  chapitre: string | null;
  nombre_heure: number;
  date_emargement: string;
  valide: boolean | number;
  enseignant?: Enseignant;
  classe?: Classe;
  matiere?: Matiere;
}

export default function EmargementsScreen() {
  const { user } = useAuth();
  const list = usePaginatedApi<Emargement>('/emargements', {}, 'emargements');
  const [actionError, setActionError] = useState<string | null>(null);

  const canValidate = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire'].includes(user.droit);
  const canDelete = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire', 'enseignant'].includes(user.droit);

  async function handleValidate(id: number) {
    setActionError(null);
    try {
      await api.post(`/emargements/${id}/validate`);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de valider.'));
    }
  }

  async function handleDelete(id: number) {
    setActionError(null);
    try {
      await api.delete(`/emargements/${id}`);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de supprimer.'));
    }
  }

  return (
    <>
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_emargement)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error ?? actionError}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucun émargement."
        renderItem={(item) => (
          <View style={styles.row}>
            <Text style={styles.title}>
              {item.classe?.nom_classe ?? '—'} · {item.matiere?.nom_matiere ?? '—'}
            </Text>
            <Text style={styles.meta}>
              {item.date_emargement} · {item.nombre_heure}h · {item.enseignant?.nom_prenom_enseignant ?? ''}
            </Text>
            {item.chapitre ? <Text style={styles.meta}>{item.chapitre}</Text> : null}
            <Text style={item.valide ? styles.validated : styles.pending}>{item.valide ? 'Validé' : 'En attente'}</Text>
            {!item.valide ? (
              <View style={styles.actions}>
                {canValidate ? (
                  <Button compact onPress={() => handleValidate(item.id_emargement)}>
                    Valider
                  </Button>
                ) : null}
                {canDelete ? (
                  <Button compact textColor="#d33" onPress={() => handleDelete(item.id_emargement)}>
                    Supprimer
                  </Button>
                ) : null}
              </View>
            ) : null}
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/emargements/new')} />
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
  meta: { opacity: 0.6, marginTop: 4 },
  validated: { color: '#1f8a4c', marginTop: 6, fontSize: 12 },
  pending: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  actions: { flexDirection: 'row', marginTop: 4 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
