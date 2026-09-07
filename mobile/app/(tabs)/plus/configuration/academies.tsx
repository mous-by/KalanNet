import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';

interface Academie {
  id_academie: number;
  nom_academie: string;
  code_academie: string;
  localite_academie: string;
}

export default function AcademiesScreen() {
  const list = usePaginatedApi<Academie>('/configuration/academies');
  const [editing, setEditing] = useState<Academie | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [code, setCode] = useState('');
  const [localite, setLocalite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function openDialog(item?: Academie) {
    setEditing(item ?? null);
    setNom(item?.nom_academie ?? '');
    setCode(item?.code_academie ?? '');
    setLocalite(item?.localite_academie ?? '');
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim() || !code.trim() || !localite.trim()) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_academie: nom.trim(), code_academie: code.trim(), localite_academie: localite.trim() };
      if (editing) {
        await api.put(`/configuration/academies/${editing.id_academie}`, payload);
      } else {
        await api.post('/configuration/academies', payload);
      }
      setDialogVisible(false);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette académie.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Academie) {
    try {
      await api.delete(`/configuration/academies/${item.id_academie}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_academie)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune académie.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_academie}</Text>
              <Text style={styles.meta}>
                {item.code_academie} · {item.localite_academie}
              </Text>
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
          <Dialog.Title>{editing ? 'Modifier l’académie' : 'Nouvelle académie'}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label="Nom" value={nom} onChangeText={setNom} style={styles.input} />
            <TextInput mode="outlined" label="Code" value={code} onChangeText={setCode} style={styles.input} />
            <TextInput mode="outlined" label="Localité" value={localite} onChangeText={setLocalite} style={styles.input} />
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
  meta: { opacity: 0.6, marginTop: 4 },
  actions: { flexDirection: 'row' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
