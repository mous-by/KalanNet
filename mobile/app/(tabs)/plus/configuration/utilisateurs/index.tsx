import { useState } from 'react';
import { router } from 'expo-router';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, FAB, Searchbar, Text } from 'react-native-paper';

import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';

interface ConfigUser {
  idUtilisateur: number;
  nomPrenom: string;
  email: string | null;
  droit: string;
  statut: number;
  ecole?: { nomEcole: string } | null;
}

export default function UtilisateursScreen() {
  const { user } = useAuth();
  const [search, setSearch] = useState('');
  const { data, isLoading, error, reload } = useApiGet<{ data: ConfigUser[] }>(
    search ? `/configuration/utilisateurs?search=${encodeURIComponent(search)}` : '/configuration/utilisateurs',
    [search]
  );
  const [actionError, setActionError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const canCreate = hasPermission(user, 'utilisateurs_creation');
  const canEditStatus = hasPermission(user, 'utilisateurs_modification');
  const canDelete = hasPermission(user, 'utilisateurs_supprimer');
  const canAssignPermissions =
    user?.droit === 'SupAdmin' ||
    user?.droit === 'Admin' ||
    hasPermission(user, 'permissions_assigner') ||
    hasPermission(user, 'permission_assigner');

  function canAssignPermissionsTo(target: ConfigUser): boolean {
    if (!canAssignPermissions) return false;
    if (target.idUtilisateur === user?.id) return false;
    if (target.droit === 'SupAdmin') return false;
    return user?.droit === 'SupAdmin' || target.droit !== 'Admin';
  }

  async function toggleStatus(target: ConfigUser) {
    setActionError(null);
    try {
      await api.patch(`/configuration/utilisateurs/${target.idUtilisateur}/status`, { statut: target.statut ? 0 : 1 });
      setSuccessMessage(target.statut ? 'Utilisateur désactivé avec succès.' : 'Utilisateur activé avec succès.');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setActionError(apiErrorMessage(err));
    }
  }

  async function handleDelete(target: ConfigUser) {
    setActionError(null);
    try {
      await api.delete(`/configuration/utilisateurs/${target.idUtilisateur}`);
      setSuccessMessage('Utilisateur supprimé avec succès.');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setActionError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <Searchbar placeholder="Nom ou email…" value={search} onChangeText={setSearch} style={styles.search} />
      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : error ? (
        <Text style={styles.error}>{error}</Text>
      ) : (
        <FlatList
          data={data?.data ?? []}
          keyExtractor={(item) => String(item.idUtilisateur)}
          contentContainerStyle={styles.list}
          refreshing={false}
          onRefresh={reload}
          ListEmptyComponent={<Text style={styles.empty}>Aucun utilisateur.</Text>}
          renderItem={({ item }) => (
            <View style={styles.row}>
              <Text style={styles.name}>{item.nomPrenom}</Text>
              <Text style={styles.meta}>
                {item.droit} · {item.email ?? '—'} {item.ecole?.nomEcole ? `· ${item.ecole.nomEcole}` : ''}
              </Text>
              <View style={styles.actions}>
                {canAssignPermissionsTo(item) ? (
                  <Button compact onPress={() => router.push(`/plus/configuration/utilisateurs/${item.idUtilisateur}/permissions`)}>
                    Permissions
                  </Button>
                ) : null}
                {item.idUtilisateur !== user?.id ? (
                  <>
                    {canEditStatus ? (
                      <Button compact onPress={() => toggleStatus(item)}>
                        {item.statut ? 'Désactiver' : 'Activer'}
                      </Button>
                    ) : null}
                    {canDelete ? (
                      <Button compact textColor="#d33" onPress={() => handleDelete(item)}>
                        Supprimer
                      </Button>
                    ) : null}
                  </>
                ) : null}
              </View>
            </View>
          )}
        />
      )}
      {actionError ? <Text style={styles.error}>{actionError}</Text> : null}
      {canCreate ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/configuration/utilisateurs/new')} /> : null}

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  search: { margin: 16, marginBottom: 0 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  list: { padding: 16, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  name: { fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 2, marginBottom: 6 },
  actions: { flexDirection: 'row', flexWrap: 'wrap' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
