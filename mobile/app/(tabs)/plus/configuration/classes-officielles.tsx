import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';

interface ClasseOfficielle {
  id_classe_officielle: number;
  nom_classe_officielle: string;
  ordre_enseignement: string;
}

const ORDRE_OPTIONS = [
  { value: 'Fondamentale I', label: 'Fondamentale I' },
  { value: 'Fondamentale II', label: 'Fondamentale II' },
  { value: 'Secondaire Generale', label: 'Secondaire Général' },
  { value: 'Secondaire Technique et Professionnel', label: 'Secondaire Technique et Professionnel' },
];

export default function ClassesOfficiellesScreen() {
  const list = usePaginatedApi<ClasseOfficielle>('/configuration/classes-officielles', {}, 'data');
  const [editing, setEditing] = useState<ClasseOfficielle | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [ordre, setOrdre] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function openDialog(item?: ClasseOfficielle) {
    setEditing(item ?? null);
    setNom(item?.nom_classe_officielle ?? '');
    setOrdre(item?.ordre_enseignement ?? null);
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim() || !ordre) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_classe_officielle: nom.trim(), ordre_enseignement: ordre };
      if (editing) {
        await api.put(`/configuration/classes-officielles/${editing.id_classe_officielle}`, payload);
      } else {
        await api.post('/configuration/classes-officielles', payload);
      }
      setDialogVisible(false);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette classe officielle.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: ClasseOfficielle) {
    try {
      await api.delete(`/configuration/classes-officielles/${item.id_classe_officielle}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_classe_officielle)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune classe officielle.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_classe_officielle}</Text>
              <Text style={styles.meta}>{item.ordre_enseignement}</Text>
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
          <Dialog.Title>{editing ? 'Modifier la classe officielle' : 'Nouvelle classe officielle'}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label="Nom" value={nom} onChangeText={setNom} style={styles.input} />
            <SelectField label="Ordre d'enseignement" value={ordre} options={ORDRE_OPTIONS} onChange={(v) => setOrdre(v as string)} />
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
