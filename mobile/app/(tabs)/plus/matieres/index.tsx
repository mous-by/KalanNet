import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Checkbox, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';
import { Matiere } from '@/types/api';

const ALL_ORDRES = ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'];
const ORDRE_LABELS: Record<string, string> = {
  'Fondamentale I': 'Fondamentale I',
  'Fondamentale II': 'Fondamentale II',
  'Secondaire Generale': 'Secondaire Générale',
  'Secondaire Technique et Professionnel': 'Secondaire Technique et Professionnel',
};

export default function MatieresScreen() {
  const list = usePaginatedApi<Matiere>('/matieres', {}, 'data');
  const [editing, setEditing] = useState<Matiere | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [ordres, setOrdres] = useState<Set<string>>(new Set());
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Matiere) {
    setEditing(item ?? null);
    setNom(item?.nom_matiere ?? '');
    setOrdres(new Set((item?.ordres ?? []).map((o) => o.ordre_enseignement)));
    setError(null);
    setDialogVisible(true);
  }

  function toggleOrdre(ordre: string) {
    setOrdres((prev) => {
      const next = new Set(prev);
      if (next.has(ordre)) next.delete(ordre);
      else next.add(ordre);
      return next;
    });
  }

  async function handleSubmit() {
    if (!nom.trim() || ordres.size === 0) {
      setError('Le nom et au moins un ordre d’enseignement sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_matiere: nom.trim(), ordre_enseignement: Array.from(ordres) };
      if (editing) {
        await api.put(`/matieres/${editing.id_matiere}`, payload);
      } else {
        await api.post('/matieres', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette matière.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Matiere) {
    try {
      await api.delete(`/matieres/${item.id_matiere}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_matiere)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune matière.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_matiere}</Text>
              <Text style={styles.meta}>{(item.ordres ?? []).map((o) => ORDRE_LABELS[o.ordre_enseignement] ?? o.ordre_enseignement).join(', ')}</Text>
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
          <Dialog.Title>{editing ? 'Modifier la matière' : 'Nouvelle matière'}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel('Nom')} value={nom} onChangeText={setNom} style={styles.input} />
            <Text style={styles.sectionTitle}>
              Ordres d'enseignement <Text style={styles.required}>*</Text>
            </Text>
            {ALL_ORDRES.map((ordre) => (
              <View key={ordre} style={styles.checkRow}>
                <Checkbox status={ordres.has(ordre) ? 'checked' : 'unchecked'} onPress={() => toggleOrdre(ordre)} />
                <Text>{ORDRE_LABELS[ordre]}</Text>
              </View>
            ))}
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
        message={editing ? 'Matière modifiée avec succès.' : 'Matière créée avec succès.'}
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
  meta: { opacity: 0.6, marginTop: 4 },
  actions: { flexDirection: 'row' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  sectionTitle: { fontWeight: '600', marginTop: 4, marginBottom: 4 },
  required: { color: '#d33' },
  checkRow: { flexDirection: 'row', alignItems: 'center' },
  error: { color: '#d33', marginTop: 4 },
});
