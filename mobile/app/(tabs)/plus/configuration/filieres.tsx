import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';
import { Filiere } from '@/types/api';

export default function FilieresScreen() {
  const list = usePaginatedApi<Filiere>('/configuration/filieres');
  const [editing, setEditing] = useState<Filiere | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Filiere) {
    setEditing(item ?? null);
    setNom(item?.nom_filiere ?? '');
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim()) {
      setError('Le nom de la filière est requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_filiere: nom.trim() };
      if (editing) {
        await api.put(`/configuration/filieres/${editing.id_filiere}`, payload);
      } else {
        await api.post('/configuration/filieres', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette filière.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Filiere) {
    try {
      await api.delete(`/configuration/filieres/${item.id_filiere}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_filiere)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        onEndReached={list.loadMore}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune filière.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_filiere}</Text>
            </View>
            <View style={styles.actions}>
              <Button compact onPress={() => openDialog(item)}>
                Modifier
              </Button>
              <Button compact textColor="#d33" onPress={() => handleDelete(item)}>
                Supprimer
              </Button>
            </View>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => openDialog()} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>{editing ? 'Modifier la filière' : 'Nouvelle filière'}</Dialog.Title>
          <Dialog.Content>
            <TextInput
              mode="outlined"
              label={requiredLabel('Nom')}
              value={nom}
              onChangeText={setNom}
              placeholder="Ex : Infirmier, Sage-femme..."
              style={styles.input}
            />
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Enregistrer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar
        visible={successVisible}
        message={editing ? 'Filière modifiée avec succès.' : 'Filière créée avec succès.'}
        onDismiss={() => setSuccessVisible(false)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { padding: 16, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  rowInfo: { marginBottom: 6 },
  title: { fontSize: 16, fontWeight: '600' },
  actions: { flexDirection: 'row' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
